import { Head, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    Check,
    Clock3,
    LoaderCircle,
    MapPin,
    Scissors,
    ShieldCheck,
    Sparkles,
    UserRound,
    UsersRound,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useBrandTheme } from '@/hooks/use-brand-theme';
import { brandStyle } from '@/lib/brand';
import { cn } from '@/lib/utils';

type Company = {
    name: string;
    slug: string;
    segment: string | null;
    whatsapp: string | null;
    phone: string | null;
    city: string | null;
    state: string | null;
    primary_color: string;
    logo_url: string | null;
};

type Service = {
    uuid: string;
    name: string;
    description: string | null;
    category: string | null;
    duration_minutes: number;
    price: string;
};

type Professional = {
    uuid: string;
    name: string;
    description: string | null;
    service_uuids: string[];
};

type Slot = { value: string; label: string; starts_at: string };

const steps = [
    'Serviço',
    'Profissional',
    'Data',
    'Horário',
    'Seus dados',
    'Confirmar',
];

const initials = (name: string) =>
    name
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();

const currency = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

export default function PublicBooking({
    company,
    services,
    professionals,
    today,
    maximumDate,
    timezone,
}: {
    company: Company;
    services: Service[];
    professionals: Professional[];
    today: string;
    maximumDate: string;
    timezone: string;
}) {
    useBrandTheme(company);
    const [step, setStep] = useState(1);
    const [slots, setSlots] = useState<Slot[]>([]);
    const [loadingSlots, setLoadingSlots] = useState(false);
    const requestSequence = useRef(0);
    const form = useForm({
        service_uuid: '',
        professional_uuid: '',
        date: '',
        time: '',
        name: '',
        whatsapp: '',
        email: '',
    });

    const service = services.find(
        (item) => item.uuid === form.data.service_uuid,
    );
    const professional = professionals.find(
        (item) => item.uuid === form.data.professional_uuid,
    );
    const eligibleProfessionals = useMemo(
        () =>
            professionals.filter((item) =>
                item.service_uuids.includes(form.data.service_uuid),
            ),
        [form.data.service_uuid, professionals],
    );
    const suggestedDates = useMemo(
        () =>
            Array.from({ length: 7 }, (_, index) => {
                const date = new Date(`${today}T12:00:00Z`);

                date.setUTCDate(date.getUTCDate() + index);

                return {
                    value: date.toISOString().slice(0, 10),
                    weekday: new Intl.DateTimeFormat('pt-BR', {
                        weekday: 'short',
                        timeZone: 'UTC',
                    }).format(date),
                    day: new Intl.DateTimeFormat('pt-BR', {
                        day: '2-digit',
                        month: '2-digit',
                        timeZone: 'UTC',
                    }).format(date),
                };
            }).filter((date) => date.value <= maximumDate),
        [maximumDate, today],
    );

    const chooseService = (uuid: string) => {
        form.setData({
            ...form.data,
            service_uuid: uuid,
            professional_uuid: '',
            date: '',
            time: '',
        });
        setSlots([]);
        setStep(2);
    };

    const chooseProfessional = (uuid: string) => {
        form.setData({
            ...form.data,
            professional_uuid: uuid,
            date: '',
            time: '',
        });
        setSlots([]);
        setStep(3);
    };

    const loadSlots = async () => {
        if (!form.data.service_uuid || !form.data.date) {
            return;
        }

        const sequence = ++requestSequence.current;
        const params = new URLSearchParams({
            service: form.data.service_uuid,
            date: form.data.date,
        });

        if (form.data.professional_uuid) {
            params.set('professional', form.data.professional_uuid);
        }

        setLoadingSlots(true);
        setSlots([]);
        setStep(4);

        try {
            const response = await fetch(
                `/agenda/${company.slug}/disponibilidade?${params.toString()}`,
                { headers: { Accept: 'application/json' } },
            );

            if (!response.ok) {
                throw new Error('availability');
            }

            const data = (await response.json()) as { slots: Slot[] };

            if (sequence === requestSequence.current) {
                setSlots(data.slots);
            }
        } catch {
            if (sequence === requestSequence.current) {
                setSlots([]);
            }
        } finally {
            if (sequence === requestSequence.current) {
                setLoadingSlots(false);
            }
        }
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/agenda/${company.slug}`, {
            onError: (errors) => setStep(errors.time ? 4 : 5),
        });
    };

    return (
        <>
            <Head title={`Agendar em ${company.name}`}>
                <meta
                    name="description"
                    content={`Agende seu atendimento online em ${company.name}.`}
                />
            </Head>
            <div
                className="brand-theme min-h-dvh bg-slate-50 text-slate-950 dark:bg-neutral-950 dark:text-neutral-50"
                style={brandStyle(company.primary_color)}
            >
                <header className="border-b bg-white/95 dark:border-neutral-800 dark:bg-neutral-950">
                    <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
                        <div className="flex min-w-0 items-center gap-3">
                            <span className="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-primary text-sm font-bold text-primary-foreground">
                                {company.logo_url ? (
                                    <img
                                        src={company.logo_url}
                                        alt={`Logo de ${company.name}`}
                                        className="size-full bg-white object-contain p-1"
                                    />
                                ) : (
                                    initials(company.name)
                                )}
                            </span>
                            <div className="min-w-0">
                                <p className="truncate font-semibold">
                                    {company.name}
                                </p>
                                {(company.city || company.state) && (
                                    <p className="flex items-center gap-1 text-xs text-slate-500 dark:text-neutral-400">
                                        <MapPin
                                            className="size-3"
                                            aria-hidden="true"
                                        />
                                        {[company.city, company.state]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                )}
                            </div>
                        </div>
                        <span className="hidden items-center gap-2 text-sm text-slate-500 sm:flex dark:text-neutral-400">
                            <ShieldCheck
                                className="size-4 text-emerald-600"
                                aria-hidden="true"
                            />
                            Agendamento seguro
                        </span>
                    </div>
                </header>

                <main className="mx-auto grid max-w-5xl gap-6 px-4 py-6 sm:px-6 sm:py-10 lg:grid-cols-[1fr_16rem]">
                    <section>
                        <div className="mb-6">
                            <p className="text-sm font-semibold text-blue-700 dark:text-blue-300">
                                Etapa {step} de {steps.length}
                            </p>
                            <div className="mt-2 flex gap-1" aria-hidden="true">
                                {steps.map((label, index) => (
                                    <span
                                        key={label}
                                        className={cn(
                                            'h-1.5 flex-1 rounded-full',
                                            index < step
                                                ? 'bg-blue-700 dark:bg-blue-400'
                                                : 'bg-slate-200 dark:bg-neutral-800',
                                        )}
                                    />
                                ))}
                            </div>
                            <h1 className="mt-5 text-2xl font-semibold tracking-tight sm:text-3xl">
                                {step === 1 && 'Qual serviço você procura?'}
                                {step === 2 && 'Com quem você prefere?'}
                                {step === 3 && 'Escolha uma data'}
                                {step === 4 && 'Escolha o melhor horário'}
                                {step === 5 && 'Como podemos falar com você?'}
                                {step === 6 && 'Confira seu agendamento'}
                            </h1>
                            <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-neutral-400">
                                {steps[step - 1]} · Horários exibidos no fuso{' '}
                                {timezone}
                            </p>
                        </div>

                        <Card className="border-slate-200 py-0 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                            <CardContent className="p-4 sm:p-6">
                                {step === 1 && (
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        {services.map((item) => (
                                            <button
                                                type="button"
                                                key={item.uuid}
                                                onClick={() =>
                                                    chooseService(item.uuid)
                                                }
                                                className="group flex min-h-28 w-full items-start gap-4 rounded-xl border border-slate-200 p-4 text-left transition-colors hover:border-blue-300 hover:bg-blue-50/60 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none dark:border-neutral-700 dark:hover:border-blue-700 dark:hover:bg-blue-950/30"
                                            >
                                                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 group-hover:bg-white dark:bg-blue-950 dark:text-blue-300 dark:group-hover:bg-blue-950">
                                                    <Scissors
                                                        className="size-5"
                                                        aria-hidden="true"
                                                    />
                                                </span>
                                                <span className="min-w-0 flex-1">
                                                    {item.category && (
                                                        <span className="text-xs font-medium text-slate-500 dark:text-neutral-400">
                                                            {item.category}
                                                        </span>
                                                    )}
                                                    <span className="mt-0.5 block font-semibold">
                                                        {item.name}
                                                    </span>
                                                    <span className="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm text-slate-600 dark:text-neutral-400">
                                                        <span>
                                                            {
                                                                item.duration_minutes
                                                            }{' '}
                                                            min
                                                        </span>
                                                        <span className="font-semibold text-slate-950 dark:text-white">
                                                            {currency.format(
                                                                Number(
                                                                    item.price,
                                                                ),
                                                            )}
                                                        </span>
                                                    </span>
                                                </span>
                                                <ArrowRight
                                                    className="mt-2 size-4 shrink-0 text-slate-400"
                                                    aria-hidden="true"
                                                />
                                            </button>
                                        ))}
                                        {services.length === 0 && (
                                            <p className="col-span-full py-12 text-center text-slate-500">
                                                Nenhum serviço disponível no
                                                momento.
                                            </p>
                                        )}
                                    </div>
                                )}

                                {step === 2 && (
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                chooseProfessional('')
                                            }
                                            className="flex min-h-24 items-center gap-4 rounded-xl border border-blue-200 bg-blue-50/60 p-4 text-left transition-colors hover:bg-blue-100 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none dark:border-blue-900 dark:bg-blue-950/30 dark:hover:bg-blue-950/60"
                                        >
                                            <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                                <UsersRound className="size-5" />
                                            </span>
                                            <span>
                                                <span className="block font-semibold">
                                                    Qualquer profissional
                                                    disponível
                                                </span>
                                                <span className="mt-1 block text-sm text-slate-600 dark:text-neutral-400">
                                                    Encontre o primeiro horário
                                                    livre.
                                                </span>
                                            </span>
                                        </button>
                                        {eligibleProfessionals.map((item) => (
                                            <button
                                                type="button"
                                                key={item.uuid}
                                                onClick={() =>
                                                    chooseProfessional(
                                                        item.uuid,
                                                    )
                                                }
                                                className="flex min-h-24 items-center gap-4 rounded-xl border border-slate-200 p-4 text-left transition-colors hover:border-blue-300 hover:bg-blue-50/60 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none dark:border-neutral-700 dark:hover:border-blue-700 dark:hover:bg-blue-950/30"
                                            >
                                                <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700 dark:bg-neutral-800 dark:text-neutral-200">
                                                    {initials(item.name)}
                                                </span>
                                                <span>
                                                    <span className="block font-semibold">
                                                        {item.name}
                                                    </span>
                                                    {item.description && (
                                                        <span className="mt-1 line-clamp-2 block text-sm text-slate-600 dark:text-neutral-400">
                                                            {item.description}
                                                        </span>
                                                    )}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                )}

                                {step === 3 && (
                                    <div className="mx-auto max-w-md py-4 sm:py-8">
                                        <p className="text-sm font-medium">
                                            Próximos dias
                                        </p>
                                        <div className="mt-3 grid grid-cols-4 gap-2 sm:grid-cols-7">
                                            {suggestedDates.map((date) => (
                                                <button
                                                    type="button"
                                                    key={date.value}
                                                    onClick={() =>
                                                        form.setData(
                                                            'date',
                                                            date.value,
                                                        )
                                                    }
                                                    aria-pressed={
                                                        form.data.date ===
                                                        date.value
                                                    }
                                                    className={cn(
                                                        'flex min-h-16 flex-col items-center justify-center rounded-lg border px-2 text-center focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none',
                                                        form.data.date ===
                                                            date.value
                                                            ? 'border-blue-700 bg-blue-700 text-white dark:border-blue-400 dark:bg-blue-400 dark:text-neutral-950'
                                                            : 'border-slate-200 hover:border-blue-300 hover:bg-blue-50 dark:border-neutral-700 dark:hover:border-blue-700 dark:hover:bg-blue-950/30',
                                                    )}
                                                >
                                                    <span className="text-xs capitalize">
                                                        {date.weekday}
                                                    </span>
                                                    <span className="mt-1 text-sm font-semibold tabular-nums">
                                                        {date.day}
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                        <div className="my-5 flex items-center gap-3 text-xs text-slate-400 dark:text-neutral-500">
                                            <span className="h-px flex-1 bg-slate-200 dark:bg-neutral-800" />
                                            ou escolha outra data
                                            <span className="h-px flex-1 bg-slate-200 dark:bg-neutral-800" />
                                        </div>
                                        <Label
                                            htmlFor="booking-date"
                                            className="text-base"
                                        >
                                            Data do atendimento
                                        </Label>
                                        <Input
                                            id="booking-date"
                                            type="date"
                                            min={today}
                                            max={maximumDate}
                                            value={form.data.date}
                                            onChange={(event) =>
                                                form.setData(
                                                    'date',
                                                    event.target.value,
                                                )
                                            }
                                            className="mt-3 min-h-12 text-base"
                                        />
                                        <p className="mt-3 flex items-start gap-2 text-sm leading-6 text-slate-600 dark:text-neutral-400">
                                            <CalendarDays className="mt-1 size-4 shrink-0" />
                                            Você verá somente horários
                                            compatíveis com o serviço e a agenda
                                            escolhidos.
                                        </p>
                                        <Button
                                            className="mt-6 min-h-12 w-full"
                                            disabled={!form.data.date}
                                            onClick={() => void loadSlots()}
                                        >
                                            Ver horários
                                            <ArrowRight className="size-4" />
                                        </Button>
                                    </div>
                                )}

                                {step === 4 && (
                                    <div>
                                        {loadingSlots ? (
                                            <div className="flex min-h-48 flex-col items-center justify-center text-slate-500">
                                                <LoaderCircle className="mb-3 size-6 animate-spin" />
                                                <p>Consultando a agenda…</p>
                                            </div>
                                        ) : slots.length > 0 ? (
                                            <div className="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5">
                                                {slots.map((slot) => (
                                                    <Button
                                                        key={slot.starts_at}
                                                        variant={
                                                            form.data.time ===
                                                            slot.value
                                                                ? 'default'
                                                                : 'outline'
                                                        }
                                                        className="min-h-12 tabular-nums"
                                                        onClick={() => {
                                                            form.setData(
                                                                'time',
                                                                slot.value,
                                                            );
                                                            setStep(5);
                                                        }}
                                                    >
                                                        {slot.label}
                                                    </Button>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="flex min-h-48 flex-col items-center justify-center text-center">
                                                <Clock3 className="mb-3 size-7 text-slate-400" />
                                                <p className="font-semibold">
                                                    Nenhum horário livre nesta
                                                    data
                                                </p>
                                                <p className="mt-1 text-sm text-slate-500">
                                                    Escolha outro dia para
                                                    continuar.
                                                </p>
                                                <Button
                                                    variant="outline"
                                                    className="mt-5 min-h-11"
                                                    onClick={() => setStep(3)}
                                                >
                                                    Escolher outra data
                                                </Button>
                                            </div>
                                        )}
                                        <InputError
                                            message={form.errors.time}
                                            className="mt-3"
                                        />
                                    </div>
                                )}

                                {step === 5 && (
                                    <div className="grid gap-5">
                                        <div className="grid gap-2">
                                            <Label htmlFor="booking-name">
                                                Nome completo{' '}
                                                <span className="text-red-600">
                                                    *
                                                </span>
                                            </Label>
                                            <Input
                                                id="booking-name"
                                                autoComplete="name"
                                                className="min-h-12 text-base"
                                                value={form.data.name}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={Boolean(
                                                    form.errors.name,
                                                )}
                                            />
                                            <InputError
                                                message={form.errors.name}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="booking-whatsapp">
                                                WhatsApp{' '}
                                                <span className="text-red-600">
                                                    *
                                                </span>
                                            </Label>
                                            <Input
                                                id="booking-whatsapp"
                                                type="tel"
                                                inputMode="tel"
                                                autoComplete="tel"
                                                placeholder="(11) 99999-9999"
                                                className="min-h-12 text-base"
                                                value={form.data.whatsapp}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'whatsapp',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={Boolean(
                                                    form.errors.whatsapp,
                                                )}
                                            />
                                            <InputError
                                                message={form.errors.whatsapp}
                                            />
                                            <p className="text-xs text-slate-500">
                                                Será usado futuramente para
                                                confirmações e lembretes.
                                            </p>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="booking-email">
                                                E-mail{' '}
                                                <span className="font-normal text-slate-500">
                                                    (opcional)
                                                </span>
                                            </Label>
                                            <Input
                                                id="booking-email"
                                                type="email"
                                                autoComplete="email"
                                                className="min-h-12 text-base"
                                                value={form.data.email}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'email',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={Boolean(
                                                    form.errors.email,
                                                )}
                                            />
                                            <InputError
                                                message={form.errors.email}
                                            />
                                        </div>
                                        <Button
                                            className="min-h-12"
                                            disabled={
                                                !form.data.name.trim() ||
                                                !form.data.whatsapp.trim()
                                            }
                                            onClick={() => setStep(6)}
                                        >
                                            Revisar agendamento
                                            <ArrowRight className="size-4" />
                                        </Button>
                                    </div>
                                )}

                                {step === 6 && service && (
                                    <form onSubmit={submit}>
                                        <dl className="divide-y divide-slate-200 rounded-xl border border-slate-200 dark:divide-neutral-800 dark:border-neutral-700">
                                            {[
                                                ['Serviço', service.name],
                                                [
                                                    'Profissional',
                                                    professional?.name ??
                                                        'Qualquer profissional disponível',
                                                ],
                                                [
                                                    'Data',
                                                    new Intl.DateTimeFormat(
                                                        'pt-BR',
                                                        {
                                                            dateStyle: 'long',
                                                            timeZone: 'UTC',
                                                        },
                                                    ).format(
                                                        new Date(
                                                            `${form.data.date}T12:00:00Z`,
                                                        ),
                                                    ),
                                                ],
                                                ['Horário', form.data.time],
                                                [
                                                    'Duração',
                                                    `${service.duration_minutes} minutos`,
                                                ],
                                                [
                                                    'Valor',
                                                    currency.format(
                                                        Number(service.price),
                                                    ),
                                                ],
                                            ].map(([label, value]) => (
                                                <div
                                                    key={label}
                                                    className="grid grid-cols-[7rem_1fr] gap-3 px-4 py-3 text-sm sm:grid-cols-[9rem_1fr]"
                                                >
                                                    <dt className="text-slate-500 dark:text-neutral-400">
                                                        {label}
                                                    </dt>
                                                    <dd className="text-right font-medium sm:text-left">
                                                        {value}
                                                    </dd>
                                                </div>
                                            ))}
                                        </dl>
                                        <div className="mt-5 rounded-xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                                            <span className="flex gap-2">
                                                <ShieldCheck className="mt-0.5 size-5 shrink-0" />
                                                Sua vaga será validada novamente
                                                ao confirmar para evitar
                                                reservas simultâneas.
                                            </span>
                                        </div>
                                        <Button
                                            type="submit"
                                            className="mt-5 min-h-12 w-full"
                                            disabled={form.processing}
                                        >
                                            {form.processing ? (
                                                <LoaderCircle className="size-4 animate-spin" />
                                            ) : (
                                                <Check className="size-4" />
                                            )}
                                            Confirmar agendamento
                                        </Button>
                                    </form>
                                )}
                            </CardContent>
                        </Card>

                        {step > 1 && (
                            <Button
                                variant="ghost"
                                className="mt-4 min-h-11"
                                onClick={() => {
                                    form.clearErrors();
                                    setStep(step - 1);
                                }}
                            >
                                <ArrowLeft className="size-4" />
                                Voltar
                            </Button>
                        )}
                    </section>

                    <aside
                        className="space-y-4 lg:pt-24"
                        aria-label="Resumo da seleção"
                    >
                        <Card className="border-slate-200 py-0 shadow-none dark:border-neutral-800 dark:bg-neutral-900">
                            <CardContent className="p-5">
                                <p className="text-sm font-semibold">
                                    Sua escolha
                                </p>
                                <div className="mt-4 space-y-4 text-sm">
                                    <div className="flex gap-3">
                                        <Scissors className="mt-0.5 size-4 shrink-0 text-blue-700 dark:text-blue-300" />
                                        <div>
                                            <p className="text-slate-500 dark:text-neutral-400">
                                                Serviço
                                            </p>
                                            <p className="mt-0.5 font-medium">
                                                {service?.name ??
                                                    'Ainda não escolhido'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex gap-3">
                                        <UserRound className="mt-0.5 size-4 shrink-0 text-blue-700 dark:text-blue-300" />
                                        <div>
                                            <p className="text-slate-500 dark:text-neutral-400">
                                                Profissional
                                            </p>
                                            <p className="mt-0.5 font-medium">
                                                {form.data.service_uuid
                                                    ? (professional?.name ??
                                                      'Qualquer disponível')
                                                    : 'Ainda não escolhido'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex gap-3">
                                        <CalendarDays className="mt-0.5 size-4 shrink-0 text-blue-700 dark:text-blue-300" />
                                        <div>
                                            <p className="text-slate-500 dark:text-neutral-400">
                                                Quando
                                            </p>
                                            <p className="mt-0.5 font-medium tabular-nums">
                                                {form.data.date
                                                    ? `${form.data.date.split('-').reverse().join('/')} ${form.data.time ? `às ${form.data.time}` : ''}`
                                                    : 'Ainda não escolhido'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        <div className="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 text-xs leading-5 text-slate-500 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-400">
                            <Sparkles className="mt-0.5 size-4 shrink-0 text-blue-700 dark:text-blue-300" />
                            <p>
                                Você não precisa criar conta. Leva menos de dois
                                minutos.
                            </p>
                        </div>
                    </aside>
                </main>
            </div>
        </>
    );
}
