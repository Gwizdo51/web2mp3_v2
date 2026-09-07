import { Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { Form } from '../pages/form/form';
import { environment } from '../../../environments/environment';
import { HttpClient } from '@angular/common/http';

@Component({
    selector: 'app-main',
    imports: [RouterLink, Form],
    templateUrl: './main.html',
    styleUrl: './main.css',
})
export default class Main implements OnInit {
    private http = inject(HttpClient);

    protected readonly count = signal(0);
    private eventSource: EventSource|null = null;

    public ngOnInit(): void {
        console.log('main init');
        console.log('current env:', environment.env);
        console.log(environment.apiUrl + '/.well-known/mercure');
        const hub = new URL(environment.apiUrl + '/.well-known/mercure');
        hub.searchParams.append('topic', 'https://api.web2mp3-v2.100.124.238.99.nip.io/downloads/123abc');
        console.log(hub);
        this.eventSource = new EventSource(hub);
        this.eventSource.onmessage = event => {
            console.log('message received');
            console.log(event);
            console.log('data', JSON.parse(event.data));
        };
    }

    protected increment() {
        console.log('incrementing counter');
        this.count.update((n) => n + 1);
    }

    protected reset() {
        console.log('resetting counter');
        this.count.set(0);
    }

    protected checkApiConnection() {
        console.log('checking connection with API ...');
        this.http.get(
            // 'https://api.web2mp3-v2.100.124.238.99.nip.io/api/test',
            environment.apiUrl + '/api/test',
            {
                headers: {
                    'Accept': 'application/ld+json',
                },
            },
        ).subscribe({
            next: (value) => {console.log('response', value)},
            error: console.error,
        });
    }
}
