<?php declare(strict_types=1);

namespace App\Enum;

enum DownloadFormat: string {
    case Mp3 = 'mp3';
    case M4a = 'm4a';
    case Flac = 'flac';
    case Wav = 'wav';
    case Aac = 'aac';
    case Alac = 'alac';
    case Opus = 'opus';
    case Vorbis = 'vorbis';
}
