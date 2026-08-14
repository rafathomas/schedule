import { Head, router, useForm } from '@inertiajs/react';
import { Building2, Clock3, Save, UserRound } from 'lucide-react';
import type { FormEvent } from 'react';
import { FormField } from '@/components/resources/form-field';
import { ResourcePageHeader } from '@/components/resources/resource-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { WorkingHour } from '@/types';

type ProfessionalOption = {
    uuid: string;
    name: string;
    status: 'active' | 'inactive';
};
type SelectedProfessional = Pick<ProfessionalOption, 'uuid' | 'name'>;

const dayNames = [
    'Domingo',
    'Segunda-feira',
    'Terça-feira',
    'Quarta-feira',
    'Quinta-feira',
    'Sexta-feira',
    'Sábado',
];

export default function HoursIndex({
    companyHours,
    professionals,
    selectedProfessional,
    professionalHours,
    usesCompanyHours,
    canManage,
}: {
    companyHours: WorkingHour[];
    professionals: ProfessionalOption[];
    selectedProfessional: SelectedProfessional | null;
    professionalHours: WorkingHour[] | null;
    usesCompanyHours: boolean;
    canManage: boolean;
}) {
    const hours =
        selectedProfessional && professionalHours
            ? professionalHours
            : companyHours;

    return (
        <>
            <Head title="Horários de atendimento" />
            <main
                id="main-content"
                className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
            >
                <ResourcePageHeader
                    icon={Clock3}
                    eyebrow="Configurações"
                    title="Horários de atendimento"
                    description="Defina a jornada padrão da empresa e crie exceções semanais por profissional."
                />

                <Card className="gap-4 py-5 shadow-none">
                    <CardHeader className="px-5 sm:px-6">
                        <CardTitle className="text-base">
                            Agenda que você está editando
                        </CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Escolha a empresa ou um profissional. Nenhum horário
                            de agendamento é criado nesta etapa.
                        </p>
                    </CardHeader>
                    <CardContent className="px-5 sm:px-6">
                        <label htmlFor="schedule-owner" className="sr-only">
                            Agenda
                        </label>
                        <select
                            id="schedule-owner"
                            value={selectedProfessional?.uuid ?? 'company'}
                            onChange={(event) =>
                                router.get(
                                    '/horarios',
                                    event.target.value === 'company'
                                        ? {}
                                        : { professional: event.target.value },
                                    { preserveState: false },
                                )
                            }
                            className="min-h-11 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 sm:max-w-md"
                        >
                            <option value="company">Studio / empresa</option>
                            {professionals.map((professional) => (
                                <option
                                    key={professional.uuid}
                                    value={professional.uuid}
                                >
                                    {professional.name}
                                    {professional.status === 'inactive'
                                        ? ' — inativo'
                                        : ''}
                                </option>
                            ))}
                        </select>
                    </CardContent>
                </Card>

                <HoursEditor
                    key={selectedProfessional?.uuid ?? 'company'}
                    hours={hours}
                    selectedProfessional={selectedProfessional}
                    usesCompanyHours={usesCompanyHours}
                    canManage={canManage}
                />
            </main>
        </>
    );
}

