import { Head, useForm } from '@inertiajs/react';
import { Clock3, Pencil, Plus, Scissors, TimerReset } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DeleteResourceDialog } from '@/components/resources/delete-resource-dialog';
import { EmptyState } from '@/components/resources/empty-state';
import { FormField } from '@/components/resources/form-field';
import { Pagination } from '@/components/resources/pagination';
import { ResourcePageHeader } from '@/components/resources/resource-page-header';
import { ResourceSearch } from '@/components/resources/resource-search';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { Paginated, Service } from '@/types';

type ServiceForm = {
    name: string;
    description: string;
    category: string;
    duration_minutes: string;
    buffer_minutes: string;
    price: string;
    is_active: boolean;
};

const emptyForm: ServiceForm = {
    name: '',
    description: '',
    category: '',
    duration_minutes: '60',
    buffer_minutes: '0',
    price: '',
    is_active: true,
};

const currency = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

export default function ServicesIndex({
    services,
    filters,
    canManage,
}: {
    services: Paginated<Service>;
    filters: { search: string };
    canManage: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Service | null>(null);
    const form = useForm<ServiceForm>(emptyForm);

    const create = () => {
        setEditing(null);
        form.setData(emptyForm);
        form.clearErrors();
        setOpen(true);
    };

    const edit = (service: Service) => {
        setEditing(service);
        form.setData({
            name: service.name,
            description: service.description ?? '',
            category: service.category ?? '',
            duration_minutes: String(service.duration_minutes),
            buffer_minutes: String(service.buffer_minutes),
            price: service.price,
            is_active: service.is_active,
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            form.patch(`/servicos/${editing.uuid}`, options);
        } else {
            form.post('/servicos', options);
        }
    };

    const action = canManage ? (
        <Button onClick={create} className="min-h-11 w-full sm:w-auto">
            <Plus className="size-4" aria-hidden="true" />
            Novo serviço
        </Button>
    ) : undefined;

    return (
        <>
            <Head title="Serviços" />
            <main
                id="main-content"
                className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
            >
                <ResourcePageHeader
                    icon={Scissors}
                    title="Serviços"
                    description="Defina duração, preço e intervalo técnico de cada atendimento."
                    action={action}
                />
                <ResourceSearch
                    action="/servicos"
                    initialValue={filters.search}
                    placeholder="Buscar por serviço ou categoria"
                />

                {services.data.length === 0 ? (
                    <EmptyState
                        icon={Scissors}
                        title={
                            filters.search
                                ? 'Nenhum serviço encontrado'
                                : 'Monte seu catálogo de serviços'
                        }
                        description={
                            filters.search
                                ? 'Tente outro nome ou categoria para encontrar o serviço.'
                                : 'Cadastre o primeiro serviço para relacioná-lo aos profissionais.'
                        }
                        action={action}
                    />
                ) : (
                    <section
                        aria-label="Lista de serviços"
                        className="grid gap-4 md:grid-cols-2 2xl:grid-cols-3"
                    >
                        {services.data.map((service) => (
                            <Card
                                key={service.uuid}
                                className="gap-4 py-5 shadow-none transition-colors hover:border-blue-200 dark:hover:border-blue-900"
                            >
                                <CardContent className="px-5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h2 className="font-semibold">
                                                    {service.name}
                                                </h2>
                                                <Badge
                                                    variant={
                                                        service.is_active
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {service.is_active
                                                        ? 'Ativo'
                                                        : 'Inativo'}
                                                </Badge>
                                            </div>
                                            {service.category && (
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {service.category}
                                                </p>
                                            )}
                                        </div>
                                        {canManage && (
                                            <div className="flex shrink-0">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="min-h-11 min-w-11"
                                                    onClick={() =>
                                                        edit(service)
                                                    }
                                                    aria-label={`Editar ${service.name}`}
                                                >
                                                    <Pencil
                                                        className="size-4"
                                                        aria-hidden="true"
                                                    />
                                                </Button>
                                                <DeleteResourceDialog
                                                    compact
                                                    title={service.name}
                                                    description="O serviço será retirado do catálogo e dos profissionais relacionados."
                                                    url={`/servicos/${service.uuid}`}
                                                />
                                            </div>
                                        )}
                                    </div>
                                    {service.description && (
                                        <p className="mt-3 line-clamp-2 text-sm leading-6 text-muted-foreground">
                                            {service.description}
                                        </p>
                                    )}
                                    <dl className="mt-4 grid grid-cols-3 gap-2 border-t pt-4 text-sm">
                                        <div>
                                            <dt className="flex items-center gap-1 text-xs text-muted-foreground">
                                                <Clock3
                                                    className="size-3.5"
                                                    aria-hidden="true"
                                                />
                                                Duração
                                            </dt>
                                            <dd className="mt-1 font-medium tabular-nums">
                                                {service.duration_minutes} min
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="flex items-center gap-1 text-xs text-muted-foreground">
                                                <TimerReset
                                                    className="size-3.5"
                                                    aria-hidden="true"
                                                />
                                                Intervalo
                                            </dt>
                                            <dd className="mt-1 font-medium tabular-nums">
                                                {service.buffer_minutes} min
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-xs text-muted-foreground">
                                                Preço
                                            </dt>
                                            <dd className="mt-1 font-medium tabular-nums">
                                                {currency.format(
                                                    Number(service.price),
                                                )}
                                            </dd>
                                        </div>
                                    </dl>
                                </CardContent>
                            </Card>
                        ))}
                    </section>
                )}
                <Pagination page={services} />
            </main>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Editar serviço' : 'Novo serviço'}
                        </DialogTitle>
                        <DialogDescription>
                            Campos com * são obrigatórios. Valores podem ser
                            alterados antes de criar a agenda.
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        id="service-form"
                        onSubmit={submit}
                        className="grid gap-5"
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                htmlFor="service-name"
                                label="Nome"
                                required
                                error={form.errors.name}
                            >
                                <Input
                                    id="service-name"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    className="min-h-11"
                                    autoFocus
                                    aria-invalid={Boolean(form.errors.name)}
                                />
                            </FormField>
                            <FormField
                                htmlFor="service-category"
                                label="Categoria"
                                error={form.errors.category}
                            >
                                <Input
                                    id="service-category"
                                    value={form.data.category}
                                    onChange={(event) =>
                                        form.setData(
                                            'category',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-11"
                                    placeholder="Ex.: Cabelo"
                                    aria-invalid={Boolean(form.errors.category)}
                                />
                            </FormField>
                        </div>
                        <FormField
                            htmlFor="service-description"
                            label="Descrição"
                            error={form.errors.description}
                        >
                            <textarea
                                id="service-description"
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                            />
                        </FormField>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <FormField
                                htmlFor="service-duration"
                                label="Duração (min)"
                                required
                                error={form.errors.duration_minutes}
                            >
                                <Input
                                    id="service-duration"
                                    type="number"
                                    min="5"
                                    max="1440"
                                    step="5"
                                    value={form.data.duration_minutes}
                                    onChange={(event) =>
                                        form.setData(
                                            'duration_minutes',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-11"
                                />
                            </FormField>
                            <FormField
                                htmlFor="service-buffer"
                                label="Intervalo (min)"
                                required
                                error={form.errors.buffer_minutes}
                            >
                                <Input
                                    id="service-buffer"
                                    type="number"
                                    min="0"
                                    max="240"
                                    step="5"
                                    value={form.data.buffer_minutes}
                                    onChange={(event) =>
                                        form.setData(
                                            'buffer_minutes',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-11"
                                />
                            </FormField>
                            <FormField
                                htmlFor="service-price"
                                label="Preço (R$)"
                                required
                                error={form.errors.price}
                            >
                                <Input
                                    id="service-price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    inputMode="decimal"
                                    value={form.data.price}
                                    onChange={(event) =>
                                        form.setData(
                                            'price',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-11"
                                />
                            </FormField>
                        </div>
                        <label className="flex min-h-11 items-center gap-3 rounded-lg border px-3 text-sm">
                            <Checkbox
                                checked={form.data.is_active}
                                onCheckedChange={(checked) =>
                                    form.setData('is_active', checked === true)
                                }
                            />
                            Serviço ativo e disponível para a equipe
                        </label>
                    </form>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            className="min-h-11"
                            onClick={() => setOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            form="service-form"
                            className="min-h-11"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {editing
                                ? 'Salvar alterações'
                                : 'Cadastrar serviço'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
