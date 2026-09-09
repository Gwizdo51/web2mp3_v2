import { Component, inject, output, signal } from '@angular/core';
import { form, FormField, FormRoot, pattern, required } from '@angular/forms/signals';
import { RequestFormModel } from '../../../models/request-form-model';
import { LucideCircleAlert, LucideLoaderCircle, } from '@lucide/angular';
import { DownloadService } from '../../../services/download-service';
import { RequestReply } from '../../../models/request-reply';
import { firstValueFrom } from 'rxjs';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
    selector: 'app-form',
    imports: [FormField, FormRoot, LucideCircleAlert, LucideLoaderCircle],
    templateUrl: './form.html',
    styleUrl: './form.css',
})
export class Form {
    // public readonly requestModel = model.required<RequestFormModel>();
    protected readonly requestModel = signal(new RequestFormModel());
    protected readonly formats: DownloadFormat[] = ['mp3', 'm4a', 'flac', 'wav', 'aac', 'alac', 'opus', 'vorbis'];
    protected readonly qualities: DownloadQuality[] = ['best', 'good', 'average', 'poor'];
    protected readonly requestAccepted = output<RequestReply>();
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
                    // console.log('valid form submitted', field(), field().value(), detail);
                    try {
                        const response = await firstValueFrom(this.downloadService.postDownloadRequest(field().value()));
                        console.log('API response:', response);
                        this.requestAccepted.emit(response);
                        return;
                    }
                    catch (err: any) {
                        // console.log('error caught');
                        // console.log(err);
                        if (err instanceof HttpErrorResponse && err.status === 422) {
                            // console.log('validation error');
                            // console.log(err.error.violations);
                            const linkViolations: any[] = [];
                            err.error.violations.forEach((violation: any) => {
                                // console.log(violation);
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
                    // console.log('invalid form submitted', field(), field().value(), detail);
                    this.modelForm.link().markAsDirty();
                },
            },
        },
    );
}
