import { Head, router } from '@inertiajs/react';
import {
    Banknote,
    BarChart3,
    CalendarDays,
    CalendarX,
    CheckCircle2,
    Clock3,
    RefreshCw,
    Repeat2,
    UserPlus,
    UserX,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { ResourcePageHeader } from '@/components/resources/resource-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type Period = {
    preset: string;
    label: string;
    start_date: string;
    end_date: string;
};

type Metrics = {
    appointments: number;
    expected_revenue_cents: number;
    completed: number;
    cancelled: number;
    no_show: number;
    confirmation_rate: number;
    new_customers: number;
    recurring_customers: number;
};

type TimelinePoint = {
    key: string;
    label: string;
    appointments: number;
    revenue_cents: number;
};

type StatusPoint = {
    status: string;
    label: string;
    value: number;
};

type ServiceRanking = {
    name: string;
    appointments: number;
    revenue_cents: number;
};

type ProfessionalRanking = {
    name: string;
    appointments: number;
    completed: number;
};

type Props = {
    period: Period;
    metrics: Metrics;
    timeline: TimelinePoint[];
    status_distribution: StatusPoint[];
    top_services: ServiceRanking[];
    top_professionals: ProfessionalRanking[];
};

const periods = [
    { value: 'today', label: 'Hoje' },
    { value: 'last_7_days', label: '7 dias' },
    { value: 'last_30_days', label: '30 dias' },
    { value: 'current_month', label: 'Mês atual' },
    { value: 'custom', label: 'Personalizado' },
];

const currency = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const statusStyles: Record<string, string> = {
    pending: 'bg-amber-500',
    awaiting_confirmation: 'bg-violet-500',
    confirmed: 'bg-blue-600',
    cancelled: 'bg-rose-600',
    completed: 'bg-emerald-600',
    no_show: 'bg-slate-600',
};

