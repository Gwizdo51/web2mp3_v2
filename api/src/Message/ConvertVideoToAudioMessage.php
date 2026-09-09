<?php declare(strict_types=1);

namespace App\Message;

use App\ApiResource\DownloadRequest;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final class ConvertVideoToAudioMessage {
    public function __construct(
        public DownloadRequest $downloadRequest,
    ) {}
}
