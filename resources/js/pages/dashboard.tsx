import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Banknote,
    BarChart3,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Copy,
    ExternalLink,
    UsersRound,
} from 'lucide-react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type OperationalSummary = {
    today: {
        appointments: number;
        expected_revenue_cents: number;
        completed: number;
        awaiting_confirmation: number;
        confirmed: number;
        confirmation_rate: number;
        cancelled: number;
    };
    upcoming: {
        uuid: string;
        customer: string;
        service: string;
        professional: string;
        date: string;
        time: string;
        status: string;
        status_label: string;
    }[];
};

const currency = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const statusVariant = (status: string) =>
    status === 'confirmed'
        ? 'default'
        : status === 'awaiting_confirmation'
          ? 'secondary'
          : 'outline';

export default function Dashboard({
    catalogSummary,
    operationalSummary,
    canManage,
}: {
    catalogSummary: {
        customers: number;
        professionals: number;
        services: number;
    };
    operationalSummary: OperationalSummary;
    canManage: boolean;
}) {
    const { auth, company } = usePage().props;
    const bookingPath = `/agenda/${company?.slug ?? ''}`;
    const bookingUrl =
        typeof window === 'undefined'
            ? bookingPath
            : `${window.location.origin}${bookingPath}`;

    const copyBookingUrl = async () => {
        try {
            await navigator.clipboard.writeText(bookingUrl);
            toast.success('Endereço copiado.');
        } catch {
            toast.error(
                'Não foi possível copiar. Selecione o endereço manualmente.',
            );
        }
    };

    const metrics = [
        {
            label: 'Agendamentos hoje',
            value: operationalSummary.today.appointments.toLocaleString(
                'pt-BR',
            ),
            note: `${operationalSummary.today.completed} concluído${operationalSummary.today.completed === 1 ? '' : 's'}`,
            icon: CalendarDays,
        },
        {
            label: 'Aguardando confirmação',
            value: operationalSummary.today.awaiting_confirmation.toLocaleString(
                'pt-BR',
            ),
            note: `${operationalSummary.today.confirmation_rate.toLocaleString('pt-BR')}% de confirmação`,
            icon: Clock3,
        },
        {
            label: 'Confirmados',
            value: operationalSummary.today.confirmed.toLocaleString('pt-BR'),
            note: `${operationalSummary.today.cancelled} cancelado${operationalSummary.today.cancelled === 1 ? '' : 's'} hoje`,
            icon: CheckCircle2,
        },
        {
            label: 'Faturamento previsto',
            value: currency.format(
                operationalSummary.today.expected_revenue_cents / 100,
            ),
            note: 'Exclui cancelamentos e faltas',
            icon: Banknote,
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <main
                id="main-content"
                className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
            >
                <section className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-blue-700 dark:text-blue-300">
                            {company?.name}
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                            Olá, {auth.user.name.split(' ')[0]}
                        </h1>
                        <p className="mt-2 text-muted-foreground">
                            Aqui está o resumo da operação de hoje.
                        </p>
                    </div>
                    <Button asChild className="min-h-11 w-full sm:w-auto">
                        <Link href="/agenda">
                            Abrir agenda
                            <ArrowRight className="size-4" aria-hidden="true" />
                        </Link>
                    </Button>
                </section>

                <section
                    aria-label="Resumo de hoje"
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    {metrics.map(({ label, value, icon: Icon, note }) => (
                        <Card key={label} className="gap-4 py-5 shadow-none">
                            <CardContent className="px-5">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="min-w-0">
                                        <p className="text-sm font-medium text-muted-foreground">
                                            {label}
                                        </p>
                                        <p className="mt-2 text-2xl font-semibold tracking-tight tabular-nums">
                                            {value}
                                        </p>
                                    </div>
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                                        <Icon
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                </div>
                                <p className="mt-3 text-xs text-muted-foreground">
                                    {note}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.4fr_0.6fr]">
                    <Card className="overflow-hidden py-0 shadow-none">
                        <CardHeader className="flex-row items-center justify-between gap-4 border-b px-5 py-5 sm:px-6">
                            <div>
                                <CardTitle className="text-lg">
                                    Próximos atendimentos
                                </CardTitle>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Os cinco compromissos ativos mais próximos
                                </p>
                            </div>
                            <Button
                                asChild
                                variant="ghost"
                                size="sm"
                                className="min-h-11"
                            >
                                <Link href="/agenda">Ver agenda</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="divide-y px-0">
                            {operationalSummary.upcoming.length === 0 ? (
                                <div className="px-5 py-12 text-center sm:px-6">
                                    <CalendarDays
                                        className="mx-auto size-9 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <p className="mt-3 font-medium">
                                        Nenhum atendimento próximo
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Novas reservas aparecerão aqui
                                        automaticamente.
                                    </p>
                                </div>
                            ) : (
                                operationalSummary.upcoming.map(
                                    (appointment) => (
                                        <div
                                            key={appointment.uuid}
                                            className="grid gap-3 px-5 py-4 sm:grid-cols-[5rem_1fr_auto] sm:items-center sm:px-6"
                                        >
                                            <div className="flex items-baseline gap-2 sm:block">
                                                <p className="font-semibold tabular-nums">
                                                    {appointment.time}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {appointment.date}
                                                </p>
                                            </div>
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {appointment.customer}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {appointment.service} ·{' '}
                                                    {appointment.professional}
                                                </p>
                                            </div>
                                            <Badge
                                                variant={statusVariant(
                                                    appointment.status,
                                                )}
                                                className="w-fit"
                                            >
                                                {appointment.status_label}
                                            </Badge>
                                        </div>
                                    ),
                                )
                            )}
                        </CardContent>
                    </Card>

                    <div className="grid content-start gap-6">
                        {canManage && (
                            <Card className="gap-4 py-5 shadow-none">
                                <CardContent className="px-5">
                                    <span className="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                                        <BarChart3
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <h2 className="mt-4 font-semibold">
                                        Analise seu desempenho
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                        Compare receita, confirmações,
                                        recorrência, serviços e profissionais.
                                    </p>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="mt-4 min-h-11 w-full"
                                    >
                                        <Link href="/relatorios">
                                            Abrir relatórios
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        )}

                        <Card className="gap-5 bg-primary py-6 text-primary-foreground shadow-none">
                            <CardHeader className="px-5">
                                <span className="mb-3 flex size-11 items-center justify-center rounded-lg bg-white/10">
                                    <ExternalLink
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <CardTitle className="text-lg">
                                    Link de agendamento
                                </CardTitle>
                                <p className="text-sm leading-6 text-blue-100">
                                    Compartilhe sua página para receber novas
                                    reservas.
                                </p>
                            </CardHeader>
                            <CardContent className="px-5">
                                <div className="rounded-lg border border-white/15 bg-white/10 p-3 text-sm break-all">
                                    /agenda/{company?.slug}
                                </div>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={copyBookingUrl}
                                    className="mt-4 min-h-11 w-full"
                                >
                                    <Copy
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Copiar endereço
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <section
                    aria-label="Catálogo"
                    className="text-sm text-muted-foreground"
                >
                    <UsersRound
                        className="mr-2 inline size-4"
                        aria-hidden="true"
                    />
                    {catalogSummary.customers} clientes ·{' '}
                    {catalogSummary.professionals} profissionais ·{' '}
                    {catalogSummary.services} serviços
                </section>
            </main>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
