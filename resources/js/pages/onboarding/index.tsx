import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    Check,
    Clock3,
    MapPin,
    Save,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { Company } from '@/types';

type Props = {
    company: Company;
};

const fieldClass = 'min-h-11 w-full';

export default function Onboarding({ company }: Props) {
    const form = useForm({
        name: company.name ?? '',
        segment: company.segment ?? '',
        phone: company.phone ?? '',
        whatsapp: company.whatsapp ?? '',
        postal_code: company.postal_code ?? '',
        address: company.address ?? '',
        address_number: company.address_number ?? '',
        address_complement: company.address_complement ?? '',
        city: company.city ?? '',
        state: company.state ?? '',
        timezone: company.timezone ?? 'America/Sao_Paulo',
        appointment_interval_minutes:
            company.appointment_interval_minutes ?? 30,
        minimum_notice_minutes: company.minimum_notice_minutes ?? 120,
        maximum_notice_days: company.maximum_notice_days ?? 60,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.patch('/onboarding/company', {
            preserveScroll: true,
            onSuccess: () => toast.success('Dados da empresa salvos.'),
            onError: () =>
                toast.error('Revise os campos destacados e tente novamente.'),
        });
    };

    const completed = Boolean(company.onboarding_steps?.company_profile);

    return (
        <>
            <Head title="Configurar empresa" />
            <main
                id="main-content"
                className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
            >
                <div>
                    <Button asChild variant="ghost" className="-ml-3 min-h-11">
                        <Link href="/dashboard">
                            <ArrowLeft className="size-4" aria-hidden="true" />
                            Voltar ao dashboard
                        </Link>
                    </Button>
                    <div className="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-blue-700 dark:text-blue-300">
                                Etapa 1 de 5
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                                Dados da empresa
                            </h1>
                            <p className="mt-2 max-w-2xl text-muted-foreground">
                                Essas informações definem como o negócio
                                funciona e aparecerão na página de agendamento.
                            </p>
                        </div>
                        {completed && (
                            <div className="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                                <Check className="size-5" aria-hidden="true" />
                                Dados principais concluídos
                            </div>
                        )}
                    </div>
                </div>

                <div
                    className="h-2 overflow-hidden rounded-full bg-border"
                    aria-label="1 de 5 etapas concluídas"
                >
                    <div className="h-full w-1/5 rounded-full bg-blue-700" />
                </div>

                <form onSubmit={submit} noValidate className="grid gap-6">
                    <Card className="gap-6 shadow-none">
                        <CardHeader className="flex-row items-center gap-3">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                                <Building2
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                            <div>
                                <CardTitle>Identificação</CardTitle>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Nome e canais de contato do estabelecimento.
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2">
                            <Field
                                label="Nome comercial"
                                error={form.errors.name}
                                className="sm:col-span-2"
                            >
                                <Input
                                    id="name"
                                    className={fieldClass}
                                    value={form.data.name}
                                    onChange={(e) =>
                                        form.setData('name', e.target.value)
                                    }
                                    autoComplete="organization"
                                    required
                                />
                            </Field>
                            <Field label="Segmento" error={form.errors.segment}>
                                <select
                                    id="segment"
                                    className="min-h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
                                    value={form.data.segment}
                                    onChange={(e) =>
                                        form.setData('segment', e.target.value)
                                    }
                                    required
                                >
                                    <option value="">Selecione</option>
                                    <option value="beauty">
                                        Beleza e estética
                                    </option>
                                    <option value="barbershop">
                                        Barbearia
                                    </option>
                                    <option value="health">
                                        Saúde e consultório
                                    </option>
                                    <option value="wellness">Bem-estar</option>
                                    <option value="fitness">
                                        Atividade física
                                    </option>
                                    <option value="other">Outro</option>
                                </select>
                            </Field>
                            <Field label="Telefone" error={form.errors.phone}>
                                <Input
                                    id="phone"
                                    type="tel"
                                    className={fieldClass}
                                    value={form.data.phone}
                                    onChange={(e) =>
                                        form.setData('phone', e.target.value)
                                    }
                                    autoComplete="tel"
                                    required
                                />
                            </Field>
                            <Field
                                label="WhatsApp"
                                error={form.errors.whatsapp}
                            >
                                <Input
                                    id="whatsapp"
                                    type="tel"
                                    className={fieldClass}
                                    value={form.data.whatsapp}
                                    onChange={(e) =>
                                        form.setData('whatsapp', e.target.value)
                                    }
                                    autoComplete="tel"
                                    required
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Card className="gap-6 shadow-none">
                        <CardHeader className="flex-row items-center gap-3">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                                <MapPin className="size-5" aria-hidden="true" />
                            </span>
                            <div>
                                <CardTitle>Localização</CardTitle>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Endereço que seus clientes verão após
                                    agendar.
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-6">
                            <Field
                                label="CEP"
                                error={form.errors.postal_code}
                                className="sm:col-span-2"
                            >
                                <Input
                                    id="postal_code"
                                    className={fieldClass}
                                    value={form.data.postal_code}
                                    onChange={(e) =>
                                        form.setData(
                                            'postal_code',
                                            e.target.value,
                                        )
                                    }
                                    autoComplete="postal-code"
                                    required
                                />
                            </Field>
                            <Field
                                label="Endereço"
                                error={form.errors.address}
                                className="sm:col-span-4"
                            >
                                <Input
                                    id="address"
                                    className={fieldClass}
                                    value={form.data.address}
                                    onChange={(e) =>
                                        form.setData('address', e.target.value)
                                    }
                                    autoComplete="street-address"
                                    required
                                />
                            </Field>
                            <Field
                                label="Número"
                                error={form.errors.address_number}
                                className="sm:col-span-2"
                            >
                                <Input
                                    id="address_number"
                                    className={fieldClass}
                                    value={form.data.address_number}
                                    onChange={(e) =>
                                        form.setData(
                                            'address_number',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </Field>
                            <Field
                                label="Complemento"
                                error={form.errors.address_complement}
                                className="sm:col-span-4"
                                optional
                            >
                                <Input
                                    id="address_complement"
                                    className={fieldClass}
                                    value={form.data.address_complement}
                                    onChange={(e) =>
                                        form.setData(
                                            'address_complement',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Cidade"
                                error={form.errors.city}
                                className="sm:col-span-4"
                            >
                                <Input
                                    id="city"
                                    className={fieldClass}
                                    value={form.data.city}
                                    onChange={(e) =>
                                        form.setData('city', e.target.value)
                                    }
                                    autoComplete="address-level2"
                                    required
                                />
                            </Field>
                            <Field
                                label="UF"
                                error={form.errors.state}
                                className="sm:col-span-2"
                            >
                                <Input
                                    id="state"
                                    className={fieldClass}
                                    maxLength={2}
                                    value={form.data.state}
                                    onChange={(e) =>
                                        form.setData(
                                            'state',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    autoComplete="address-level1"
                                    required
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Card className="gap-6 shadow-none">
                        <CardHeader className="flex-row items-center gap-3">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                                <Clock3 className="size-5" aria-hidden="true" />
                            </span>
                            <div>
                                <CardTitle>Regras iniciais</CardTitle>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Padrões usados futuramente pelo motor de
                                    disponibilidade.
                                </p>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            <Field
                                label="Fuso horário"
                                error={form.errors.timezone}
                                className="sm:col-span-2 lg:col-span-1"
                            >
                                <select
                                    id="timezone"
                                    className="min-h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
                                    value={form.data.timezone}
                                    onChange={(e) =>
                                        form.setData('timezone', e.target.value)
                                    }
                                >
                                    <option value="America/Sao_Paulo">
                                        Brasília
                                    </option>
                                    <option value="America/Manaus">
                                        Manaus
                                    </option>
                                    <option value="America/Rio_Branco">
                                        Rio Branco
                                    </option>
                                    <option value="America/Noronha">
                                        Fernando de Noronha
                                    </option>
                                </select>
                            </Field>
                            <Field
                                label="Intervalos de"
                                error={form.errors.appointment_interval_minutes}
                            >
                                <select
                                    id="appointment_interval_minutes"
                                    className="min-h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
                                    value={
                                        form.data.appointment_interval_minutes
                                    }
                                    onChange={(e) =>
                                        form.setData(
                                            'appointment_interval_minutes',
                                            Number(e.target.value),
                                        )
                                    }
                                >
                                    <option value={15}>15 minutos</option>
                                    <option value={30}>30 minutos</option>
                                    <option value={60}>60 minutos</option>
                                </select>
                            </Field>
                            <Field
                                label="Antecedência mínima (min)"
                                error={form.errors.minimum_notice_minutes}
                            >
                                <Input
                                    id="minimum_notice_minutes"
                                    type="number"
                                    min={0}
                                    className={fieldClass}
                                    value={form.data.minimum_notice_minutes}
                                    onChange={(e) =>
                                        form.setData(
                                            'minimum_notice_minutes',
                                            Number(e.target.value),
                                        )
                                    }
                                    required
                                />
                            </Field>
                            <Field
                                label="Agenda aberta por (dias)"
                                error={form.errors.maximum_notice_days}
                            >
                                <Input
                                    id="maximum_notice_days"
                                    type="number"
                                    min={1}
                                    max={365}
                                    className={fieldClass}
                                    value={form.data.maximum_notice_days}
                                    onChange={(e) =>
                                        form.setData(
                                            'maximum_notice_days',
                                            Number(e.target.value),
                                        )
                                    }
                                    required
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <div className="sticky bottom-0 z-10 -mx-4 flex flex-col-reverse gap-3 border-t bg-background/95 px-4 py-4 backdrop-blur sm:static sm:mx-0 sm:flex-row sm:justify-end sm:border-0 sm:bg-transparent sm:px-0 sm:py-0 sm:backdrop-blur-none">
                        <Button
                            asChild
                            variant="outline"
                            className="min-h-11 sm:min-w-32"
                        >
                            <Link href="/dashboard">Salvar depois</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="min-h-11 sm:min-w-44"
                        >
                            {form.processing ? (
                                <Spinner />
                            ) : (
                                <Save className="size-4" aria-hidden="true" />
                            )}
                            {form.processing ? 'Salvando...' : 'Salvar dados'}
                        </Button>
                    </div>
                </form>
            </main>
        </>
    );
}

function Field({
    label,
    error,
    className,
    optional,
    children,
}: {
    label: string;
    error?: string;
    className?: string;
    optional?: boolean;
    children: React.ReactNode;
}) {
    const childId = (children as React.ReactElement<{ id?: string }>).props.id;

    return (
        <div className={`grid content-start gap-2 ${className ?? ''}`}>
            <Label htmlFor={childId}>
                {label}
                {optional && (
                    <span className="ml-1 font-normal text-muted-foreground">
                        (opcional)
                    </span>
                )}
            </Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

Onboarding.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Configuração', href: '/onboarding' },
    ],
};
