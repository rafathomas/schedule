import { usePage } from '@inertiajs/react';
import { useBrandTheme } from '@/hooks/use-brand-theme';
import AuthLayoutTemplate from '@/layouts/auth/auth-split-layout';
import { brandStyle } from '@/lib/brand';

export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    const { company } = usePage().props;
    const brand = useBrandTheme(company, true);

    return (
        <div
            className="brand-theme contents"
            style={brandStyle(brand?.primary_color)}
        >
            <AuthLayoutTemplate title={title} description={description}>
                {children}
            </AuthLayoutTemplate>
        </div>
    );
}
