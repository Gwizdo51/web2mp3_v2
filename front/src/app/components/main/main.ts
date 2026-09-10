import { Component, effect, signal, viewChild } from '@angular/core';
import { Form } from '../pages/form/form';
import { PageTitle } from '../../enum/page-title';
import { Processing } from '../pages/processing/processing';
import { Result } from '../pages/result/result';
import { Title } from '@angular/platform-browser';
import { Download } from '../../models/download';
import { RouterLink } from '@angular/router';

@Component({
    selector: 'app-main',
    imports: [RouterLink, Form, Processing, Result],
    templateUrl: './main.html',
    styleUrl: './main.css',
})
export default class Main {
    protected readonly currentPage = signal(PageTitle.Landing);
    protected readonly pageTitleEnum = PageTitle;
    public readonly download = signal<Download|null>(null);
    // public readonly download = signal<Download|null>({
    //     id: '01a08890-b274-7b87-a894-0a500f5bb1f2',
    //     state: 'failed',
    //     fileName: 'Yee.mp3',
    //     error: 'error description',
    // });
    protected readonly formComponent = viewChild(Form);

    public constructor(
        protected readonly titleService: Title,
    ) {
        effect(() => {
            const download = this.download();
            if (download == null) {
                this.currentPage.set(PageTitle.Landing);
            }
            else if (download.state === 'waiting' || download.state === 'running') {
                this.currentPage.set(PageTitle.Processing);
            }
            else {
                this.currentPage.set(PageTitle.Result);
            }
        });
        effect(() => {
            this.titleService.setTitle(PageTitle[this.currentPage()]);
        });
    }

    protected reset() {
        this.download.set(null);
        this.formComponent()?.reset();
    }
}
