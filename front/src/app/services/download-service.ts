import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { RequestFormModel } from '../models/request-form-model';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { RequestReply } from '../models/request-reply';

@Injectable({
    providedIn: 'root',
})
export class DownloadService {
    public constructor(
        protected readonly http: HttpClient,
    ) {}

    public postDownloadRequest(requestFormModel: RequestFormModel): Observable<RequestReply> {
        return this.http.post<RequestReply>(
            environment.apiUrl + '/api/download_requests',
            requestFormModel,
            {
                headers: {
                    'Content-Type': 'application/ld+json',
                    'accept': 'application/ld+json',
                },
            },
        );
    }

    public getHubEventSource(id: string): EventSource {
        const hub = new URL(environment.apiUrl + '/.well-known/mercure');
        hub.searchParams.append('topic', `${environment.apiUrl}/downloads/${id}`);
        return new EventSource(hub);
    }
}
