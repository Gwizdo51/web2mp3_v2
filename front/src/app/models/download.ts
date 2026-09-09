export interface Download {
    id: string;
    state: DownloadState;
    fileName: string|null;
    error: string|null;
}
