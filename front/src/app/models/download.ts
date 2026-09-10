export class Download {
    public id: string|null = null;
    public state: DownloadState|null = null;
    public fileName: string|null = null;
    public error: string|null = null;
    public queuePosition: number|null = null;

    public constructor(obj?: Partial<Download>) {
        if (obj) {
            Object.assign(this, obj);
        }
    }
}
