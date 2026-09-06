import { Component, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Form } from '../pages/form/form';
import { environment } from '../../../environments/environment';

@Component({
    selector: 'app-main',
    imports: [RouterLink, Form],
    templateUrl: './main.html',
    styleUrl: './main.css',
})
export default class Main implements OnInit {
    protected readonly count = signal(0);

    public ngOnInit(): void {
        console.log('main init');
        console.log('current env:', environment.env);
    }

    protected increment() {
        console.log('incrementing counter');
        this.count.update((n) => n + 1);
    }

    protected reset() {
        console.log('resetting counter');
        this.count.set(0);
    }
}
