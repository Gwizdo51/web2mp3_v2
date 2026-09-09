<?php declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ConvertVideoToAudioMessage;
use App\Service\DownloadService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ConvertVideoToAudioMessageHandler {
    public function __construct(
        protected readonly DownloadService $downloadService,
    ) {}

    public function __invoke(ConvertVideoToAudioMessage $message): void {
        $this->downloadService->handleConvertVideoToAudio($message->downloadRequest);
    }
}
