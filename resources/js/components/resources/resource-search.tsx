import { useForm } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Props = {
    action: string;
    initialValue: string;
    placeholder: string;
};

export function ResourceSearch({ action, initialValue, placeholder }: Props) {
    const form = useForm({ search: initialValue });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.get(action, { preserveState: true, replace: true });
    };

    const clear = () => {
        form.setData('search', '');
        window.setTimeout(() => {
            form.get(action, { preserveState: true, replace: true });
        }, 0);
    };

    return (
        <form onSubmit={submit} role="search" className="flex w-full gap-2">
            <div className="relative min-w-0 flex-1 sm:max-w-md">
                <Search
                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    aria-label="Buscar"
                    value={form.data.search}
                    onChange={(event) =>
                        form.setData('search', event.target.value)
                    }
                    placeholder={placeholder}
                    className="min-h-11 pr-10 pl-9"
                />
                {form.data.search !== '' && (
                    <button
                        type="button"
                        onClick={clear}
                        className="absolute top-1/2 right-1 flex size-9 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        aria-label="Limpar busca"
                    >
                        <X className="size-4" aria-hidden="true" />
                    </button>
                )}
            </div>
            <Button type="submit" variant="secondary" className="min-h-11">
                Buscar
            </Button>
        </form>
    );
}
