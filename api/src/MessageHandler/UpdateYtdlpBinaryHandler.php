<?php declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdateYtdlpBinary;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final class UpdateYtdlpBinaryHandler {
    public function __construct(
        protected readonly LoggerInterface $logger,
    ) {}

    public function __invoke(UpdateYtdlpBinary $message): void {
        $this->logger->info('UpdateYtdlpBinaryHandler->__invoke - Updating yt-dlp');
        $updateProcess = new Process(['yt-dlp', '-U']);
        $updateProcess->setTimeout(300);
        $updateProcess->run();
        if ($updateProcess->isSuccessful()) {
            $this->logger->info('UpdateYtdlpBinaryHandler->__invoke - Successfully updated yt-dlp - Output:');
            $this->logger->info(trim($updateProcess->getOutput()));
        }
        else {
            $this->logger->info('UpdateYtdlpBinaryHandler->__invoke - Error while updating yt-dlp - Output:');
            $this->logger->info(trim($updateProcess->getOutput()));
            $this->logger->info('UpdateYtdlpBinaryHandler->__invoke - Error output:');
            $this->logger->info(trim($updateProcess->getErrorOutput()));
        }
    }
}
