export class RequestFormModel {
    public link: string = '';
    public format: DownloadFormat;
    public quality: DownloadQuality;

    public constructor() {
        this.format = <DownloadFormat>globalThis.localStorage.getItem('format') ?? 'mp3';
        this.quality = <DownloadQuality>globalThis.localStorage.getItem('quality') ?? 'best';
    }
}
