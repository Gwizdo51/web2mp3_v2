import { Component, inject, model, OnDestroy, OnInit, signal } from '@angular/core';
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

    public ngOnInit(): void {
        console.log('Processing.ngOnInit called');
        this.eventSource = this.downloadService.getHubEventSource(<string>this.download()?.id);
        this.eventSource.onmessage = (event) => {
            console.log('message received', event);
        };
    }

    public ngOnDestroy(): void {
        console.log('Processing.ngOnDestroy called');
    }
}
