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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:test',
    description: 'Test stuff',
)]
class TestCommand {
    public function __construct(
        // protected readonly SerializerInterface $serializer,
        protected readonly Filesystem $filesystem,
    ) {}

    public function __invoke(SymfonyStyle $io) {
        // $downloadRequest = new DownloadRequest(
        //     link: 'http://example.com',
        //     format: DownloadFormat::Mp3,
        //     quality: DownloadQuality::Best,
        //     id: 'abc123',
        //     state: DownloadState::Running,
        //     fileName: 'song.mp3',
        //     error: 'error description',
        // );
        // $json = $this->serializer->serialize($downloadRequest, 'json', ['groups' => 'download_request:get']);
        // $io->writeln($json);

        // $this->filesystem->mkdir('/app/public/storage/testDir');
        // $this->filesystem->dumpFile('/app/public/storage/testDir/prout.txt', "haha prout\n");

        $process = new Process(['ls', '/public/storage/testDir']);
        $process->setTimeout(300);
        $process->run();
        $io->writeln('success: '.($process->isSuccessful() ? 'yes' : 'no'));
        if ($process->isSuccessful()) {
            $io->writeln('output:');
            $io->writeln($process->getOutput());
            return Command::SUCCESS;
        }
        else {
            $io->writeln('error:');
            $io->writeln($process->getErrorOutput());
            return Command::FAILURE;
        }
        // $process->
        return Command::SUCCESS;
    }
}
