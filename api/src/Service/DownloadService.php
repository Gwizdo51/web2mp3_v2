<?php declare(strict_types=1);

namespace App\Service;

use App\ApiResource\DownloadRequest;
use App\Entity\Download;
use App\Enum\DownloadState;
use App\Message\ConvertVideoToAudioMessage;
use App\Repository\DownloadRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
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
        // dump($newDownload->getCreatedAt());
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
        $download = $this->downloadRepository->find(Uuid::fromString($downloadRequest->id));
        dump('download from database :', $download);
        // set the state to "running"
        $download->setState(DownloadState::Running);
        $this->em->flush();
        // notify the start of the process with Mercure
        $downloadRequest->state = DownloadState::Running;
        $downloadRequest->fileName = 'fileName.mp3';
        $jsonContent = $this->serializer->serialize($downloadRequest, 'json', ['groups' => ['download_request:get']]);
        dump('$jsonContent', $jsonContent);
        $this->hub->publish(new Update(
            topics: $topic,
            // type: 'stateUpdate',
            data: $jsonContent,
        ));
        // download the file with yt-dlp
        dump('processing download');
        // ...
        sleep(15);
        $downloadRequest->state = DownloadState::Succeeded;
        $downloadRequest->fileName = 'prout.mp3';
        dump('$downloadRequest', $downloadRequest);
        $jsonContent = $this->serializer->serialize($downloadRequest, 'json', ['groups' => ['download_request:get']]);
        dump('$jsonContent', $jsonContent);
        $this->hub->publish(new Update(
            topics: $topic,
            // type: 'stateUpdate',
            data: $jsonContent,
        ));
    }
}
