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
use Psr\Log\LoggerInterface;
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
        protected readonly LoggerInterface $logger,
    ) {
        $this->downloadQualityToInt = [
            DownloadQuality::Best->value => 0,
            DownloadQuality::Good->value => 2,
            DownloadQuality::Average->value => 4,
            DownloadQuality::Poor->value => 6,
        ];
    }

    public function processDownloadRequest(DownloadRequest $downloadRequest): DownloadRequest {
        $this->logger->info('DownloadService->processDownloadRequest - New download request received', ['$downloadRequest' => $downloadRequest]);
        // check if another download has the same parameters
        $sameDownload = $this->downloadRepository->findSameDownload($downloadRequest);
        if ($sameDownload !== null) {
            // send the download found to the user
            $downloadRequest->id = $sameDownload->getId();
            $downloadRequest->state = $sameDownload->getState();
            $downloadRequest->fileName = $sameDownload->getFileName();
            // get the queue position of the download message
            $queuePosition = $this->downloadRepository->getQueuePosition($sameDownload->getCreatedAt());
            // dump('queue position :', $queuePosition);
            $downloadRequest->queuePosition = $queuePosition;
            $this->logger->info('DownloadService->processDownloadRequest - Same download found', ['$sameDownload' => $downloadRequest]);
        }
        else {
            $now = new DateTimeImmutable();
            // create and save a new instance of Download in the database
            $newDownload = new Download();
            $newDownload->setId(Uuid::v7()->toString());
            $newDownload->setLink($downloadRequest->link);
            $newDownload->setFormat($downloadRequest->format);
            $newDownload->setQuality($downloadRequest->quality);
            $newDownload->setState(DownloadState::Waiting);
            $newDownload->setCreatedAt($now);
            $this->em->persist($newDownload);
            $this->em->flush();
            // update the $downloadRequest
            $downloadRequest->id = $newDownload->getId();
            $downloadRequest->state = $newDownload->getState();
            // get the queue position of the download message
            $queuePosition = $this->downloadRepository->getQueuePosition($now);
            $downloadRequest->queuePosition = $queuePosition;
            // dispatch the message to download the link
            $this->logger->info('DownloadService->processDownloadRequest - Same download not found, dispatching message', ['$downloadRequest' => $downloadRequest]);
            $this->bus->dispatch(new ConvertVideoToAudioMessage($downloadRequest));
        }
        return $downloadRequest;
    }

    public function handleConvertVideoToAudio(DownloadRequest $downloadRequest): void {
        $this->logger->info('DownloadService->handleConvertVideoToAudio - Called', ['$downloadRequest' => $downloadRequest]);
        // retrieve the download from the database
        /** @var Download */
        $download = $this->downloadRepository->find($downloadRequest->id);
        // set the state to "running"
        $download->setState(DownloadState::Running);
        $downloadRequest->state = DownloadState::Running;
        $this->em->flush();
        // broadcast the queue update to all waiting downloads
        $this->bus->dispatch(new BroadcastQueueUpdateMessage());
        // notify the start of the process with Mercure
        $this->broadcastUpdate($downloadRequest);
        // download the file with yt-dlp
        $this->filesystem->mkdir("/app/public/storage/{$downloadRequest->id}");
        $downloadProcessCommand = ['yt-dlp', '-x', '-f', 'bestaudio', '--audio-format', $downloadRequest->format->value, '--audio-quality', $this->downloadQualityToInt[$downloadRequest->quality->value],
            '-o', "/app/public/storage/{$downloadRequest->id}/%(title)s.%(ext)s", '--no-playlist', '--no-cache-dir', $downloadRequest->link];
        $this->logger->info('DownloadService->handleConvertVideoToAudio - Starting new download process - Command:');
        $this->logger->info(implode(' ', $downloadProcessCommand));
        $downloadProcess = new Process($downloadProcessCommand);
        $downloadProcess->setTimeout(300);
        $downloadProcess->run();
        if ($downloadProcess->isSuccessful()) {
            $this->logger->info('DownloadService->handleConvertVideoToAudio - Download process successful - Output:');
            $this->logger->info(trim($downloadProcess->getOutput()));
            $downloadRequest->state = DownloadState::Succeeded;
            $download->setState(DownloadState::Succeeded);
            // retrieve the name of the generated file
            $lsProcess = new Process(['ls', "/app/public/storage/{$downloadRequest->id}"]);
            $lsProcess->mustRun();
            $filename = trim($lsProcess->getOutput());
            $downloadRequest->fileName = $filename;
            $download->setFileName($filename);
            // queue the file deletion job
            $this->logger->info('DownloadService->handleConvertVideoToAudio - Dispatching file deletion message');
            $this->bus->dispatch(new DeleteFileMessage($downloadRequest->id), [
                // new DelayStamp(60 * 1000),
                new DelayStamp(60 * 60 * 1000),
            ]);
        }
        else {
            $this->logger->info('DownloadService->handleConvertVideoToAudio - Download process successful');
            $this->logger->info('DownloadService->handleConvertVideoToAudio - Output:');
            $this->logger->info(trim($downloadProcess->getOutput()));
            $this->logger->info('DownloadService->handleConvertVideoToAudio - Error output:');
            $this->logger->info(trim($downloadProcess->getErrorOutput()));
            // delete the folder that was created for the download
            $this->filesystem->remove("/app/public/storage/{$downloadRequest->id}");
            // update the download state
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
        $this->logger->info('DownloadService->broadcastUpdate - Broadcasting update', ['$downloadRequest' => $downloadRequest]);
        $jsonContent = $this->serializer->serialize($downloadRequest, 'json', ['groups' => ['download_request:get']]);
        dump('broadcasting update', $jsonContent);
        $this->hub->publish(new Update(
            topics: "{$this->defaultUri}/downloads/{$downloadRequest->id}",
            data: $jsonContent,
        ));
    }

    public function handleDeleteFile(string $id): void {
        $this->logger->info("DownloadService->broadcastUpdate - Deleting folder \"{$id}\"");
        $this->filesystem->remove("/app/public/storage/{$id}");
        // update the state of the download in the database
        /** @var Download */
        $download = $this->downloadRepository->find($id);
        $download->setState(DownloadState::Deleted);
        $this->em->flush();
    }

    public function handleBroadcastQueueUpdate(): void {
        // retrieve all the downloads in waiting state, along with their queue positions
        $queuePositionsArray = $this->downloadRepository->getWaitingDownloadsQueuePositions();
        $this->logger->info('DownloadService->handleBroadcastQueueUpdate - Broadcasting queue position updates', $queuePositionsArray);
        foreach ($queuePositionsArray as $queuePositionArray) {
            // create a DownloadRequest object and broadcast it via Mercure
            $downloadRequest = new DownloadRequest(
                id: $queuePositionArray['download']['id'],
                state: $queuePositionArray['download']['state'],
                queuePosition: $queuePositionArray['queuePosition'],
            );
            $this->broadcastUpdate($downloadRequest);
        }
    }
}
