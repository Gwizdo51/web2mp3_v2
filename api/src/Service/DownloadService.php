<?php declare(strict_types=1);

namespace App\Service;

use App\ApiResource\DownloadRequest;
use App\Entity\Download;
use App\Enum\DownloadState;
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
    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly MessageBusInterface $bus,
        protected readonly DownloadRepository $downloadRepository,
        protected readonly HubInterface $hub,
        #[Autowire(env: 'DEFAULT_URI')]
        protected readonly string $defaultUri,
        protected readonly SerializerInterface $serializer,
        protected readonly Filesystem $filesystem,
    ) {}

    public function processDownloadRequest(DownloadRequest $downloadRequest): DownloadRequest {
        dump('DownloadService->processDownloadRequest called');
        // create and save a new instance of Download in the database
        $newDownload = new Download();
        $newDownload->setId(Uuid::v7());
        $newDownload->setLink($downloadRequest->link);
        $newDownload->setFormat($downloadRequest->format);
        $newDownload->setQuality($downloadRequest->quality);
        $newDownload->setState(DownloadState::Waiting);
        $newDownload->setCreatedAt(new DateTimeImmutable());
        $this->em->persist($newDownload);
        $this->em->flush();
        // update the $downloadRequest
        $downloadRequest->id = $newDownload->getId()->toString();
        $downloadRequest->state = $newDownload->getState();
        // dispatch the message to download the link
        $this->bus->dispatch(new ConvertVideoToAudioMessage($downloadRequest));
        return $downloadRequest;
    }

    public function handleConvertVideoToAudio(DownloadRequest $downloadRequest): void {
        dump('DownloadService->handleConvertVideoToAudio called');
        $topic = "{$this->defaultUri}/downloads/{$downloadRequest->id}";
        // retrieve the download from the database
        /** @var Download */
        $download = $this->downloadRepository->find(Uuid::fromString($downloadRequest->id));
        dump('download from database :', $download);
        // set the state to "running"
        $download->setState(DownloadState::Running);
        $this->em->flush();
        // notify the start of the process with Mercure
        $downloadRequest->state = DownloadState::Running;
        $jsonContent = $this->serializer->serialize($downloadRequest, 'json', ['groups' => ['download_request:get']]);
        dump('$jsonContent', $jsonContent);
        $this->hub->publish(new Update(
            topics: $topic,
            // type: 'stateUpdate',
            data: $jsonContent,
        ));
        // download the file with yt-dlp
        dump('processing download');
        $this->filesystem->mkdir("/app/public/storage/{$downloadRequest->id}");
        $downloadProcess = new Process(['yt-dlp', '-x', '-f', 'bestaudio', '--audio-format', $downloadRequest->format->value, '--audio-quality', $downloadRequest->quality->value,
            '-o', "/app/public/storage/{$downloadRequest->id}/%(title)s.%(ext)s", '--no-playlist', '--no-cache-dir', $downloadRequest->link]);
        $downloadProcess->setTimeout(300);
        $downloadProcess->run();
        if ($downloadProcess->isSuccessful()) {
            dump('success', $downloadProcess->getOutput());
            $downloadRequest->state = DownloadState::Succeeded;
            $download->setState(DownloadState::Succeeded);
            // retrieve the name of the generated file
            $lsProcess = new Process(['ls', "/app/public/storage/{$downloadRequest->id}"]);
            $lsProcess->mustRun();
            $downloadRequest->fileName = $lsProcess->getOutput();
            $download->setFileName($lsProcess->getOutput());
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
        $this->em->flush();
        dump('$downloadRequest', $downloadRequest);
        $jsonContent = $this->serializer->serialize($downloadRequest, 'json', ['groups' => ['download_request:get']]);
        dump('$jsonContent', $jsonContent);
        $this->hub->publish(new Update(
            topics: $topic,
            // type: 'stateUpdate',
            data: $jsonContent,
        ));
    }

    public function handleDeleteFile(string $id) {
        dump('handleDeleteFile called', $id);
        $this->filesystem->remove("/app/public/storage/{$id}");
    }
}
