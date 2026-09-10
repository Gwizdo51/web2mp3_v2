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
    // private http = inject(HttpClient);

    // protected readonly count = signal(0);
    // private eventSource: EventSource|null = null;

    // protected readonly titleService = inject(Title);

    protected readonly currentPage = signal(PageTitle.Landing);
    protected readonly pageTitleEnum = PageTitle;
    // protected readonly requestFormModel = signal(new RequestFormModel());
    // public download: Download|null = null;
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

    // public ngOnInit(): void {
    //     console.log('main init');
    //     console.log('current env:', environment.env);
    //     // console.log(environment.apiUrl + '/.well-known/mercure');
    //     // const hub = new URL(environment.apiUrl + '/.well-known/mercure');
    //     // hub.searchParams.append('topic', 'https://api.web2mp3-v2.100.124.238.99.nip.io/downloads/123abc');
    //     // console.log(hub);
    //     // this.eventSource = new EventSource(hub);
    //     // this.eventSource.onmessage = event => {
    //     //     console.log('message received');
    //     //     console.log(event);
    //     //     console.log('data', JSON.parse(event.data));
    //     // };
    //     // console.log(PageTitle[PageTitle.Landing]);
    //     // console.log(PageTitle.Landing);
    // }

    // protected increment() {
    //     console.log('incrementing counter');
    //     this.count.update((n) => n + 1);
    // }

    // protected reset() {
    //     console.log('resetting counter');
    //     this.count.set(0);
    // }

    // protected checkApiConnection() {
    //     console.log('checking connection with API ...');
    //     this.http.get(
    //         // 'https://api.web2mp3-v2.100.124.238.99.nip.io/api/test',
    //         environment.apiUrl + '/api/test',
    //         {
    //             headers: {
    //                 'accept': 'application/ld+json',
    //             },
    //         },
    //     ).subscribe({
    //         next: (value) => {console.log('response', value)},
    //         error: console.error,
    //     });
    // }

    protected reset() {
        this.download.set(null);
        this.formComponent()?.reset();
    }
}
