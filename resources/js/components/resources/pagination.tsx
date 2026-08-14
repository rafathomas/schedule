import { Link } from '@inertiajs/react';
import type { Paginated } from '@/types';

export function Pagination({ page }: { page: Paginated<unknown> }) {
    if (page.last_page <= 1) {
        return null;
    }

    const label = (value: string) => {
        if (value.includes('previous')) {
            return 'Anterior';
        }

        if (value.includes('next')) {
            return 'Próxima';
        }

        return value.replaceAll('&laquo;', '').replaceAll('&raquo;', '').trim();
    };

    return (
        <nav
            className="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between"
            aria-label="Paginação"
        >
            <p className="text-sm text-muted-foreground">
                Exibindo {page.from}–{page.to} de {page.total}
            </p>
            <div className="flex flex-wrap gap-1">
                {page.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url}
                            preserveScroll
                            className={`flex min-h-11 min-w-11 items-center justify-center rounded-md border px-3 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none ${
                                link.active
                                    ? 'border-blue-700 bg-blue-700 text-white'
                                    : 'bg-background hover:bg-muted'
                            }`}
                        >
                            {label(link.label)}
                        </Link>
                    ) : (
                        <span
                            key={`${link.label}-${index}`}
                            className="flex min-h-11 min-w-11 items-center justify-center rounded-md border px-3 text-sm text-muted-foreground opacity-50"
                        >
                            {label(link.label)}
                        </span>
                    ),
                )}
            </div>
        </nav>
    );
}
