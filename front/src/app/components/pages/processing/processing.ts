import { Component, effect, inject, model, OnDestroy, OnInit, signal } from '@angular/core';
import { LucideLoaderCircle } from '@lucide/angular';
import { DownloadService } from '../../../services/download-service';
import { Download } from '../../../models/download';

@Component({
    selector: 'app-processing',
    imports: [LucideLoaderCircle],
    templateUrl: './processing.html',
    styleUrl: './processing.css',
})
export class Processing implements OnInit, OnDestroy {
    public readonly download = model.required<Download|null>();
    protected readonly displayedText = signal('In Queue ...');
    protected eventSource: EventSource|null = null;
    protected readonly downloadService = inject(DownloadService);

    public constructor() {
        effect(() => {
            const download = <Download>this.download();
            if (download.state === 'waiting') {
                this.displayedText.set(`In queue (position: ${download.queuePosition}) ...`);
            }
            else {
                this.displayedText.set('Processing ...');
            }
        });
    }

    public ngOnInit(): void {
        this.eventSource = this.downloadService.getHubEventSource(<string>this.download()?.id);
        this.eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            console.log('message received', data);
            this.download.update((download) => download ? {...download, ...data} : null);
        };
    }

    public ngOnDestroy(): void {
        this.eventSource?.close();
        this.eventSource = null;
    }
}
