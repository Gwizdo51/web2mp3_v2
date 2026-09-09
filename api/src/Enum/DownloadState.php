<?php declare(strict_types=1);

namespace App\Enum;

enum DownloadState: string {
    case Waiting = 'waiting';
    case Running = 'running';
    case Failed = 'failed';
    case Succeeded = 'succeeded';
}
