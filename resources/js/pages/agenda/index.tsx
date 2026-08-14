import { Head, router, useForm } from '@inertiajs/react';
import {
    Ban,
    CalendarDays,
    Check,
    ChevronLeft,
    ChevronRight,
    Clock3,
    LoaderCircle,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
    UserRound,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useRef, useState } from 'react';
import { FormField } from '@/components/resources/form-field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type Day = {
    date: string;
    weekday: string;
    day: string;
    month: string;
    is_today: boolean;
};

type AppointmentStatus =
    | 'pending'
    | 'awaiting_confirmation'
    | 'confirmed'
    | 'cancelled'
    | 'completed'
    | 'no_show';

type Appointment = {
    uuid: string;
    date: string;
    time: string;
    end_time: string;
    status: AppointmentStatus;
    status_label: string;
    notes: string | null;
    cancellation_reason: string | null;
    customer: {
        uuid: string;
        name: string;
        phone: string;
        whatsapp: string | null;
        email: string | null;
    };
    professional: { uuid: string; name: string };
    service: { uuid: string; name: string; category: string | null };
    can_edit: boolean;
    can_update_status: boolean;
    available_statuses: Array<{ value: string; label: string }>;
    events: Array<{ type: string; actor: string | null; created_at: string }>;
};

type BlockedPeriod = {
    uuid: string;
    date: string;
    starts_at: string;
    ends_at: string;
    time: string;
    end_time: string;
    reason: string;
    professional: { uuid: string; name: string } | null;
};

type Professional = {
    uuid: string;
    name: string;
    service_uuids: string[];
};

type Service = {
    uuid: string;
    name: string;
    duration_minutes: number;
    price: string;
};

type Customer = { uuid: string; name: string; phone: string };
type Slot = { value: string; label: string; starts_at: string };

type AppointmentForm = {
    customer_mode: 'existing' | 'new';
    customer_uuid: string;
    new_customer: {
        name: string;
        phone: string;
        whatsapp: string;
        email: string;
    };
    professional_uuid: string;
    service_uuid: string;
    date: string;
    time: string;
    notes: string;
};

const emptyAppointment = (date: string): AppointmentForm => ({
    customer_mode: 'existing',
    customer_uuid: '',
    new_customer: { name: '', phone: '', whatsapp: '', email: '' },
    professional_uuid: '',
    service_uuid: '',
    date,
    time: '',
    notes: '',
});

const statusStyles: Record<AppointmentStatus, string> = {
    pending:
        'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/45 dark:text-amber-200',
    awaiting_confirmation:
        'border-violet-200 bg-violet-50 text-violet-900 dark:border-violet-900 dark:bg-violet-950/45 dark:text-violet-200',
    confirmed:
        'border-blue-200 bg-blue-50 text-blue-900 dark:border-blue-900 dark:bg-blue-950/45 dark:text-blue-200',
    cancelled: 'border-border bg-muted/60 text-muted-foreground line-through',
    completed:
        'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/45 dark:text-emerald-200',
    no_show:
        'border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/45 dark:text-rose-200',
};

function shiftDate(value: string, days: number): string {
    const date = new Date(`${value}T12:00:00`);

    date.setDate(date.getDate() + days);

    return date.toISOString().slice(0, 10);
}

