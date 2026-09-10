<?php declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\DeleteFileMessage;
use App\Service\DownloadService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteFileMessageHandler {
    public function __construct(
        protected readonly DownloadService $downloadService,
    ) {}

    public function __invoke(DeleteFileMessage $message): void {
        $this->downloadService->handleDeleteFile($message->id);
    }
}
