import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    Mail,
    Pencil,
    Phone,
    Plus,
    UserRound,
    UsersRound,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DeleteResourceDialog } from '@/components/resources/delete-resource-dialog';
import { EmptyState } from '@/components/resources/empty-state';
import { FormField } from '@/components/resources/form-field';
import { Pagination } from '@/components/resources/pagination';
import { ResourcePageHeader } from '@/components/resources/resource-page-header';
import { ResourceSearch } from '@/components/resources/resource-search';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import type { Customer, Paginated } from '@/types';

type CustomerForm = {
    name: string;
    phone: string;
    whatsapp: string;
    email: string;
    birth_date: string;
    notes: string;
};

const emptyForm: CustomerForm = {
    name: '',
    phone: '',
    whatsapp: '',
    email: '',
    birth_date: '',
    notes: '',
};

export default function CustomersIndex({
    customers,
    filters,
    canManage,
}: {
    customers: Paginated<Customer>;
    filters: { search: string };
    canManage: boolean;
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Customer | null>(null);
    const form = useForm<CustomerForm>(emptyForm);

    const create = () => {
        setEditing(null);
        form.setData(emptyForm);
        form.clearErrors();
        setOpen(true);
    };

    const edit = (customer: Customer) => {
        setEditing(customer);
        form.setData({
            name: customer.name,
            phone: customer.phone,
            whatsapp: customer.whatsapp ?? '',
            email: customer.email ?? '',
            birth_date: customer.birth_date ?? '',
            notes: customer.notes ?? '',
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
            form.patch(`/clientes/${editing.uuid}`, options);
        } else {
            form.post('/clientes', options);
        }
    };

    const action = canManage ? (
        <Button onClick={create} className="min-h-11 w-full sm:w-auto">
            <Plus className="size-4" aria-hidden="true" />
            Novo cliente
        </Button>
    ) : undefined;

    return (
        <>
            <Head title="Clientes" />
            <main
                id="main-content"
                className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
            >
                <ResourcePageHeader
                    icon={UsersRound}
                    title="Clientes"
                    description="Mantenha contatos, preferências e observações importantes em um só lugar."
                    action={action}
                />
                <ResourceSearch
                    action="/clientes"
                    initialValue={filters.search}
                    placeholder="Buscar por nome, telefone ou e-mail"
                />

                {customers.data.length === 0 ? (
                    <EmptyState
                        icon={UserRound}
                        title={
                            filters.search
                                ? 'Nenhum cliente encontrado'
                                : 'Comece sua base de clientes'
                        }
                        description={
                            filters.search
                                ? 'Revise o termo ou limpe a busca para visualizar todos os clientes.'
                                : 'Cadastre contatos agora; o histórico de atendimentos será preenchido pela agenda.'
                        }
                        action={action}
                    />
                ) : (
                    <section
                        aria-label="Lista de clientes"
                        className="grid gap-4 md:grid-cols-2 2xl:grid-cols-3"
                    >
                        {customers.data.map((customer) => (
                            <Card
                                key={customer.uuid}
                                className="gap-4 py-5 shadow-none transition-colors hover:border-blue-200 dark:hover:border-blue-900"
                            >
                                <CardContent className="px-5">
                                    <div className="flex items-start gap-3">
                                        <Avatar className="size-11">
                                            <AvatarFallback>
                                                {customer.name
                                                    .slice(0, 2)
                                                    .toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-start justify-between gap-2">
                                                <h2 className="truncate font-semibold">
                                                    {customer.name}
                                                </h2>
                                                {canManage && (
                                                    <div className="flex shrink-0">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="min-h-11 min-w-11"
                                                            onClick={() =>
                                                                edit(customer)
                                                            }
                                                            aria-label={`Editar ${customer.name}`}
                                                        >
                                                            <Pencil
                                                                className="size-4"
                                                                aria-hidden="true"
                                                            />
                                                        </Button>
                                                        <DeleteResourceDialog
                                                            compact
                                                            title={
                                                                customer.name
                                                            }
                                                            description="O cadastro e suas observações serão removidos. Esta ação não pode ser desfeita."
                                                            url={`/clientes/${customer.uuid}`}
                                                        />
                                                    </div>
                                                )}
                                            </div>
                                            <div className="mt-2 space-y-1.5 text-sm text-muted-foreground">
                                                <p className="flex items-center gap-2">
                                                    <Phone
                                                        className="size-4"
                                                        aria-hidden="true"
                                                    />
                                                    {customer.phone}
                                                </p>
                                                {customer.email && (
                                                    <p className="flex min-w-0 items-center gap-2">
                                                        <Mail
                                                            className="size-4 shrink-0"
                                                            aria-hidden="true"
                                                        />
                                                        <span className="truncate">
                                                            {customer.email}
                                                        </span>
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        className="mt-4 min-h-11 w-full justify-between border-t pt-3 text-blue-700 hover:text-blue-800 dark:text-blue-300"
                                    >
                                        <Link
                                            href={`/clientes/${customer.uuid}`}
                                        >
                                            Ver perfil e histórico
                                            <ArrowRight
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </section>
                )}
                <Pagination page={customers} />
            </main>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing ? 'Editar cliente' : 'Novo cliente'}
                        </DialogTitle>
                        <DialogDescription>
                            Campos com * são obrigatórios. Registre apenas dados
                            necessários ao atendimento.
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        id="customer-form"
                        onSubmit={submit}
                        className="grid gap-5"
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                htmlFor="customer-name"
                                label="Nome"
                                required
                                error={form.errors.name}
                            >
                                <Input
                                    id="customer-name"
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
                                htmlFor="customer-phone"
                                label="Telefone"
                                required
                                error={form.errors.phone}
                            >
                                <Input
                                    id="customer-phone"
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
                            <FormField
                                htmlFor="customer-whatsapp"
                                label="WhatsApp"
                                error={form.errors.whatsapp}
                                hint="Preencha se for diferente do telefone."
                            >
                                <Input
                                    id="customer-whatsapp"
                                    type="tel"
                                    value={form.data.whatsapp}
                                    onChange={(event) =>
                                        form.setData(
                                            'whatsapp',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-11"
                                />
                            </FormField>
                            <FormField
                                htmlFor="customer-email"
                                label="E-mail"
                                error={form.errors.email}
                            >
                                <Input
                                    id="customer-email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-11"
                                />
                            </FormField>
                            <FormField
                                htmlFor="customer-birth"
                                label="Data de nascimento"
                                error={form.errors.birth_date}
                            >
                                <Input
                                    id="customer-birth"
                                    type="date"
                                    value={form.data.birth_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'birth_date',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-11"
                                />
                            </FormField>
                        </div>
                        <FormField
                            htmlFor="customer-notes"
                            label="Observações"
                            error={form.errors.notes}
                            hint="Até 3.000 caracteres."
                        >
                            <textarea
                                id="customer-notes"
                                value={form.data.notes}
                                onChange={(event) =>
                                    form.setData('notes', event.target.value)
                                }
                                className="min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                            />
                        </FormField>
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
                            form="customer-form"
                            className="min-h-11"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {editing
                                ? 'Salvar alterações'
                                : 'Cadastrar cliente'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
