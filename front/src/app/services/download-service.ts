import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { RequestFormModel } from '../models/request-form-model';
import { map, Observable } from 'rxjs';
import { environment } from '../../environments/environment';
import { Download } from '../models/download';

@Injectable({
    providedIn: 'root',
})
export class DownloadService {
    public constructor(
        protected readonly http: HttpClient,
    ) {}

    public postDownloadRequest(requestFormModel: RequestFormModel): Observable<Download> {
        return this.http.post<Partial<Download>>(
            environment.apiUrl + '/api/download_requests',
            requestFormModel,
            {
                headers: {
                    'Content-Type': 'application/ld+json',
                    'accept': 'application/ld+json',
                },
            },
        ).pipe(map((downloadResponse) => new Download(downloadResponse)));
    }

    public getHubEventSource(id: string): EventSource {
        const hub = new URL(`${environment.apiUrl}/.well-known/mercure`);
        hub.searchParams.append('topic', `${environment.apiUrl}/downloads/${id}`);
        return new EventSource(hub);
    }

    public getFileUrl(download: Download) {
        return `${environment.apiUrl}/storage/${download.id}/${download.fileName}`;
    }
}
