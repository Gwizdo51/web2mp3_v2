import { Routes } from '@angular/router';

export const routes: Routes = [
    {
        path: '',
        loadComponent: () => import('./components/main/main'),
        title: 'Landing',
    },
    { path: '**', redirectTo: '/' },
];