export default function AgendaIndex({
    view,
    selectedDate,
    periodLabel,
    days,
    appointments,
    blocks,
    professionals,
    services,
    customers,
    selectedProfessional,
    canManage,
    timezone,
}: {
    view: 'day' | 'week';
    selectedDate: string;
    periodLabel: string;
    days: Day[];
    appointments: Appointment[];
    blocks: BlockedPeriod[];
    professionals: Professional[];
    services: Service[];
    customers: Customer[];
    selectedProfessional: string | null;
    canManage: boolean;
    timezone: string;
}) {
    const [appointmentOpen, setAppointmentOpen] = useState(false);
    const [blockOpen, setBlockOpen] = useState(false);
    const [details, setDetails] = useState<Appointment | null>(null);
    const [editing, setEditing] = useState<Appointment | null>(null);
    const [slots, setSlots] = useState<Slot[]>([]);
    const [loadingSlots, setLoadingSlots] = useState(false);
    const slotRequest = useRef(0);
    const form = useForm<AppointmentForm>(emptyAppointment(selectedDate));
    const blockForm = useForm({
        professional_uuid: selectedProfessional ?? '',
        starts_at: `${selectedDate}T09:00`,
        ends_at: `${selectedDate}T10:00`,
        reason: '',
    });

    const filteredServices = useMemo(() => {
        const professional = professionals.find(
            (item) => item.uuid === form.data.professional_uuid,
        );

        return professional
            ? services.filter((service) =>
                  professional.service_uuids.includes(service.uuid),
              )
            : services;
    }, [form.data.professional_uuid, professionals, services]);

    const loadSlots = async (
        professional: string,
        service: string,
        date: string,
        exclude?: string,
    ) => {
        const request = ++slotRequest.current;

        if (!professional || !service || !date) {
            setSlots([]);

            return;
        }

        const params = new URLSearchParams({
            professional,
            service,
            date,
        });

        if (exclude) {
            params.set('exclude', exclude);
        }

        setLoadingSlots(true);

        try {
            const response = await fetch(
                `/agenda/disponibilidade?${params.toString()}`,
                { headers: { Accept: 'application/json' } },
            );

            if (!response.ok) {
                throw new Error('availability');
            }

            const data = (await response.json()) as { slots: Slot[] };

            if (request === slotRequest.current) {
                setSlots(data.slots);
            }
        } catch {
            if (request === slotRequest.current) {
                setSlots([]);
            }
        } finally {
            if (request === slotRequest.current) {
                setLoadingSlots(false);
            }
        }
    };

    const navigate = (
        date: string,
        nextView = view,
        professional = selectedProfessional,
    ) => {
        router.get(
            '/agenda',
            {
                date,
                view: nextView,
                ...(professional ? { professional } : {}),
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const openCreate = (date = selectedDate) => {
        setEditing(null);
        form.setData({
            ...emptyAppointment(date),
            professional_uuid:
                selectedProfessional ??
                (professionals.length === 1 ? professionals[0].uuid : ''),
        });
        form.clearErrors();
        setSlots([]);
        setAppointmentOpen(true);
    };

    const openEdit = (appointment: Appointment) => {
        setDetails(null);
        setEditing(appointment);
        form.setData({
            ...emptyAppointment(appointment.date),
            customer_uuid: appointment.customer.uuid,
            professional_uuid: appointment.professional.uuid,
            service_uuid: appointment.service.uuid,
            time: appointment.time,
            notes: appointment.notes ?? '',
        });
        form.clearErrors();
        void loadSlots(
            appointment.professional.uuid,
            appointment.service.uuid,
            appointment.date,
            appointment.uuid,
        );
        setAppointmentOpen(true);
    };

    const submitAppointment = (event: FormEvent) => {
        event.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => setAppointmentOpen(false),
        };

        if (editing) {
            form.patch(`/agenda/agendamentos/${editing.uuid}`, options);
        } else {
            form.post('/agenda/agendamentos', options);
        }
    };

    const submitBlock = (event: FormEvent) => {
        event.preventDefault();
        blockForm.post('/agenda/bloqueios', {
            preserveScroll: true,
            onSuccess: () => {
                setBlockOpen(false);
                blockForm.reset('reason');
            },
        });
    };

    const updateStatus = (appointment: Appointment, status: string) => {
        router.patch(
            `/agenda/agendamentos/${appointment.uuid}/status`,
            {
                status,
                reason:
                    status === 'cancelled' ? 'Cancelado pela equipe.' : null,
            },
            {
                preserveScroll: true,
                onSuccess: () => setDetails(null),
            },
        );
    };

    const today = new Intl.DateTimeFormat('en-CA', {
        timeZone: timezone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date());

    return (
        <>
            <Head title="Agenda" />
            <main
                id="main-content"
                className="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8"
            >
                <section className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-sm font-medium text-blue-700 dark:text-blue-300">
                            <CalendarDays
                                className="size-4"
                                aria-hidden="true"
                            />
                            Operação diária
                        </div>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                            Agenda
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Agendamentos no fuso {timezone}. Horários ocupados
                            incluem o intervalo do serviço.
                        </p>
                    </div>
                    {canManage && (
                        <div className="grid grid-cols-2 gap-2 sm:flex">
                            <Button
                                variant="outline"
                                className="min-h-11"
                                onClick={() => setBlockOpen(true)}
                            >
                                <Ban className="size-4" aria-hidden="true" />
                                Bloquear
                            </Button>
                            <Button
                                className="min-h-11"
                                onClick={() => openCreate()}
                            >
                                <Plus className="size-4" aria-hidden="true" />
                                Agendar
                            </Button>
                        </div>
                    )}
                </section>

                <Card className="gap-0 py-0 shadow-none">
                    <CardContent className="flex flex-col gap-3 p-3 sm:p-4 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="icon"
                                className="min-h-11 min-w-11"
                                onClick={() =>
                                    navigate(
                                        shiftDate(
                                            selectedDate,
                                            view === 'week' ? -7 : -1,
                                        ),
                                    )
                                }
                                aria-label={
                                    view === 'week'
                                        ? 'Semana anterior'
                                        : 'Dia anterior'
                                }
                            >
                                <ChevronLeft
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </Button>
                            <Button
                                variant="outline"
                                className="min-h-11"
                                onClick={() => navigate(today)}
                            >
                                Hoje
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                className="min-h-11 min-w-11"
                                onClick={() =>
                                    navigate(
                                        shiftDate(
                                            selectedDate,
                                            view === 'week' ? 7 : 1,
                                        ),
                                    )
                                }
                                aria-label={
                                    view === 'week'
                                        ? 'Próxima semana'
                                        : 'Próximo dia'
                                }
                            >
                                <ChevronRight
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </Button>
                            <p className="ml-1 text-sm font-semibold sm:text-base">
                                {periodLabel}
                            </p>
                        </div>
                        <div className="grid gap-2 sm:grid-cols-2 lg:flex">
                            {canManage && professionals.length > 1 && (
                                <Select
                                    value={selectedProfessional ?? 'all'}
                                    onValueChange={(value) =>
                                        navigate(
                                            selectedDate,
                                            view,
                                            value === 'all' ? null : value,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        className="min-h-11 w-full lg:w-52"
                                        aria-label="Filtrar profissional"
                                    >
                                        <SelectValue placeholder="Toda a equipe" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Toda a equipe
                                        </SelectItem>
                                        {professionals.map((professional) => (
                                            <SelectItem
                                                key={professional.uuid}
                                                value={professional.uuid}
                                            >
                                                {professional.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}
                            <div
                                className="grid grid-cols-2 rounded-lg border p-1"
                                aria-label="Visualização da agenda"
                            >
                                <Button
                                    variant={
                                        view === 'day' ? 'secondary' : 'ghost'
                                    }
                                    size="sm"
                                    className="min-h-11"
                                    onClick={() =>
                                        navigate(selectedDate, 'day')
                                    }
                                    aria-pressed={view === 'day'}
                                >
                                    Dia
                                </Button>
                                <Button
                                    variant={
                                        view === 'week' ? 'secondary' : 'ghost'
                                    }
                                    size="sm"
                                    className="min-h-11"
                                    onClick={() =>
                                        navigate(selectedDate, 'week')
                                    }
                                    aria-pressed={view === 'week'}
                                >
                                    Semana
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <section
                    aria-label={
                        view === 'week' ? 'Agenda da semana' : 'Agenda do dia'
                    }
                    className={cn(
                        'grid items-start gap-3',
                        view === 'week' &&
                            'md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7',
                    )}
                >
                    {days.map((day) => {
                        const dayAppointments = appointments.filter(
                            (item) => item.date === day.date,
                        );
                        const dayBlocks = blocks.filter(
                            (item) => item.date === day.date,
                        );

                        return (
                            <Card
                                key={day.date}
                                className={cn(
                                    'min-w-0 gap-0 overflow-hidden py-0 shadow-none',
                                    day.is_today &&
                                        'border-blue-300 ring-1 ring-blue-100 dark:border-blue-800 dark:ring-blue-950',
                                )}
                            >
                                <CardHeader
                                    className={cn(
                                        'border-b px-4 py-3',
                                        day.is_today &&
                                            'bg-blue-50/70 dark:bg-blue-950/30',
                                    )}
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <div className="flex items-baseline gap-2">
                                            <CardTitle className="text-sm">
                                                {day.weekday}
                                            </CardTitle>
                                            <span className="text-lg font-semibold tabular-nums">
                                                {day.day}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {day.month}
                                            </span>
                                        </div>
                                        {day.is_today && (
                                            <Badge className="bg-blue-700 text-white">
                                                Hoje
                                            </Badge>
                                        )}
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-2 p-2">
                                    {dayBlocks.map((block) => (
                                        <div
                                            key={block.uuid}
                                            className="rounded-md border border-dashed bg-muted/60 p-3 text-xs"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <p className="font-semibold tabular-nums">
                                                        {block.time}–
                                                        {block.end_time}
                                                    </p>
                                                    <p className="mt-1 text-muted-foreground">
                                                        {block.reason}
                                                    </p>
                                                    {block.professional && (
                                                        <p className="mt-1 text-muted-foreground">
                                                            {
                                                                block
                                                                    .professional
                                                                    .name
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                                {canManage && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="-m-2 min-h-11 min-w-11"
                                                        onClick={() =>
                                                            router.delete(
                                                                `/agenda/bloqueios/${block.uuid}`,
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                        aria-label={`Remover bloqueio ${block.reason}`}
                                                    >
                                                        <Trash2
                                                            className="size-4"
                                                            aria-hidden="true"
                                                        />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                    {dayAppointments.map((appointment) => (
                                        <button
                                            type="button"
                                            key={appointment.uuid}
                                            onClick={() =>
                                                setDetails(appointment)
                                            }
                                            className={cn(
                                                'w-full rounded-md border p-3 text-left transition-colors hover:brightness-95 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none dark:hover:brightness-110',
                                                statusStyles[
                                                    appointment.status
                                                ],
                                            )}
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <span className="text-sm font-bold tabular-nums">
                                                    {appointment.time}
                                                </span>
                                                <span className="text-[10px] font-semibold tracking-wide uppercase">
                                                    {appointment.status_label}
                                                </span>
                                            </div>
                                            <p className="mt-2 truncate text-sm font-semibold">
                                                {appointment.customer.name}
                                            </p>
                                            <p className="mt-0.5 truncate text-xs opacity-80">
                                                {appointment.service.name}
                                            </p>
                                            <p className="mt-2 flex items-center gap-1 truncate text-xs opacity-75">
                                                <UserRound
                                                    className="size-3"
                                                    aria-hidden="true"
                                                />
                                                {appointment.professional.name}
                                            </p>
                                        </button>
                                    ))}
                                    {dayAppointments.length === 0 &&
                                        dayBlocks.length === 0 && (
                                            <div className="flex min-h-24 flex-col items-center justify-center rounded-md border border-dashed px-3 text-center text-xs text-muted-foreground">
                                                <Clock3
                                                    className="mb-2 size-4"
                                                    aria-hidden="true"
                                                />
                                                Horário livre
                                                {canManage && (
                                                    <Button
                                                        variant="link"
                                                        size="sm"
                                                        className="min-h-11 text-xs"
                                                        onClick={() =>
                                                            openCreate(day.date)
                                                        }
                                                    >
                                                        Criar agendamento
                                                    </Button>
                                                )}
                                            </div>
                                        )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </section>
            </main>

            <Dialog open={appointmentOpen} onOpenChange={setAppointmentOpen}>
                <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing
                                ? 'Editar agendamento'
                                : 'Novo agendamento'}
                        </DialogTitle>
                        <DialogDescription>
                            Escolha serviço e profissional para consultar apenas
                            os horários realmente livres.
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        id="appointment-form"
                        onSubmit={submitAppointment}
                        className="grid gap-5"
                    >
                        {!editing && (
                            <div
                                className="grid grid-cols-2 rounded-lg border p-1"
                                aria-label="Tipo de cliente"
                            >
                                <Button
                                    type="button"
                                    variant={
                                        form.data.customer_mode === 'existing'
                                            ? 'secondary'
                                            : 'ghost'
                                    }
                                    onClick={() =>
                                        form.setData(
                                            'customer_mode',
                                            'existing',
                                        )
                                    }
                                    aria-pressed={
                                        form.data.customer_mode === 'existing'
                                    }
                                >
                                    Cliente cadastrado
                                </Button>
                                <Button
                                    type="button"
                                    variant={
                                        form.data.customer_mode === 'new'
                                            ? 'secondary'
                                            : 'ghost'
                                    }
                                    onClick={() =>
                                        form.setData('customer_mode', 'new')
                                    }
                                    aria-pressed={
                                        form.data.customer_mode === 'new'
                                    }
                                >
                                    Novo cliente
                                </Button>
                            </div>
                        )}
                        {form.data.customer_mode === 'existing' ? (
                            <FormField
                                htmlFor="appointment-customer"
                                label="Cliente"
                                required
                                error={form.errors.customer_uuid}
                            >
                                <Select
                                    value={form.data.customer_uuid}
                                    onValueChange={(value) =>
                                        form.setData('customer_uuid', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="appointment-customer"
                                        className="min-h-11 w-full"
                                        aria-invalid={Boolean(
                                            form.errors.customer_uuid,
                                        )}
                                    >
                                        <SelectValue placeholder="Selecione o cliente" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {customers.map((customer) => (
                                            <SelectItem
                                                key={customer.uuid}
                                                value={customer.uuid}
                                            >
                                                {customer.name} ·{' '}
                                                {customer.phone}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                        ) : (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <FormField
                                    htmlFor="new-customer-name"
                                    label="Nome"
                                    required
                                    error={form.errors['new_customer.name']}
                                >
                                    <Input
                                        id="new-customer-name"
                                        className="min-h-11"
                                        value={form.data.new_customer.name}
                                        onChange={(event) =>
                                            form.setData('new_customer', {
                                                ...form.data.new_customer,
                                                name: event.target.value,
                                            })
                                        }
                                    />
                                </FormField>
                                <FormField
                                    htmlFor="new-customer-phone"
                                    label="Telefone"
                                    required
                                    error={form.errors['new_customer.phone']}
                                >
                                    <Input
                                        id="new-customer-phone"
                                        type="tel"
                                        className="min-h-11"
                                        value={form.data.new_customer.phone}
                                        onChange={(event) =>
                                            form.setData('new_customer', {
                                                ...form.data.new_customer,
                                                phone: event.target.value,
                                            })
                                        }
                                    />
                                </FormField>
                                <FormField
                                    htmlFor="new-customer-whatsapp"
                                    label="WhatsApp"
                                    error={form.errors['new_customer.whatsapp']}
                                >
                                    <Input
                                        id="new-customer-whatsapp"
                                        type="tel"
                                        className="min-h-11"
                                        value={form.data.new_customer.whatsapp}
                                        onChange={(event) =>
                                            form.setData('new_customer', {
                                                ...form.data.new_customer,
                                                whatsapp: event.target.value,
                                            })
                                        }
                                    />
                                </FormField>
                                <FormField
                                    htmlFor="new-customer-email"
                                    label="E-mail"
                                    error={form.errors['new_customer.email']}
                                >
                                    <Input
                                        id="new-customer-email"
                                        type="email"
                                        className="min-h-11"
                                        value={form.data.new_customer.email}
                                        onChange={(event) =>
                                            form.setData('new_customer', {
                                                ...form.data.new_customer,
                                                email: event.target.value,
                                            })
                                        }
                                    />
                                </FormField>
                            </div>
                        )}
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                htmlFor="appointment-professional"
                                label="Profissional"
                                required
                                error={form.errors.professional_uuid}
                            >
                                <Select
                                    value={form.data.professional_uuid}
                                    onValueChange={(value) => {
                                        form.setData((data) => ({
                                            ...data,
                                            professional_uuid: value,
                                            service_uuid: '',
                                            time: '',
                                        }));
                                        setSlots([]);
                                    }}
                                >
                                    <SelectTrigger
                                        id="appointment-professional"
                                        className="min-h-11 w-full"
                                    >
                                        <SelectValue placeholder="Selecione" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {professionals.map((professional) => (
                                            <SelectItem
                                                key={professional.uuid}
                                                value={professional.uuid}
                                            >
                                                {professional.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <FormField
                                htmlFor="appointment-service"
                                label="Serviço"
                                required
                                error={form.errors.service_uuid}
                            >
                                <Select
                                    value={form.data.service_uuid}
                                    onValueChange={(value) => {
                                        form.setData((data) => ({
                                            ...data,
                                            service_uuid: value,
                                            time: '',
                                        }));
                                        void loadSlots(
                                            form.data.professional_uuid,
                                            value,
                                            form.data.date,
                                            editing?.uuid,
                                        );
                                    }}
                                    disabled={!form.data.professional_uuid}
                                >
                                    <SelectTrigger
                                        id="appointment-service"
                                        className="min-h-11 w-full"
                                    >
                                        <SelectValue placeholder="Selecione" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {filteredServices.map((service) => (
                                            <SelectItem
                                                key={service.uuid}
                                                value={service.uuid}
                                            >
                                                {service.name} ·{' '}
                                                {service.duration_minutes} min
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <FormField
                                htmlFor="appointment-date"
                                label="Data"
                                required
                                error={form.errors.date}
                            >
                                <Input
                                    id="appointment-date"
                                    type="date"
                                    className="min-h-11"
                                    value={form.data.date}
                                    onChange={(event) => {
                                        const date = event.target.value;

                                        form.setData((data) => ({
                                            ...data,
                                            date,
                                            time: '',
                                        }));
                                        void loadSlots(
                                            form.data.professional_uuid,
                                            form.data.service_uuid,
                                            date,
                                            editing?.uuid,
                                        );
                                    }}
                                />
                            </FormField>
                            <FormField
                                htmlFor="appointment-time"
                                label="Horário"
                                required
                                error={form.errors.time}
                                hint={
                                    !loadingSlots &&
                                    form.data.service_uuid &&
                                    slots.length === 0
                                        ? 'Nenhum horário disponível nesta data.'
                                        : undefined
                                }
                            >
                                <Select
                                    value={form.data.time}
                                    onValueChange={(value) =>
                                        form.setData('time', value)
                                    }
                                    disabled={
                                        loadingSlots || slots.length === 0
                                    }
                                >
                                    <SelectTrigger
                                        id="appointment-time"
                                        className="min-h-11 w-full"
                                    >
                                        {loadingSlots ? (
                                            <span className="flex items-center gap-2">
                                                <LoaderCircle className="size-4 animate-spin" />{' '}
                                                Consultando
                                            </span>
                                        ) : (
                                            <SelectValue placeholder="Selecione" />
                                        )}
                                    </SelectTrigger>
                                    <SelectContent>
                                        {slots.map((slot) => (
                                            <SelectItem
                                                key={slot.starts_at}
                                                value={slot.value}
                                            >
                                                {slot.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                        </div>
                        <FormField
                            htmlFor="appointment-notes"
                            label="Observações"
                            error={form.errors.notes}
                        >
                            <textarea
                                id="appointment-notes"
                                className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                                value={form.data.notes}
                                onChange={(event) =>
                                    form.setData('notes', event.target.value)
                                }
                            />
                        </FormField>
                    </form>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            className="min-h-11"
                            onClick={() => setAppointmentOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            form="appointment-form"
                            className="min-h-11"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {editing
                                ? 'Salvar alterações'
                                : 'Criar agendamento'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={blockOpen} onOpenChange={setBlockOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Bloquear horário</DialogTitle>
                        <DialogDescription>
                            Use para pausas, reuniões, feriados ou qualquer
                            período sem atendimento.
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        id="block-form"
                        onSubmit={submitBlock}
                        className="grid gap-4"
                    >
                        <FormField
                            htmlFor="block-professional"
                            label="Abrangência"
                            error={blockForm.errors.professional_uuid}
                        >
                            <Select
                                value={
                                    blockForm.data.professional_uuid || 'all'
                                }
                                onValueChange={(value) =>
                                    blockForm.setData(
                                        'professional_uuid',
                                        value === 'all' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="block-professional"
                                    className="min-h-11 w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Empresa inteira
                                    </SelectItem>
                                    {professionals.map((professional) => (
                                        <SelectItem
                                            key={professional.uuid}
                                            value={professional.uuid}
                                        >
                                            {professional.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </FormField>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <FormField
                                htmlFor="block-start"
                                label="Início"
                                required
                                error={blockForm.errors.starts_at}
                            >
                                <Input
                                    id="block-start"
                                    type="datetime-local"
                                    className="min-h-11"
                                    value={blockForm.data.starts_at}
                                    onChange={(event) =>
                                        blockForm.setData(
                                            'starts_at',
                                            event.target.value,
                                        )
                                    }
                                />
                            </FormField>
                            <FormField
                                htmlFor="block-end"
                                label="Fim"
                                required
                                error={blockForm.errors.ends_at}
                            >
                                <Input
                                    id="block-end"
                                    type="datetime-local"
                                    className="min-h-11"
                                    value={blockForm.data.ends_at}
                                    onChange={(event) =>
                                        blockForm.setData(
                                            'ends_at',
                                            event.target.value,
                                        )
                                    }
                                />
                            </FormField>
                        </div>
                        <FormField
                            htmlFor="block-reason"
                            label="Motivo"
                            required
                            error={blockForm.errors.reason}
                        >
                            <Input
                                id="block-reason"
                                className="min-h-11"
                                placeholder="Ex.: reunião da equipe"
                                value={blockForm.data.reason}
                                onChange={(event) =>
                                    blockForm.setData(
                                        'reason',
                                        event.target.value,
                                    )
                                }
                            />
                        </FormField>
                    </form>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            className="min-h-11"
                            onClick={() => setBlockOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            form="block-form"
                            className="min-h-11"
                            disabled={blockForm.processing}
                        >
                            {blockForm.processing && <Spinner />}Bloquear
                            período
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={details !== null}
                onOpenChange={(open) => !open && setDetails(null)}
            >
                {details && (
                    <DialogContent>
                        <DialogHeader>
                            <div className="flex items-center gap-2 pr-8">
                                <DialogTitle>
                                    {details.customer.name}
                                </DialogTitle>
                                <Badge
                                    variant="outline"
                                    className={statusStyles[details.status]}
                                >
                                    {details.status_label}
                                </Badge>
                            </div>
                            <DialogDescription>
                                {details.service.name} com{' '}
                                {details.professional.name}
                            </DialogDescription>
                        </DialogHeader>
                        <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-3 rounded-lg border bg-muted/25 p-4 text-sm">
                            <dt className="text-muted-foreground">Horário</dt>
                            <dd className="font-medium tabular-nums">
                                {details.time}–{details.end_time}
                            </dd>
                            <dt className="text-muted-foreground">Telefone</dt>
                            <dd className="font-medium">
                                {details.customer.phone}
                            </dd>
                            {details.notes && (
                                <>
                                    <dt className="text-muted-foreground">
                                        Observações
                                    </dt>
                                    <dd>{details.notes}</dd>
                                </>
                            )}
                        </dl>
                        {details.can_update_status &&
                            details.available_statuses.length > 0 && (
                                <div>
                                    <p className="mb-2 text-sm font-medium">
                                        Atualizar atendimento
                                    </p>
                                    <div className="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                                        {details.available_statuses.map(
                                            (status) => (
                                                <Button
                                                    key={status.value}
                                                    variant={
                                                        status.value ===
                                                        'cancelled'
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                    className="min-h-11"
                                                    onClick={() =>
                                                        updateStatus(
                                                            details,
                                                            status.value,
                                                        )
                                                    }
                                                >
                                                    {status.value ===
                                                        'confirmed' ||
                                                    status.value ===
                                                        'completed' ? (
                                                        <Check className="size-4" />
                                                    ) : null}
                                                    {status.label}
                                                </Button>
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}
                        <DialogFooter>
                            {details.can_edit && (
                                <Button
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() => openEdit(details)}
                                >
                                    <Pencil
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Editar
                                </Button>
                            )}
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        className="min-h-11"
                                    >
                                        <MoreHorizontal className="size-4" />
                                        Mais detalhes
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem disabled>
                                        Histórico: {details.events.length}{' '}
                                        eventos
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </DialogFooter>
                    </DialogContent>
                )}
            </Dialog>
        </>
    );
}
