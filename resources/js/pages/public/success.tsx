import { Head, Link } from '@inertiajs/react';
import {
    CalendarPlus,
    Check,
    Clock3,
    MapPin,
    MessageCircle,
    Scissors,
    UserRound,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useBrandTheme } from '@/hooks/use-brand-theme';
import { brandStyle } from '@/lib/brand';

type Props = {
    company: {
        name: string;
        slug: string;
        whatsapp: string | null;
        phone: string | null;
        address: string;
        primary_color: string;
        logo_url: string | null;
    };
    appointment: {
        uuid: string;
        service: string;
        professional: string;
        date: string;
        time: string;
        calendar_url: string;
    };
};

export default function PublicBookingSuccess({ company, appointment }: Props) {
    useBrandTheme(company);
    const contact = company.whatsapp ?? company.phone;
    const whatsappUrl = company.whatsapp
        ? `https://wa.me/55${company.whatsapp.replace(/\D/g, '')}`
        : null;

    return (
        <>
            <Head title="Horário reservado" />
            <main
                className="brand-theme flex min-h-dvh items-center justify-center bg-slate-50 px-4 py-8 text-slate-950 dark:bg-neutral-950 dark:text-neutral-50"
                style={brandStyle(company.primary_color)}
            >
                <div className="w-full max-w-xl">
                    <div className="text-center">
                        <span className="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                            <Check className="size-8" aria-hidden="true" />
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
                            Seu horário foi reservado!
                        </h1>
                        <p className="mx-auto mt-3 max-w-md leading-7 text-slate-600 dark:text-neutral-400">
                            Guarde os detalhes abaixo. Enviaremos a confirmação
                            e os lembretes nos canais informados.
                        </p>
                    </div>

                    <Card className="mt-7 border-slate-200 py-0 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                        <CardContent className="divide-y divide-slate-200 p-0 dark:divide-neutral-800">
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
                                    icon: Clock3,
                                    label: 'Data e horário',
                                    value: `${appointment.date}, às ${appointment.time}`,
                                },
                                ...(company.address
                                    ? [
                                          {
                                              icon: MapPin,
                                              label: 'Endereço',
                                              value: company.address,
                                          },
                                      ]
                                    : []),
                                ...(contact
                                    ? [
                                          {
                                              icon: MessageCircle,
                                              label: 'Contato',
                                              value: contact,
                                          },
                                      ]
                                    : []),
                            ].map(({ icon: Icon, label, value }) => (
                                <div
                                    key={label}
                                    className="flex gap-4 px-5 py-4 sm:px-6"
                                >
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                        <Icon
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div>
                                        <p className="text-xs font-medium text-slate-500 dark:text-neutral-400">
                                            {label}
                                        </p>
                                        <p className="mt-1 leading-6 font-medium">
                                            {value}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                        <Button asChild className="min-h-12">
                            <a href={appointment.calendar_url}>
                                <CalendarPlus className="size-4" />
                                Adicionar ao calendário
                            </a>
                        </Button>
                        {whatsappUrl ? (
                            <Button
                                asChild
                                variant="outline"
                                className="min-h-12"
                            >
                                <a
                                    href={whatsappUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <MessageCircle className="size-4" />
                                    Falar com a empresa
                                </a>
                            </Button>
                        ) : (
                            <Button
                                asChild
                                variant="outline"
                                className="min-h-12"
                            >
                                <Link href={`/agenda/${company.slug}`}>
                                    Novo agendamento
                                </Link>
                            </Button>
                        )}
                    </div>
                    {whatsappUrl && (
                        <Button
                            asChild
                            variant="ghost"
                            className="mt-3 min-h-11 w-full"
                        >
                            <Link href={`/agenda/${company.slug}`}>
                                Fazer outro agendamento
                            </Link>
                        </Button>
                    )}
                </div>
            </main>
        </>
    );
}
