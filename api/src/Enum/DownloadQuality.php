<?php declare(strict_types=1);

namespace App\Enum;

enum DownloadQuality: string {
    case Best = 'best';
    case Good = 'good';
    case Average = 'average';
    case Poor = 'poor';
}
