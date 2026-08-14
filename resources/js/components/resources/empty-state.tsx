import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

type Props = {
    icon: LucideIcon;
    title: string;
    description: string;
    action?: ReactNode;
};

export function EmptyState({ icon: Icon, title, description, action }: Props) {
    return (
        <div className="flex min-h-72 flex-col items-center justify-center rounded-xl border border-dashed bg-muted/20 px-5 py-10 text-center">
            <span className="flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                <Icon className="size-6" aria-hidden="true" />
            </span>
            <h2 className="mt-4 text-base font-semibold">{title}</h2>
            <p className="mt-1 max-w-sm text-sm leading-6 text-muted-foreground">
                {description}
            </p>
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
