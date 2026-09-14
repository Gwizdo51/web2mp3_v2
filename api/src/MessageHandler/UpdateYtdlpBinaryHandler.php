<?php declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdateYtdlpBinary;
use App\Service\DownloadService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateYtdlpBinaryHandler {
    public function __construct(
        protected readonly DownloadService $downloadService,
    ) {}

    public function __invoke(UpdateYtdlpBinary $message): void {
        $this->downloadService->updateYtdlpBinary();
    }
}
