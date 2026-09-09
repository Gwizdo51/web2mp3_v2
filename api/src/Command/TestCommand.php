<?php declare(strict_types=1);

namespace App\Command;

use App\ApiResource\DownloadRequest;
use App\Enum\DownloadFormat;
use App\Enum\DownloadQuality;
use App\Enum\DownloadState;
use App\Repository\DownloadRepository;
use App\Service\DownloadService;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:test',
    description: 'Test stuff',
)]
class TestCommand {
    public function __construct(
        protected readonly SerializerInterface $serializer,
    ) {}

    public function __invoke(SymfonyStyle $io) {
        $downloadRequest = new DownloadRequest(
            link: 'http://example.com',
            format: DownloadFormat::Mp3,
            quality: DownloadQuality::Best,
            id: 'abc123',
            state: DownloadState::Running,
            fileName: 'song.mp3',
            error: 'error description',
        );
        $json = $this->serializer->serialize($downloadRequest, 'json', ['groups' => 'download_request:get']);
        $io->writeln($json);
        return Command::SUCCESS;
    }
}
