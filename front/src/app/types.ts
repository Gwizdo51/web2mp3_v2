export type DownloadFormat = 'mp3' | 'm4a' | 'flac' | 'wav' | 'aac' | 'alac' | 'opus' | 'vorbis';

export function getAllDownloadFormats(): DownloadFormat[] {
    return ['mp3', 'm4a', 'flac', 'wav', 'aac', 'alac', 'opus', 'vorbis'];
}

export function isDownloadFormat(obj: any): obj is DownloadFormat {
    return getAllDownloadFormats().includes(obj);
}


export type DownloadQuality = 'best' | 'good' | 'average' | 'poor';

export function getAllDownloadQualities(): DownloadQuality[] {
    return ['best', 'good', 'average', 'poor'];
}

export function isDownloadQuality(obj: any): obj is DownloadQuality {
    return getAllDownloadQualities().includes(obj);
}


export type DownloadState = 'waiting' | 'running' | 'failed' | 'succeeded';

export function getAllDownloadStates(): DownloadState[] {
    return ['waiting', 'running', 'failed', 'succeeded'];
}

export function isDownloadState(obj: any): obj is DownloadState {
    return getAllDownloadStates().includes(obj);
}
