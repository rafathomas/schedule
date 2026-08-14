import { Head, useForm } from '@inertiajs/react';
import { Mail, Pencil, Phone, Plus, UserRound, UsersRound } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DeleteResourceDialog } from '@/components/resources/delete-resource-dialog';
import { EmptyState } from '@/components/resources/empty-state';
import { FormField } from '@/components/resources/form-field';
import { Pagination } from '@/components/resources/pagination';
import { ResourcePageHeader } from '@/components/resources/resource-page-header';
import { ResourceSearch } from '@/components/resources/resource-search';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { Paginated, Professional } from '@/types';

type ServiceOption = { uuid: string; name: string; is_active: boolean };
type ProfessionalForm = {
    name: string;
    email: string;
    phone: string;
    description: string;
    status: 'active' | 'inactive';
    avatar: File | null;
    remove_avatar: boolean;
    service_uuids: string[];
};

const emptyForm: ProfessionalForm = {
    name: '',
    email: '',
    phone: '',
    description: '',
    status: 'active',
    avatar: null,
    remove_avatar: false,
    service_uuids: [],
};

export default function ProfessionalsIndex({
    professionals,
    services,
    filters,
    canManage,
}: {
    professionals: Paginated<Professional>;
    services: ServiceOption[];
    filters: { search: string };
    canManage: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Professional | null>(null);
    const form = useForm<ProfessionalForm>(emptyForm);

    const create = () => {
        setEditing(null);
        form.setData(emptyForm);
        form.clearErrors();
        setOpen(true);
    };

    const edit = (professional: Professional) => {
        setEditing(professional);
        form.setData({
            name: professional.name,
            email: professional.email ?? '',
            phone: professional.phone ?? '',
            description: professional.description ?? '',
            status: professional.status,
            avatar: null,
            remove_avatar: false,
            service_uuids: professional.service_uuids,
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            form.transform((data) => ({ ...data, _method: 'patch' }));
            form.post(`/profissionais/${editing.uuid}`, options);

            return;
        }

        form.transform((data) => data);
        form.post('/profissionais', options);
    };

    const toggleService = (uuid: string, checked: boolean) => {
        form.setData(
            'service_uuids',
            checked
                ? [...form.data.service_uuids, uuid]
                : form.data.service_uuids.filter((item) => item !== uuid),
        );
    };

    const action = canManage ? (
        <Button onClick={create} className="min-h-11 w-full sm:w-auto">
            <Plus className="size-4" aria-hidden="true" />
            Novo profissional
        </Button>
    ) : undefined;

    return (
        <>
            <Head title="Profissionais" />
            <main
                id="main-content"
                className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
            >
                <ResourcePageHeader
                    icon={UsersRound}
                    title="Profissionais"
                    description="Organize a equipe, os contatos e os serviços atendidos por cada pessoa."
                    action={action}
                />

                <ResourceSearch
                    action="/profissionais"
                    initialValue={filters.search}
                    placeholder="Buscar por nome, telefone ou e-mail"
                />

                {professionals.data.length === 0 ? (
                    <EmptyState
                        icon={UserRound}
                        title={
                            filters.search
                                ? 'Nenhum profissional encontrado'
                                : 'Cadastre sua equipe'
                        }
                        description={
                            filters.search
                                ? 'Revise o termo buscado ou limpe o filtro para ver toda a equipe.'
                                : 'Adicione o primeiro profissional e relacione os serviços que ele atende.'
                        }
                        action={action}
                    />
                ) : (
                    <section
                        aria-label="Lista de profissionais"
                        className="grid gap-4 md:grid-cols-2 2xl:grid-cols-3"
                    >
                        {professionals.data.map((professional) => (
                            <Card
                                key={professional.uuid}
                                className="gap-4 py-5 shadow-none transition-colors hover:border-blue-200 dark:hover:border-blue-900"
                            >
                                <CardContent className="px-5">
                                    <div className="flex items-start gap-3">
                                        <Avatar className="size-11">
                                            {professional.avatar_url && (
                                                <AvatarImage
                                                    src={
                                                        professional.avatar_url
                                                    }
                                                    alt=""
                                                />
                                            )}
                                            <AvatarFallback>
                                                {professional.name
                                                    .slice(0, 2)
                                                    .toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-start justify-between gap-2">
                                                <div>
                                                    <h2 className="truncate font-semibold">
                                                        {professional.name}
                                                    </h2>
                                                    <Badge
                                                        className="mt-1"
                                                        variant={
                                                            professional.status ===
                                                            'active'
                                                                ? 'secondary'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {professional.status ===
                                                        'active'
                                                            ? 'Ativo'
                                                            : 'Inativo'}
                                                    </Badge>
                                                </div>
                                                {canManage && (
                                                    <div className="flex">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="min-h-11 min-w-11"
                                                            onClick={() =>
                                                                edit(
                                                                    professional,
                                                                )
                                                            }
                                                            aria-label={`Editar ${professional.name}`}
                                                        >
                                                            <Pencil
                                                                className="size-4"
                                                                aria-hidden="true"
                                                            />
                                                        </Button>
                                                        <DeleteResourceDialog
                                                            compact
                                                            title={
                                                                professional.name
                                                            }
                                                            description="O cadastro será removido da equipe. Os dados não poderão ser usados em novos agendamentos."
                                                            url={`/profissionais/${professional.uuid}`}
                                                        />
                                                    </div>
                                                )}
                                            </div>
                                            <div className="mt-3 space-y-1.5 text-sm text-muted-foreground">
                                                {professional.phone && (
                                                    <p className="flex items-center gap-2">
                                                        <Phone
                                                            className="size-4"
                                                            aria-hidden="true"
                                                        />
                                                        {professional.phone}
                                                    </p>
                                                )}
                                                {professional.email && (
                                                    <p className="flex min-w-0 items-center gap-2">
                                                        <Mail
                                                            className="size-4 shrink-0"
                                                            aria-hidden="true"
                                                        />
                                                        <span className="truncate">
                                                            {professional.email}
                                                        </span>
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <div className="mt-4 flex flex-wrap gap-1.5 border-t pt-4">
                                        {professional.services.length > 0 ? (
                                            professional.services.map(
                                                (service) => (
                                                    <Badge
                                                        key={service.uuid}
                                                        variant="outline"
                                                    >
                                                        {service.name}
                                                    </Badge>
                                                ),
                                            )
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                Nenhum serviço relacionado
                                            </span>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </section>
                )}

                <Pagination page={professionals} />
            </main>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing
                                ? 'Editar profissional'
                                : 'Novo profissional'}
                        </DialogTitle>
                        <DialogDescription>
                            Campos com * são obrigatórios. Você pode ajustar os
                            serviços a qualquer momento.
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        onSubmit={submit}
                        className="grid gap-5"
                        id="professional-form"
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                htmlFor="professional-name"
                                label="Nome"
                                required
                                error={form.errors.name}
                            >
                                <Input
                                    id="professional-name"
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
                                htmlFor="professional-status"
                                label="Status"
                                required
                                error={form.errors.status}
                            >
                                <Select
                                    value={form.data.status}
                                    onValueChange={(
                                        value: 'active' | 'inactive',
                                    ) => form.setData('status', value)}
                                >
                                    <SelectTrigger
                                        id="professional-status"
                                        className="min-h-11 w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">
                                            Ativo
                                        </SelectItem>
                                        <SelectItem value="inactive">
                                            Inativo
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <FormField
                                htmlFor="professional-email"
                                label="E-mail"
                                error={form.errors.email}
                            >
                                <Input
                                    id="professional-email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-11"
                                    aria-invalid={Boolean(form.errors.email)}
                                />
                            </FormField>
                            <FormField
                                htmlFor="professional-phone"
                                label="Telefone"
                                error={form.errors.phone}
                            >
                                <Input
                                    id="professional-phone"
                                    type="tel"
                                    value={form.data.phone}
                                    onChange={(event) =>
                                        form.setData(
                                            'phone',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-11"
                                    aria-invalid={Boolean(form.errors.phone)}
                                />
                            </FormField>
                        </div>
                        <FormField
                            htmlFor="professional-description"
                            label="Apresentação"
                            error={form.errors.description}
                            hint="Até 1.000 caracteres."
                        >
                            <textarea
                                id="professional-description"
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
                        <FormField
                            htmlFor="professional-avatar"
                            label="Foto"
                            error={form.errors.avatar}
                            hint="JPG, PNG ou WebP de até 2 MB."
                        >
                            <Input
                                id="professional-avatar"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                className="min-h-11 py-2"
                                onChange={(event) =>
                                    form.setData(
                                        'avatar',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                        </FormField>
                        {editing?.avatar_url && (
                            <label className="flex min-h-11 items-center gap-3 rounded-lg border px-3 text-sm">
                                <Checkbox
                                    checked={form.data.remove_avatar}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'remove_avatar',
                                            checked === true,
                                        )
                                    }
                                />
                                Remover a foto atual
                            </label>
                        )}
                        <fieldset className="grid gap-2">
                            <legend className="text-sm font-medium">
                                Serviços atendidos
                            </legend>
                            <div className="grid gap-2 rounded-lg border p-2 sm:grid-cols-2">
                                {services.length > 0 ? (
                                    services.map((service) => (
                                        <label
                                            key={service.uuid}
                                            className="flex min-h-11 items-center gap-3 rounded-md px-2 text-sm hover:bg-muted/60"
                                        >
                                            <Checkbox
                                                checked={form.data.service_uuids.includes(
                                                    service.uuid,
                                                )}
                                                onCheckedChange={(checked) =>
                                                    toggleService(
                                                        service.uuid,
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <span>
                                                {service.name}
                                                {!service.is_active && (
                                                    <span className="ml-1 text-muted-foreground">
                                                        (inativo)
                                                    </span>
                                                )}
                                            </span>
                                        </label>
                                    ))
                                ) : (
                                    <p className="p-2 text-sm text-muted-foreground">
                                        Cadastre serviços para relacioná-los à
                                        equipe.
                                    </p>
                                )}
                            </div>
                            {form.errors.service_uuids && (
                                <p className="text-sm text-destructive">
                                    {form.errors.service_uuids}
                                </p>
                            )}
                        </fieldset>
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
                            form="professional-form"
                            className="min-h-11"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {editing
                                ? 'Salvar alterações'
                                : 'Cadastrar profissional'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
