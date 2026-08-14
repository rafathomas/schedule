import type { Auth, Company } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            company: Company | null;
            permissions: {
                branding: boolean;
                reports: boolean;
            };
            flash: {
                success?: string;
                error?: string;
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