function HoursEditor({
    hours,
    selectedProfessional,
    usesCompanyHours,
    canManage,
}: {
    hours: WorkingHour[];
    selectedProfessional: SelectedProfessional | null;
    usesCompanyHours: boolean;
    canManage: boolean;
}) {
    const form = useForm<{ hours: WorkingHour[] }>({ hours });
    const title = selectedProfessional
        ? selectedProfessional.name
        : 'Horário padrão da empresa';
    const errors = form.errors as Record<string, string>;

    const update = <K extends keyof WorkingHour>(
        index: number,
        key: K,
        value: WorkingHour[K],
    ) => {
        const next = form.data.hours.map((hour, hourIndex) =>
            hourIndex === index ? { ...hour, [key]: value } : hour,
        );
        form.setData('hours', next);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const url = selectedProfessional
            ? `/horarios/profissionais/${selectedProfessional.uuid}`
            : '/horarios/empresa';
        form.put(url, { preserveScroll: true });
    };

    return (
        <Card className="gap-0 overflow-hidden py-0 shadow-none">
            <CardHeader className="border-b bg-muted/30 px-5 py-5 sm:px-6">
                <div className="flex flex-wrap items-center gap-3">
                    <span className="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200">
                        {selectedProfessional ? (
                            <UserRound className="size-5" aria-hidden="true" />
                        ) : (
                            <Building2 className="size-5" aria-hidden="true" />
                        )}
                    </span>
                    <div className="min-w-0 flex-1">
                        <CardTitle className="text-base">{title}</CardTitle>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Configure atendimento e intervalo de cada dia.
                        </p>
                    </div>
                    {usesCompanyHours && (
                        <Badge variant="secondary">
                            Usando horário da empresa
                        </Badge>
                    )}
                </div>
            </CardHeader>
            <CardContent className="px-0">
                <form onSubmit={submit}>
                    <div className="divide-y">
                        {form.data.hours.map((hour, index) => (
                            <fieldset
                                key={hour.day_of_week}
                                className="grid gap-4 px-5 py-5 sm:px-6 lg:grid-cols-[11rem_1fr] lg:items-start"
                            >
                                <legend className="sr-only">
                                    {dayNames[hour.day_of_week]}
                                </legend>
                                <label className="flex min-h-11 items-center gap-3 text-sm font-medium">
                                    <Checkbox
                                        checked={!hour.is_closed}
                                        disabled={!canManage}
                                        onCheckedChange={(checked) =>
                                            update(
                                                index,
                                                'is_closed',
                                                checked !== true,
                                            )
                                        }
                                    />
                                    <span>
                                        {dayNames[hour.day_of_week]}
                                        <span className="block text-xs font-normal text-muted-foreground">
                                            {hour.is_closed
                                                ? 'Fechado'
                                                : 'Aberto'}
                                        </span>
                                    </span>
                                </label>
                                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                    <FormField
                                        htmlFor={`starts-${index}`}
                                        label="Início"
                                        error={
                                            errors[`hours.${index}.starts_at`]
                                        }
                                    >
                                        <Input
                                            id={`starts-${index}`}
                                            type="time"
                                            value={hour.starts_at ?? ''}
                                            disabled={
                                                hour.is_closed || !canManage
                                            }
                                            onChange={(event) =>
                                                update(
                                                    index,
                                                    'starts_at',
                                                    event.target.value || null,
                                                )
                                            }
                                            className="min-h-11"
                                        />
                                    </FormField>
                                    <FormField
                                        htmlFor={`ends-${index}`}
                                        label="Fim"
                                        error={errors[`hours.${index}.ends_at`]}
                                    >
                                        <Input
                                            id={`ends-${index}`}
                                            type="time"
                                            value={hour.ends_at ?? ''}
                                            disabled={
                                                hour.is_closed || !canManage
                                            }
                                            onChange={(event) =>
                                                update(
                                                    index,
                                                    'ends_at',
                                                    event.target.value || null,
                                                )
                                            }
                                            className="min-h-11"
                                        />
                                    </FormField>
                                    <FormField
                                        htmlFor={`break-starts-${index}`}
                                        label="Início do intervalo"
                                        error={
                                            errors[
                                                `hours.${index}.break_starts_at`
                                            ]
                                        }
                                    >
                                        <Input
                                            id={`break-starts-${index}`}
                                            type="time"
                                            value={hour.break_starts_at ?? ''}
                                            disabled={
                                                hour.is_closed || !canManage
                                            }
                                            onChange={(event) =>
                                                update(
                                                    index,
                                                    'break_starts_at',
                                                    event.target.value || null,
                                                )
                                            }
                                            className="min-h-11"
                                        />
                                    </FormField>
                                    <FormField
                                        htmlFor={`break-ends-${index}`}
                                        label="Fim do intervalo"
                                        error={
                                            errors[
                                                `hours.${index}.break_ends_at`
                                            ]
                                        }
                                    >
                                        <Input
                                            id={`break-ends-${index}`}
                                            type="time"
                                            value={hour.break_ends_at ?? ''}
                                            disabled={
                                                hour.is_closed || !canManage
                                            }
                                            onChange={(event) =>
                                                update(
                                                    index,
                                                    'break_ends_at',
                                                    event.target.value || null,
                                                )
                                            }
                                            className="min-h-11"
                                        />
                                    </FormField>
                                </div>
                            </fieldset>
                        ))}
                    </div>
                    {canManage && (
                        <div className="flex justify-end border-t bg-muted/20 px-5 py-4 sm:px-6">
                            <Button
                                type="submit"
                                className="min-h-11 w-full sm:w-auto"
                                disabled={form.processing}
                            >
                                <Save className="size-4" aria-hidden="true" />
                                {form.processing && <Spinner />}Salvar horários
                            </Button>
                        </div>
                    )}
                </form>
            </CardContent>
        </Card>
    );
}
