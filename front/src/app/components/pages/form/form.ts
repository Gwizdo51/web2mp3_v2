import { Component, inject, model, signal } from '@angular/core';
import { form, FormField, FormRoot, pattern, required } from '@angular/forms/signals';
import { RequestFormModel } from '../../../models/request-form-model';
import { LucideCircleAlert, LucideLoaderCircle, } from '@lucide/angular';
import { DownloadService } from '../../../services/download-service';
import { firstValueFrom } from 'rxjs';
import { HttpErrorResponse } from '@angular/common/http';
import { Download } from '../../../models/download';
import { getAllDownloadFormats, getAllDownloadQualities } from '../../../types';

@Component({
    selector: 'app-form',
    imports: [FormField, FormRoot, LucideCircleAlert, LucideLoaderCircle],
    templateUrl: './form.html',
    styleUrl: './form.css',
})
export class Form {
    public readonly download = model.required<Download|null>();
    protected readonly requestModel = signal(new RequestFormModel());
    protected readonly formats = getAllDownloadFormats();
    protected readonly qualities = getAllDownloadQualities();
    protected readonly downloadService = inject(DownloadService);
    protected readonly modelForm = form(
        this.requestModel,
        (schemaPath) => {
            required(schemaPath.link, {message: 'The download link is required'});
            pattern(
                schemaPath.link,
                /^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()!@:%_\+.~#?&\/\/=]*)$/,
                {message: 'The download link must be a valid URL'},
            );
        },
        {
            submission: {
                action: async (field) => {
                    try {
                        // send the request to the API and await its response
                        const response = await firstValueFrom(this.downloadService.postDownloadRequest(field().value()));
                        console.log('API response:', response);
                        globalThis.localStorage.setItem('format', field().value().format);
                        globalThis.localStorage.setItem('quality', field().value().quality);
                        this.download.set(response);
                        return;
                    }
                    catch (err: any) {
                        if (err instanceof HttpErrorResponse && err.status === 422) {
                            const linkViolations: any[] = [];
                            err.error.violations.forEach((violation: any) => {
                                if (violation.propertyPath === 'link') {
                                    linkViolations.push({
                                        kind: 'validationError',
                                        message: violation.message,
                                        fieldTree: field.link,
                                    });
                                }
                            });
                            return linkViolations;
                        }
                        else {
                            console.error('unkown server error', err);
                            return {
                                kind: 'serverError',
                                message: 'Unknown error when submitting form',
                            };
                        }
                    }
                },
                onInvalid: () => {
                    this.modelForm.link().markAsDirty();
                },
            },
        },
    );

    public reset() {
        this.requestModel.set(new RequestFormModel());
        this.modelForm().reset();
    }
}
