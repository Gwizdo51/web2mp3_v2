<?php declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\BroadcastQueueUpdateMessage;
use App\Service\DownloadService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class BroadcastQueueUpdateMessageHandler {
    public function __construct(
        protected readonly DownloadService $downloadService,
    ) {}

    public function __invoke(BroadcastQueueUpdateMessage $message): void {
        $this->downloadService->handleBroadcastQueueUpdate();
    }
}
