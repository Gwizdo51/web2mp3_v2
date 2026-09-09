export class RequestFormModel {
    public link: string = '';
    public format: DownloadFormat = 'mp3';
    public quality: DownloadQuality = 'best';

    public constructor(obj?: Partial<RequestFormModel>) {
        Object.assign(this, obj);
    }
}
