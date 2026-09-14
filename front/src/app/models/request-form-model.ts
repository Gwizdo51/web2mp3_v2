import { DownloadFormat, DownloadQuality, isDownloadFormat, isDownloadQuality } from "../types";

export class RequestFormModel {
    public link: string = '';
    public format: DownloadFormat;
    public quality: DownloadQuality;

    public constructor() {
        const formatFromLocalStorage = globalThis.localStorage.getItem('format');
        this.format = isDownloadFormat(formatFromLocalStorage) ? formatFromLocalStorage : 'mp3';
        const qualityFromLocalStorage = globalThis.localStorage.getItem('quality');
        this.quality = isDownloadQuality(qualityFromLocalStorage) ? qualityFromLocalStorage : 'best';
    }
}
