import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import { useBrandTheme } from '@/hooks/use-brand-theme';
import { brandStyle } from '@/lib/brand';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const { sidebarOpen: isOpen, company } = usePage().props;
    useBrandTheme(company);

    if (variant === 'header') {
        return (
            <div
                className="brand-theme flex min-h-screen w-full flex-col"
                style={brandStyle(company?.primary_color)}
            >
                {children}
            </div>
        );
    }

    return (
        <div
            className="brand-theme contents"
            style={brandStyle(company?.primary_color)}
        >
            <SidebarProvider defaultOpen={isOpen}>{children}</SidebarProvider>
        </div>
    );
}
