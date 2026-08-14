import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

type Props = {
    icon: LucideIcon;
    eyebrow?: string;
    title: string;
    description: string;
    action?: ReactNode;
};

export function ResourcePageHeader({
    icon: Icon,
    eyebrow = 'Cadastros',
    title,
    description,
    action,
}: Props) {
    return (
        <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div className="flex items-start gap-3">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                    <Icon className="size-5" aria-hidden="true" />
                </span>
                <div>
                    <p className="text-sm font-medium text-blue-700 dark:text-blue-300">
                        {eyebrow}
                    </p>
                    <h1 className="mt-0.5 text-2xl font-semibold tracking-tight sm:text-3xl">
                        {title}
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">
                        {description}
                    </p>
                </div>
            </div>
            {action}
        </header>
    );
}