export default function ReportsIndex({
    period,
    metrics,
    timeline,
    status_distribution,
    top_services,
    top_professionals,
}: Props) {
    const [selectedPeriod, setSelectedPeriod] = useState(period.preset);
    const [startDate, setStartDate] = useState(period.start_date);
    const [endDate, setEndDate] = useState(period.end_date);
    const [loading, setLoading] = useState(false);

    const loadPeriod = (value: string) => {
        setSelectedPeriod(value);

        if (value === 'custom') {
            return;
        }

        router.get(
            '/relatorios',
            { period: value },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setLoading(true),
                onFinish: () => setLoading(false),
            },
        );
    };

    const submitCustom = (event: FormEvent) => {
        event.preventDefault();
        router.get(
            '/relatorios',
            {
                period: 'custom',
                start_date: startDate,
                end_date: endDate,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setLoading(true),
                onFinish: () => setLoading(false),
            },
        );
    };

    const metricCards = [
        {
            label: 'Agendamentos',
            value: metrics.appointments.toLocaleString('pt-BR'),
            note: 'Todos os status no período',
            icon: CalendarDays,
        },
        {
            label: 'Faturamento previsto',
            value: currency.format(metrics.expected_revenue_cents / 100),
            note: 'Exclui cancelamentos e faltas',
            icon: Banknote,
        },
        {
            label: 'Concluídos',
            value: metrics.completed.toLocaleString('pt-BR'),
            note: 'Atendimentos finalizados',
            icon: CheckCircle2,
        },
        {
            label: 'Taxa de confirmação',
            value: `${metrics.confirmation_rate.toLocaleString('pt-BR')}%`,
            note: 'Confirmados sobre agendamentos',
            icon: Clock3,
        },
        {
            label: 'Cancelamentos',
            value: metrics.cancelled.toLocaleString('pt-BR'),
            note: 'Horários liberados',
            icon: CalendarX,
        },
        {
            label: 'Faltas',
            value: metrics.no_show.toLocaleString('pt-BR'),
            note: 'Marcados como não compareceu',
            icon: UserX,
        },
        {
            label: 'Clientes novos',
            value: metrics.new_customers.toLocaleString('pt-BR'),
            note: 'Primeiro atendimento no período',
            icon: UserPlus,
        },
        {
            label: 'Clientes recorrentes',
            value: metrics.recurring_customers.toLocaleString('pt-BR'),
            note: 'Já atendidos antes do período',
            icon: Repeat2,
        },
    ];

    return (
        <>
            <Head title="Relatórios" />
            <main
                id="main-content"
                className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
                aria-busy={loading}
            >
                <ResourcePageHeader
                    icon={BarChart3}
                    eyebrow="Desempenho"
                    title="Relatórios"
                    description="Acompanhe volume, receita, comparecimento e recorrência com dados do período selecionado."
                    action={
                        <Badge variant="outline" className="min-h-9 px-3">
                            {loading ? (
                                <Spinner />
                            ) : (
                                <RefreshCw className="size-3.5" />
                            )}
                            {loading ? 'Atualizando' : period.label}
                        </Badge>
                    }
                />

                <Card className="gap-4 py-5 shadow-none">
                    <CardContent className="px-5 sm:px-6">
                        <div
                            className="flex flex-wrap gap-2"
                            aria-label="Período do relatório"
                        >
                            {periods.map((option) => (
                                <Button
                                    key={option.value}
                                    type="button"
                                    variant={
                                        selectedPeriod === option.value
                                            ? 'default'
                                            : 'outline'
                                    }
                                    className="min-h-11"
                                    disabled={loading}
                                    onClick={() => loadPeriod(option.value)}
                                >
                                    {option.label}
                                </Button>
                            ))}
                        </div>

                        {selectedPeriod === 'custom' && (
                            <form
                                onSubmit={submitCustom}
                                className="mt-4 grid gap-4 border-t pt-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                            >
                                <div className="space-y-2">
                                    <Label htmlFor="report-start-date">
                                        Data inicial
                                    </Label>
                                    <Input
                                        id="report-start-date"
                                        type="date"
                                        value={startDate}
                                        max={endDate}
                                        onChange={(event) =>
                                            setStartDate(event.target.value)
                                        }
                                        required
                                        className="min-h-11"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="report-end-date">
                                        Data final
                                    </Label>
                                    <Input
                                        id="report-end-date"
                                        type="date"
                                        value={endDate}
                                        min={startDate}
                                        onChange={(event) =>
                                            setEndDate(event.target.value)
                                        }
                                        required
                                        className="min-h-11"
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    className="min-h-11"
                                    disabled={loading || !startDate || !endDate}
                                >
                                    Aplicar intervalo
                                </Button>
                            </form>
                        )}
                    </CardContent>
                </Card>

                <p className="sr-only" aria-live="polite">
                    {loading
                        ? 'Atualizando relatório.'
                        : `Relatório carregado: ${metrics.appointments} agendamentos.`}
                </p>

                <section
                    aria-label="Indicadores do período"
                    className={cn(
                        'grid gap-4 transition-opacity sm:grid-cols-2 xl:grid-cols-4',
                        loading && 'opacity-55',
                    )}
                >
                    {metricCards.map(({ label, value, note, icon: Icon }) => (
                        <Card key={label} className="gap-3 py-5 shadow-none">
                            <CardContent className="px-5">
                                <div className="flex items-start justify-between gap-3">
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
                                <p className="text-xs text-muted-foreground">
                                    {note}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.55fr_0.85fr]">
                    <TimelineChart points={timeline} />
                    <StatusDistribution
                        points={status_distribution}
                        total={metrics.appointments}
                    />
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <RankingCard
                        title="Serviços mais agendados"
                        description="Reservas não canceladas no período"
                        items={top_services.map((item) => ({
                            name: item.name,
                            value: item.appointments,
                            detail: currency.format(item.revenue_cents / 100),
                        }))}
                        valueLabel="agendamentos"
                    />
                    <RankingCard
                        title="Profissionais com mais atendimentos"
                        description="Volume atribuído no período"
                        items={top_professionals.map((item) => ({
                            name: item.name,
                            value: item.appointments,
                            detail: `${item.completed} concluído${item.completed === 1 ? '' : 's'}`,
                        }))}
                        valueLabel="atendimentos"
                    />
                </section>
            </main>
        </>
    );
}

function TimelineChart({ points }: { points: TimelinePoint[] }) {
    const width = 760;
    const height = 260;
    const padding = { top: 22, right: 22, bottom: 42, left: 36 };
    const max = Math.max(...points.map((point) => point.appointments), 1);
    const innerWidth = width - padding.left - padding.right;
    const innerHeight = height - padding.top - padding.bottom;
    const coordinates = points.map((point, index) => ({
        ...point,
        x:
            points.length === 1
                ? padding.left + innerWidth / 2
                : padding.left + (index / (points.length - 1)) * innerWidth,
        y: padding.top + innerHeight - (point.appointments / max) * innerHeight,
    }));
    const path = coordinates
        .map(
            (point, index) =>
                `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`,
        )
        .join(' ');
    const labelEvery = Math.max(1, Math.ceil(points.length / 7));

    return (
        <Card className="overflow-hidden py-0 shadow-none">
            <CardHeader className="border-b px-5 py-5 sm:px-6">
                <CardTitle className="text-base">
                    Agendamentos por período
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                    Evolução por {points.length > 62 ? 'semana' : 'dia'}{' '}
                    conforme a data agendada
                </p>
            </CardHeader>
            <CardContent className="px-3 py-5 sm:px-5">
                <figure>
                    <svg
                        viewBox={`0 0 ${width} ${height}`}
                        className="h-auto w-full"
                        role="img"
                        aria-labelledby="appointments-chart-title appointments-chart-description"
                    >
                        <title id="appointments-chart-title">
                            Evolução dos agendamentos
                        </title>
                        <desc id="appointments-chart-description">
                            Série temporal com {points.length} pontos. Maior
                            valor: {max}.
                        </desc>
                        {[0, 0.5, 1].map((ratio) => {
                            const y = padding.top + innerHeight * ratio;
                            const value = Math.round(max * (1 - ratio));

                            return (
                                <g key={ratio}>
                                    <line
                                        x1={padding.left}
                                        x2={width - padding.right}
                                        y1={y}
                                        y2={y}
                                        className="stroke-border"
                                        strokeDasharray="4 5"
                                    />
                                    <text
                                        x={padding.left - 8}
                                        y={y + 4}
                                        textAnchor="end"
                                        className="fill-muted-foreground text-[11px]"
                                    >
                                        {value}
                                    </text>
                                </g>
                            );
                        })}
                        <path
                            d={path}
                            fill="none"
                            className="stroke-blue-700 dark:stroke-blue-400"
                            strokeWidth="3"
                            strokeLinejoin="round"
                            strokeLinecap="round"
                        />
                        {coordinates.map((point, index) => (
                            <g key={point.key}>
                                <circle
                                    cx={point.x}
                                    cy={point.y}
                                    r="4"
                                    className="fill-background stroke-blue-700 dark:stroke-blue-400"
                                    strokeWidth="3"
                                >
                                    <title>
                                        {point.label}: {point.appointments}{' '}
                                        agendamentos,{' '}
                                        {currency.format(
                                            point.revenue_cents / 100,
                                        )}{' '}
                                        previstos
                                    </title>
                                </circle>
                                {(index % labelEvery === 0 ||
                                    index === points.length - 1) && (
                                    <text
                                        x={point.x}
                                        y={height - 12}
                                        textAnchor="middle"
                                        className="fill-muted-foreground text-[11px]"
                                    >
                                        {point.label}
                                    </text>
                                )}
                            </g>
                        ))}
                    </svg>
                    <details className="mt-2 rounded-lg border px-4 py-3 text-sm">
                        <summary className="cursor-pointer font-medium">
                            Ver dados do gráfico
                        </summary>
                        <div className="mt-3 max-h-64 overflow-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="text-xs text-muted-foreground">
                                    <tr>
                                        <th className="py-2 font-medium">
                                            Período
                                        </th>
                                        <th className="py-2 text-right font-medium">
                                            Agendamentos
                                        </th>
                                        <th className="py-2 text-right font-medium">
                                            Previsto
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {points.map((point) => (
                                        <tr key={point.key}>
                                            <td className="py-2">
                                                {point.label}
                                            </td>
                                            <td className="py-2 text-right tabular-nums">
                                                {point.appointments}
                                            </td>
                                            <td className="py-2 text-right tabular-nums">
                                                {currency.format(
                                                    point.revenue_cents / 100,
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </details>
                </figure>
            </CardContent>
        </Card>
    );
}

function StatusDistribution({
    points,
    total,
}: {
    points: StatusPoint[];
    total: number;
}) {
    return (
        <Card className="py-0 shadow-none">
            <CardHeader className="border-b px-5 py-5 sm:px-6">
                <CardTitle className="text-base">
                    Distribuição por status
                </CardTitle>
                <p className="text-sm text-muted-foreground">
                    Situação dos {total} agendamentos
                </p>
            </CardHeader>
            <CardContent className="space-y-4 px-5 py-5 sm:px-6">
                {points.map((point) => {
                    const percentage =
                        total === 0 ? 0 : (point.value / total) * 100;

                    return (
                        <div key={point.status}>
                            <div className="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <span className="flex min-w-0 items-center gap-2">
                                    <span
                                        className={cn(
                                            'size-2.5 shrink-0 rounded-sm',
                                            statusStyles[point.status],
                                        )}
                                        aria-hidden="true"
                                    />
                                    <span className="truncate">
                                        {point.label}
                                    </span>
                                </span>
                                <span className="font-medium tabular-nums">
                                    {point.value} · {Math.round(percentage)}%
                                </span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                <div
                                    className={cn(
                                        'h-full rounded-full transition-[width] duration-200 motion-reduce:transition-none',
                                        statusStyles[point.status],
                                    )}
                                    style={{ width: `${percentage}%` }}
                                />
                            </div>
                        </div>
                    );
                })}
            </CardContent>
        </Card>
    );
}

function RankingCard({
    title,
    description,
    items,
    valueLabel,
}: {
    title: string;
    description: string;
    items: { name: string; value: number; detail: string }[];
    valueLabel: string;
}) {
    const max = Math.max(...items.map((item) => item.value), 1);

    return (
        <Card className="py-0 shadow-none">
            <CardHeader className="border-b px-5 py-5 sm:px-6">
                <CardTitle className="text-base">{title}</CardTitle>
                <p className="text-sm text-muted-foreground">{description}</p>
            </CardHeader>
            <CardContent className="space-y-5 px-5 py-5 sm:px-6">
                {items.length === 0 ? (
                    <p className="py-8 text-center text-sm text-muted-foreground">
                        Sem dados para este período.
                    </p>
                ) : (
                    items.map((item, index) => (
                        <div key={item.name}>
                            <div className="mb-2 flex items-start justify-between gap-4 text-sm">
                                <span className="min-w-0 font-medium">
                                    <span className="mr-2 text-xs text-muted-foreground tabular-nums">
                                        {index + 1}.
                                    </span>
                                    {item.name}
                                </span>
                                <span className="shrink-0 text-right">
                                    <span className="block font-semibold tabular-nums">
                                        {item.value} {valueLabel}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {item.detail}
                                    </span>
                                </span>
                            </div>
                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-blue-700 transition-[width] duration-200 motion-reduce:transition-none dark:bg-blue-400"
                                    style={{
                                        width: `${(item.value / max) * 100}%`,
                                    }}
                                />
                            </div>
                        </div>
                    ))
                )}
            </CardContent>
        </Card>
    );
}
