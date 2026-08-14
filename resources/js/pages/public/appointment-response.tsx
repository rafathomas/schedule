import { Head, Link, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarCheck,
    CalendarX,
    Check,
    Clock3,
    Scissors,
    UserRound,
} from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useBrandTheme } from '@/hooks/use-brand-theme';
import { brandStyle } from '@/lib/brand';

type Props = {
    mode: 'confirm' | 'cancel';
    result: 'confirmed' | 'cancelled' | null;
    company: {
        name: string;
        slug: string;
        primary_color: string;
        logo_url: string | null;
    };
    appointment: {
        service: string;
        professional: string;
        date: string;
        time: string;
        status: string;
        status_label: string;
        confirmed_at: string | null;
        cancelled_at: string | null;
    };
    actionUrl: string;
    canConfirm: boolean;
    canCancel: boolean;
};

export default function AppointmentResponse({
    mode,
    result,
    company,
    appointment,
    actionUrl,
    canConfirm,
    canCancel,
}: Props) {
    useBrandTheme(company);
    const form = useForm({});
    const errors = form.errors as Record<string, string>;
    const completed =
        result === 'confirmed' ||
        result === 'cancelled' ||
        appointment.status === 'cancelled' ||
        (mode === 'confirm' && appointment.status === 'confirmed');
    const isCancellation = mode === 'cancel';
    const allowed = isCancellation ? canCancel : canConfirm;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(actionUrl, { preserveScroll: true });
    };

    const title = completed
        ? appointment.status === 'cancelled' || result === 'cancelled'
            ? 'Agendamento cancelado'
            : 'Presença confirmada'
        : isCancellation
          ? 'Cancelar agendamento'
          : 'Confirmar presença';

    return (
        <>
            <Head title={`${title} — ${company.name}`} />
            <main
                className="brand-theme flex min-h-dvh items-center justify-center bg-slate-50 px-4 py-8 text-slate-950 dark:bg-neutral-950 dark:text-neutral-50"
                style={brandStyle(company.primary_color)}
            >
                <div className="w-full max-w-xl">
                    <div className="text-center">
                        <span
                            className={`mx-auto flex size-16 items-center justify-center rounded-full ${
                                appointment.status === 'cancelled' ||
                                result === 'cancelled'
                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300'
                                    : completed
                                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                      : 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                            }`}
                        >
                            {appointment.status === 'cancelled' ||
                            result === 'cancelled' ? (
                                <CalendarX className="size-8" />
                            ) : completed ? (
                                <Check className="size-8" />
                            ) : (
                                <CalendarCheck className="size-8" />
                            )}
                        </span>
                        {company.logo_url && (
                            <img
                                src={company.logo_url}
                                alt={`Logo de ${company.name}`}
                                className="mx-auto mt-5 h-12 max-w-48 object-contain"
                            />
                        )}
                        <p className="mt-3 text-sm font-semibold text-blue-700 dark:text-blue-300">
                            {company.name}
                        </p>
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        <p className="mx-auto mt-3 max-w-md leading-7 text-slate-600 dark:text-neutral-400">
                            {completed
                                ? appointment.status === 'cancelled' ||
                                  result === 'cancelled'
                                    ? 'O horário foi liberado e a empresa recebeu o aviso.'
                                    : 'Obrigado! A empresa já recebeu sua confirmação.'
                                : isCancellation
                                  ? 'Confira os dados antes de liberar este horário.'
                                  : 'Confira os dados e confirme que você estará presente.'}
                        </p>
                    </div>

                    <Card className="mt-7 border-slate-200 py-0 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <CardContent className="p-5 sm:p-6">
                            <div className="mb-5 flex items-center justify-between gap-3">
                                <p className="font-semibold">
                                    Detalhes do atendimento
                                </p>
                                <Badge variant="outline">
                                    {appointment.status_label}
                                </Badge>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                {[
                                    {
                                        icon: Scissors,
                                        label: 'Serviço',
                                        value: appointment.service,
                                    },
                                    {
                                        icon: UserRound,
                                        label: 'Profissional',
                                        value: appointment.professional,
                                    },
                                    {
                                        icon: CalendarCheck,
                                        label: 'Data',
                                        value: appointment.date,
                                    },
                                    {
                                        icon: Clock3,
                                        label: 'Horário',
                                        value: appointment.time,
                                    },
                                ].map(({ icon: Icon, label, value }) => (
                                    <div key={label} className="flex gap-3">
                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-neutral-300">
                                            <Icon className="size-4" />
                                        </span>
                                        <div>
                                            <p className="text-xs text-slate-500 dark:text-neutral-400">
                                                {label}
                                            </p>
                                            <p className="mt-0.5 font-medium">
                                                {value}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {!completed && allowed && (
                        <form onSubmit={submit} className="mt-5">
                            {isCancellation && (
                                <div className="mb-4 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100">
                                    <AlertTriangle className="mt-0.5 size-5 shrink-0" />
                                    Esta ação não pode ser desfeita. Você poderá
                                    fazer um novo agendamento depois.
                                </div>
                            )}
                            <InputError message={errors.appointment} />
                            <Button
                                type="submit"
                                variant={
                                    isCancellation ? 'destructive' : 'default'
                                }
                                className="mt-3 min-h-12 w-full"
                                disabled={form.processing}
                            >
                                {isCancellation
                                    ? 'Sim, cancelar horário'
                                    : 'Confirmar minha presença'}
                            </Button>
                        </form>
                    )}

                    {!completed && !allowed && (
                        <p className="mt-5 rounded-xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-600 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300">
                            Este agendamento não aceita mais essa alteração.
                        </p>
                    )}

                    <Button
                        asChild
                        variant="ghost"
                        className="mt-3 min-h-11 w-full"
                    >
                        <Link href={`/agenda/${company.slug}`}>
                            Fazer novo agendamento
                        </Link>
                    </Button>
                </div>
            </main>
        </>
    );
}
