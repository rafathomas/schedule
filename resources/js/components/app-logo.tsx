import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name, company } = usePage().props;
    const displayName = company?.name ?? name;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-primary/10 text-primary">
                {company?.logo_url ? (
                    <img
                        src={company.logo_url}
                        alt={`Logo de ${displayName}`}
                        className="size-full object-contain p-0.5"
                    />
                ) : (
                    <AppLogoIcon className="size-6" />
                )}
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {displayName}
                </span>
            </div>
        </>
    );
}
