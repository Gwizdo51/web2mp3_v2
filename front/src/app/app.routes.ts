import { Routes } from '@angular/router';

export const routes: Routes = [
    {
        path: '',
        loadComponent: () => import('./components/main/main'),
        // title: 'Web2Mp3 - v2',
    },
    { path: '**', redirectTo: '/' },
];
