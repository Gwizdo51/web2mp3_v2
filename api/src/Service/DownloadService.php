<?php declare(strict_types=1);

namespace App\Service;

use App\ApiResource\DownloadRequest;
use App\Entity\Download;
use App\Enum\DownloadQuality;
use App\Enum\DownloadState;
use App\Message\BroadcastQueueUpdateMessage;
use App\Message\ConvertVideoToAudioMessage;
use App\Message\DeleteFileMessage;
use App\Repository\DownloadRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

class DownloadService {
    protected readonly array $downloadQualityToInt;

    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly MessageBusInterface $bus,
        protected readonly DownloadRepository $downloadRepository,
        protected readonly HubInterface $hub,
        #[Autowire(env: 'DEFAULT_URI')]
        protected readonly string $defaultUri,
        protected readonly SerializerInterface $serializer,
        protected readonly Filesystem $filesystem,
    ) {
        $this->downloadQualityToInt = [
            DownloadQuality::Best->value => 0,
            DownloadQuality::Good->value => 2,
            DownloadQuality::Average->value => 4,
            DownloadQuality::Poor->value => 6,
        ];
    }

    public function processDownloadRequest(DownloadRequest $downloadRequest): DownloadRequest {
        dump('DownloadService->processDownloadRequest called');
        // check if another download has the same parameters
        $sameDownload = $this->downloadRepository->findSameDownload($downloadRequest);
        dump('$sameDownload:', $sameDownload);
        if ($sameDownload !== null) {
            // send the download found to the user
            $downloadRequest->id = $sameDownload->getId()->toString();
            $downloadRequest->state = $sameDownload->getState();
            $downloadRequest->fileName = $sameDownload->getFileName();
            // get the queue position of the download message
            $queuePosition = $this->downloadRepository->getQueuePosition($sameDownload->getCreatedAt());
            dump('queue position :', $queuePosition);
            $downloadRequest->queuePosition = $queuePosition;
        }
        else {
            $now = new DateTimeImmutable();
            // create and save a new instance of Download in the database
            $newDownload = new Download();
            $newDownload->setId(Uuid::v7());
            $newDownload->setLink($downloadRequest->link);
            $newDownload->setFormat($downloadRequest->format);
            $newDownload->setQuality($downloadRequest->quality);
            $newDownload->setState(DownloadState::Waiting);
            $newDownload->setCreatedAt($now);
            $this->em->persist($newDownload);
            $this->em->flush();
            // update the $downloadRequest
            $downloadRequest->id = $newDownload->getId()->toString();
            $downloadRequest->state = $newDownload->getState();
            // get the queue position of the download message
            $queuePosition = $this->downloadRepository->getQueuePosition($now);
            dump('queue position :', $queuePosition);
            $downloadRequest->queuePosition = $queuePosition;
            // dispatch the message to download the link
            $this->bus->dispatch(new ConvertVideoToAudioMessage($downloadRequest));
        }
        return $downloadRequest;
    }

    public function handleConvertVideoToAudio(DownloadRequest $downloadRequest): void {
        dump('DownloadService->handleConvertVideoToAudio called');
        // retrieve the download from the database
        /** @var Download */
        $download = $this->downloadRepository->find(Uuid::fromString($downloadRequest->id));
        dump('download from database:', $download);
        // set the state to "running"
        $download->setState(DownloadState::Running);
        $downloadRequest->state = DownloadState::Running;
        $this->em->flush();
        // broadcast the queue update to all waiting downloads
        $this->bus->dispatch(new BroadcastQueueUpdateMessage());
        // notify the start of the process with Mercure
        $this->broadcastUpdate($downloadRequest);
        // download the file with yt-dlp
        dump('processing download');
        $this->filesystem->mkdir("/app/public/storage/{$downloadRequest->id}");
        $downloadProcessCommand = ['yt-dlp', '-x', '-f', 'bestaudio', '--audio-format', $downloadRequest->format->value, '--audio-quality', $this->downloadQualityToInt[$downloadRequest->quality->value],
            '-o', "/app/public/storage/{$downloadRequest->id}/%(title)s.%(ext)s", '--no-playlist', '--no-cache-dir', $downloadRequest->link];
        dump('process command:', implode(' ', $downloadProcessCommand));
        $downloadProcess = new Process($downloadProcessCommand);
        $downloadProcess->setTimeout(300);
        $downloadProcess->run();
        if ($downloadProcess->isSuccessful()) {
            dump('success', $downloadProcess->getOutput());
            $downloadRequest->state = DownloadState::Succeeded;
            $download->setState(DownloadState::Succeeded);
            // retrieve the name of the generated file
            $lsProcess = new Process(['ls', "/app/public/storage/{$downloadRequest->id}"]);
            $lsProcess->mustRun();
            $filename = trim($lsProcess->getOutput());
            $downloadRequest->fileName = $filename;
            $download->setFileName($filename);
            // queue the file deletion job
            $this->bus->dispatch(new DeleteFileMessage($downloadRequest->id), [
                new DelayStamp(60 * 1000),
            ]);
        }
        else {
            dump('error', $downloadProcess->getErrorOutput());
            $downloadRequest->state = DownloadState::Failed;
            $downloadRequest->error = $downloadProcess->getErrorOutput();
            $download->setState(DownloadState::Failed);
            $download->setError($downloadProcess->getErrorOutput());
        }
        // sleep(10);
        // $this->filesystem->dumpFile("/app/public/storage/{$downloadRequest->id}/test.txt", "test\n");
        // $downloadRequest->state = DownloadState::Succeeded;
        // $download->setState(DownloadState::Succeeded);
        // $downloadRequest->fileName = 'test.txt';
        // $download->setFileName('test.txt');
        $this->em->flush();
        $this->broadcastUpdate($downloadRequest);
    }

    protected function broadcastUpdate(DownloadRequest $downloadRequest) {
        $jsonContent = $this->serializer->serialize($downloadRequest, 'json', ['groups' => ['download_request:get']]);
        dump('broadcasting update', $jsonContent);
        $this->hub->publish(new Update(
            topics: "{$this->defaultUri}/downloads/{$downloadRequest->id}",
            data: $jsonContent,
        ));
    }

    public function handleDeleteFile(string $id): void {
        dump('handleDeleteFile called', $id);
        $this->filesystem->remove("/app/public/storage/{$id}");
        // update the state of the download in the database
        /** @var Download */
        $download = $this->downloadRepository->find(Uuid::fromString($id));
        $download->setState(DownloadState::Deleted);
        $this->em->flush();
    }

    public function handleBroadcastQueueUpdate(): void {
        dump('DownloadService->handleBroadcastQueueUpdate called');
        // retrieve all the downloads in waiting state, along with their queue positions
        $queuePositionsArray = $this->downloadRepository->getWaitingDownloadsQueuePositions();
        dump('queue positions: ', $queuePositionsArray);
        foreach ($queuePositionsArray as $queuePositionArray) {
            // create a DownloadRequest object and broadcast it via Mercure
            /** @var Uuid $id */
            $id = $queuePositionArray['download']['id'];
            $downloadRequest = new DownloadRequest(
                id: $id->toString(),
                state: $queuePositionArray['download']['state'],
                queuePosition: $queuePositionArray['queuePosition'],
            );
            $this->broadcastUpdate($downloadRequest);
        }
    }
}
