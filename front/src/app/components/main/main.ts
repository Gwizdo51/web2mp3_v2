import { Component, effect, inject, signal, viewChild } from '@angular/core';
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
    //     error: 'Lorem ipsum dolor, sit amet consectetur adipisicing elit. Consequuntur dolores itaque dolore illum a magni assumenda, '
    //         + 'voluptas adipisci enim alias est rerum molestiae nisi sint tempora hic unde inventore? Placeat.\nLorem, ipsum dolor sit amet consectetur '
    //         + 'adipisicing elit. Placeat dolore, ut deserunt sequi magni recusandae! Libero, alias voluptatem? Ex autem, voluptas pariatur tenetur '
    //         + 'excepturi laudantium reiciendis ad repellendus laborum ipsum.\nLorem ipsum dolor, sit amet consectetur adipisicing elit. Consequuntur '
    //         + 'dolores itaque dolore illum a magni assumenda, voluptas adipisci enim alias est rerum molestiae nisi sint tempora hic unde inventore? Placeat.\n'
    //         + 'Lorem, ipsum dolor sit amet consectetur adipisicing elit. Placeat dolore, ut deserunt sequi magni recusandae! Libero, alias voluptatem? Ex autem, '
    //         + 'voluptas pariatur tenetur excepturi laudantium reiciendis ad repellendus laborum ipsum. '
    //         + 'LoremipsumdolorsitametconsecteturadipisicingelitPlaceatdoloreutdeseruntsequimagnirecusandaeLiberoaliasvoluptatemExautemLoremipsumdolorsitametconsecteturadipisicingelitPlaceatdoloreutdeseruntsequimagnirecusandaeLiberoaliasvoluptatemExautem',
    //     queuePosition: 0,
    // });
    protected readonly formComponent = viewChild(Form);
    protected readonly titleService = inject(Title);

    public constructor() {
        effect(() => {
            const download = this.download();
            // console.log('update to "download" detected:', JSON.stringify(download));
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
