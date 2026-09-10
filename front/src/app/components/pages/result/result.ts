import { AfterViewInit, Component, ElementRef, inject, model, OnInit, output, signal, viewChild } from '@angular/core';
import { Download } from '../../../models/download';
import { LucideCheck, LucideDownload, LucideX } from '@lucide/angular';
import { HlmPopoverImports } from '@spartan-ng/helm/popover';
import { DownloadService } from '../../../services/download-service';
import { RouterLink } from '@angular/router';

@Component({
    selector: 'app-result',
    imports: [RouterLink, LucideCheck, LucideX, LucideDownload, HlmPopoverImports],
    templateUrl: './result.html',
    styleUrl: './result.css',
})
export class Result implements OnInit, AfterViewInit {
    public readonly download = model.required<Download|null>();
    protected readonly downloadService = inject(DownloadService);
    protected readonly fileUrl = signal('');
    protected readonly downloadLink = viewChild<ElementRef<HTMLAnchorElement>>('downloadLink');
    public readonly reset = output<void>();

    public ngOnInit(): void {
        this.fileUrl.set(this.downloadService.getFileUrl(<Download>this.download()));
        // console.dir(this.downloadLink()?.nativeElement);
    }

    public ngAfterViewInit(): void {
        if (this.download()?.state === 'succeeded') {
            this.onDownloadLinkClick();
        }
    }

    protected onDownloadLinkClick() {
        console.log('download link clicked');
        this.downloadLink()?.nativeElement.click();
    }

    protected onReset() {
        this.reset.emit();
    }
}
