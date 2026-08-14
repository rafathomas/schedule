import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Cake,
    Mail,
    MessageCircle,
    NotebookText,
    Phone,
    UserRound,
} from 'lucide-react';
import { EmptyState } from '@/components/resources/empty-state';
import { ResourcePageHeader } from '@/components/resources/resource-page-header';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Customer } from '@/types';

const birthDate = (value: string | null) => {
    if (!value) {
        return null;
    }

    return new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(
        new Date(`${value}T00:00:00Z`),
    );
};

export default function CustomerShow({
    customer,
}: {
    customer: Customer;
    canManage: boolean;
}) {
    return (
        <>
            <Head title={customer.name} />
            <main
                id="main-content"
                className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
            >
                <Button asChild variant="ghost" className="min-h-11 w-fit px-2">
                    <Link href="/clientes">
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Voltar para clientes
                    </Link>
                </Button>
                <ResourcePageHeader
                    icon={UserRound}
                    eyebrow="Perfil do cliente"
                    title={customer.name}
                    description="Contato, anotações e histórico de relacionamento."
                />

                <section className="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
                    <Card className="gap-5 py-5 shadow-none">
                        <CardContent className="px-5 sm:px-6">
                            <div className="flex items-center gap-3 border-b pb-5">
                                <Avatar className="size-14">
                                    <AvatarFallback>
                                        {customer.name
                                            .slice(0, 2)
                                            .toUpperCase()}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <h2 className="font-semibold">
                                        {customer.name}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        Cliente cadastrado
                                    </p>
                                </div>
                            </div>
                            <dl className="mt-5 space-y-4 text-sm">
                                <div className="flex gap-3">
                                    <Phone
                                        className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <div>
                                        <dt className="text-xs text-muted-foreground">
                                            Telefone
                                        </dt>
                                        <dd className="mt-0.5 font-medium">
                                            {customer.phone}
                                        </dd>
                                    </div>
                                </div>
                                {customer.whatsapp && (
                                    <div className="flex gap-3">
                                        <MessageCircle
                                            className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        <div>
                                            <dt className="text-xs text-muted-foreground">
                                                WhatsApp
                                            </dt>
                                            <dd className="mt-0.5 font-medium">
                                                {customer.whatsapp}
                                            </dd>
                                        </div>
                                    </div>
                                )}
                                {customer.email && (
                                    <div className="flex min-w-0 gap-3">
                                        <Mail
                                            className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        <div className="min-w-0">
                                            <dt className="text-xs text-muted-foreground">
                                                E-mail
                                            </dt>
                                            <dd className="mt-0.5 truncate font-medium">
                                                {customer.email}
                                            </dd>
                                        </div>
                                    </div>
                                )}
                                {customer.birth_date && (
                                    <div className="flex gap-3">
                                        <Cake
                                            className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        <div>
                                            <dt className="text-xs text-muted-foreground">
                                                Nascimento
                                            </dt>
                                            <dd className="mt-0.5 font-medium">
                                                {birthDate(customer.birth_date)}
                                            </dd>
                                        </div>
                                    </div>
                                )}
                            </dl>
                        </CardContent>
                    </Card>

                    <div className="grid gap-6">
                        <Card className="gap-3 py-5 shadow-none">
                            <CardHeader className="px-5 sm:px-6">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <NotebookText
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Observações
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-5 sm:px-6">
                                <p className="text-sm leading-6 whitespace-pre-wrap text-muted-foreground">
                                    {customer.notes ||
                                        'Nenhuma observação registrada.'}
                                </p>
                            </CardContent>
                        </Card>
                        <EmptyState
                            icon={CalendarDays}
                            title="Histórico de atendimentos"
                            description="Os próximos agendamentos e o histórico aparecerão aqui quando a agenda for ativada na Etapa 3."
                        />
                    </div>
                </section>
            </main>
        </>
    );
}
