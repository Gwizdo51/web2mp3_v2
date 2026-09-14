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
        $this->logger->info('{method} - New download request received', ['method' => __METHOD__, '$downloadRequest' => $downloadRequest]);
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
            $this->logger->info('{method} - Same download found', ['method' => __METHOD__, '$sameDownload' => $downloadRequest]);
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
            $this->logger->info('{method} - Same download not found, dispatching message', ['method' => __METHOD__, '$downloadRequest' => $downloadRequest]);
            $this->bus->dispatch(new ConvertVideoToAudioMessage($downloadRequest));
        }
        return $downloadRequest;
    }

    public function convertVideoToAudio(DownloadRequest $downloadRequest): void {
        $this->logger->info('{method} - Called', ['method' => __METHOD__, '$downloadRequest' => $downloadRequest]);
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
        $this->logger->info('{method} - Starting new download process - Command: "{command}"', ['method' => __METHOD__, 'command' => implode(' ', $downloadProcessCommand)]);
        $downloadProcess = new Process($downloadProcessCommand);
        $downloadProcess->setTimeout(300);
        $downloadProcess->run();
        if ($downloadProcess->isSuccessful()) {
            $this->logger->info('{method} - Download process successful', ['method' => __METHOD__]);
            $this->logger->info("{method} - Output:\n{output}", ['method' => __METHOD__, 'output' => trim($downloadProcess->getOutput())]);
            $this->logger->info("{method} - Error output:\n{errorOutput}", ['method' => __METHOD__, 'errorOutput' => trim($downloadProcess->getErrorOutput())]);
            $downloadRequest->state = DownloadState::Succeeded;
            $download->setState(DownloadState::Succeeded);
            // retrieve the name of the generated file
            $lsProcess = new Process(['ls', "/app/public/storage/{$downloadRequest->id}"]);
            $lsProcess->mustRun();
            $filename = trim($lsProcess->getOutput());
            $downloadRequest->fileName = $filename;
            $download->setFileName($filename);
            // queue the file deletion job
            $this->logger->info('{method} - Dispatching file deletion message', ['method' => __METHOD__]);
            $this->bus->dispatch(new DeleteFileMessage($downloadRequest->id), [
                // new DelayStamp(60 * 1000),
                new DelayStamp(60 * 60 * 1000),
            ]);
        }
        else {
            $this->logger->error('{method} - Download process failed', ['method' => __METHOD__]);
            $this->logger->info("{method} - Output:\n{output}", ['method' => __METHOD__, 'output' => trim($downloadProcess->getOutput())]);
            $errorOutput = trim($downloadProcess->getErrorOutput());
            $this->logger->info("{method} - Error output:\n{errorOutput}", ['method' => __METHOD__, 'errorOutput' => $errorOutput]);
            // delete the folder that was created for the download
            $this->filesystem->remove("/app/public/storage/{$downloadRequest->id}");
            // update the download state
            $downloadRequest->state = DownloadState::Failed;
            $downloadRequest->error = $errorOutput;
            $download->setState(DownloadState::Failed);
            $download->setError($errorOutput);
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
        $this->logger->info('{method} - Broadcasting update', ['method' => __METHOD__, '$downloadRequest' => $downloadRequest]);
        $jsonContent = $this->serializer->serialize($downloadRequest, 'json', ['groups' => ['download_request:get']]);
        dump('broadcasting update', $jsonContent);
        $this->hub->publish(new Update(
            topics: "{$this->defaultUri}/downloads/{$downloadRequest->id}",
            data: $jsonContent,
        ));
    }

    public function deleteFile(string $id): void {
        $this->logger->info('{method} - Deleting folder {id}', ['method' => __METHOD__, 'id' => $id]);
        $this->filesystem->remove("/app/public/storage/{$id}");
        // update the state of the download in the database
        /** @var Download */
        $download = $this->downloadRepository->find($id);
        $download->setState(DownloadState::Deleted);
        $this->em->flush();
    }

    public function broadcastQueueUpdate(): void {
        // retrieve all the downloads in waiting state, along with their queue positions
        $queuePositionsArray = $this->downloadRepository->getWaitingDownloadsQueuePositions();
        $this->logger->info('{method} - Broadcasting queue position updates', ['method' => __METHOD__, 'queuePositionsArray' => $queuePositionsArray]);
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

    public function updateYtdlpBinary(): void {
        $this->logger->info('{method} - Updating yt-dlp', ['method' => __METHOD__]);
        $updateProcess = new Process(['yt-dlp', '-U']);
        $updateProcess->setTimeout(300);
        $updateProcess->run();
        if ($updateProcess->isSuccessful()) {
            $this->logger->info('{method} - Successfully updated yt-dlp', ['method' => __METHOD__]);
            $this->logger->info("{method} - Output:\n{output}", ['method' => __METHOD__, 'output' => trim($updateProcess->getOutput())]);
            $this->logger->info("{method} - Error output:\n{errorOutput}", ['method' => __METHOD__, 'errorOutput' => trim($updateProcess->getErrorOutput())]);
        }
        else {
            $this->logger->error('{method} - Error while updating yt-dlp', ['method' => __METHOD__]);
            $this->logger->info("{method} - Output:\n{output}", ['method' => __METHOD__, 'output' => trim($updateProcess->getOutput())]);
            $this->logger->info("{method} - Error output:\n{errorOutput}", ['method' => __METHOD__, 'errorOutput' => trim($updateProcess->getErrorOutput())]);
        }
    }
}
