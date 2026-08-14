import { Head, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    BellRing,
    CheckCircle2,
    Mail,
    MessageCircle,
    QrCode,
    RefreshCw,
    Save,
    Unplug,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { ResourcePageHeader } from '@/components/resources/resource-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Settings = {
    confirmation_enabled: boolean;
    confirmation_minutes_before: number;
    reminders_enabled: boolean;
    reminder_offsets: number[];
    channels: string[];
};

type Log = {
    id: number;
    type: string;
    channel: string;
    status: string;
    recipient: string | null;
    customer: string;
    service: string;
    scheduled_for: string | null;
    sent_at: string | null;
    attempt: number;
    error: string | null;
};

type WhatsAppState = {
    available: boolean;
    status: 'connected' | 'connecting' | 'disconnected' | 'error';
    instance_name: string | null;
    last_checked_at: string | null;
    last_error: string | null;
    qr_code: string | null;
};

const offsetOptions = [
    { value: 10080, label: '7 dias antes' },
    { value: 2880, label: '48 horas antes' },
    { value: 1440, label: '24 horas antes' },
    { value: 720, label: '12 horas antes' },
    { value: 120, label: '2 horas antes' },
    { value: 60, label: '1 hora antes' },
];

const statusLabel: Record<string, string> = {
    pending: 'Pendente',
    processing: 'Processando',
    sent: 'Enviado',
    failed: 'Falhou',
    skipped: 'Ignorado',
};

const whatsAppStatusLabel: Record<WhatsAppState['status'], string> = {
    connected: 'Conectado',
    connecting: 'Aguardando leitura do QR Code',
    disconnected: 'Desconectado',
    error: 'Conexão indisponível',
};

export default function CommunicationIndex({
    settings,
    logs,
    whatsapp,
}: {
    settings: Settings;
    logs: Log[];
    whatsapp: WhatsAppState;
}) {
    const form = useForm<Settings>(settings);
    const connectForm = useForm({});
    const disconnectForm = useForm({});
    const errors = form.errors as Record<string, string>;
    const [whatsAppStatus, setWhatsAppStatus] = useState(whatsapp.status);
    const [qrCode, setQrCode] = useState(whatsapp.qr_code);
    const [connectionError, setConnectionError] = useState(whatsapp.last_error);
    const [checkingConnection, setCheckingConnection] = useState(false);

    const checkWhatsAppStatus = useCallback(async () => {
        setCheckingConnection(true);

        try {
            const response = await fetch('/comunicacao/whatsapp/status', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = (await response.json()) as {
                status: WhatsAppState['status'];
                error?: string;
            };

            setWhatsAppStatus(data.status);
            setConnectionError(data.error ?? null);

            if (data.status === 'connected') {
                setQrCode(null);
            }
        } catch {
            setWhatsAppStatus('error');
            setConnectionError(
                'Não foi possível verificar a conexão neste momento.',
            );
        } finally {
            setCheckingConnection(false);
        }
    }, []);

    useEffect(() => {
        if (!qrCode || whatsAppStatus === 'connected') {
            return;
        }

        const interval = window.setInterval(() => {
            void checkWhatsAppStatus();
        }, 3000);

        return () => window.clearInterval(interval);
    }, [checkWhatsAppStatus, qrCode, whatsAppStatus]);

    const toggleArray = (
        key: 'reminder_offsets' | 'channels',
        value: number | string,
        checked: boolean,
    ) => {
        const current = form.data[key] as (number | string)[];
        const next = checked
            ? [...current, value]
            : current.filter((item) => item !== value);
        form.setData(key, next as never);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/comunicacao', { preserveScroll: true });
    };

    return (
        <>
            <Head title="Comunicação automática" />
            <main
                id="main-content"
                className="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
            >
                <ResourcePageHeader
                    icon={BellRing}
                    eyebrow="Automação"
                    title="Comunicação automática"
                    description="Configure confirmações e lembretes. Cada envio fica registrado e protegido contra duplicidade."
                />

                <Card className="shadow-none">
                    <CardHeader className="gap-3 border-b">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <MessageCircle className="size-5" />
                                    WhatsApp da empresa
                                </CardTitle>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Cada empresa conecta seu próprio número pelo
                                    WhatsApp Web.
                                </p>
                            </div>
                            <Badge
                                variant={
                                    whatsAppStatus === 'error'
                                        ? 'destructive'
                                        : 'outline'
                                }
                                className="min-h-7 w-fit gap-1.5 px-3"
                            >
                                {whatsAppStatus === 'connected' ? (
                                    <CheckCircle2 className="size-3.5" />
                                ) : (
                                    <QrCode className="size-3.5" />
                                )}
                                {whatsAppStatusLabel[whatsAppStatus]}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-5 pt-6">
                        {!whatsapp.available ? (
                            <Alert variant="destructive">
                                <AlertCircle />
                                <AlertTitle>
                                    Evolution API não configurada
                                </AlertTitle>
                                <AlertDescription>
                                    Configure EVOLUTION_API_URL e
                                    EVOLUTION_API_KEY no servidor para liberar a
                                    conexão.
                                </AlertDescription>
                            </Alert>
                        ) : qrCode ? (
                            <div className="grid items-center gap-6 md:grid-cols-[minmax(0,1fr)_280px]">
                                <div className="space-y-4">
                                    <div>
                                        <h3 className="font-medium">
                                            Escaneie para conectar
                                        </h3>
                                        <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                            No celular, abra o WhatsApp e acesse
                                            Configurações → Aparelhos conectados
                                            → Conectar um aparelho.
                                        </p>
                                    </div>
                                    <Alert>
                                        <RefreshCw
                                            className={
                                                checkingConnection
                                                    ? 'animate-spin'
                                                    : ''
                                            }
                                        />
                                        <AlertTitle>
                                            Verificação automática ativa
                                        </AlertTitle>
                                        <AlertDescription>
                                            Esta tela reconhecerá a conexão após
                                            a leitura do código.
                                        </AlertDescription>
                                    </Alert>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={() =>
                                            void checkWhatsAppStatus()
                                        }
                                        disabled={checkingConnection}
                                    >
                                        {checkingConnection ? (
                                            <Spinner />
                                        ) : (
                                            <RefreshCw className="size-4" />
                                        )}
                                        Verificar agora
                                    </Button>
                                </div>
                                <div className="mx-auto rounded-2xl border bg-white p-3 shadow-sm">
                                    <img
                                        src={qrCode}
                                        alt="QR Code para conectar o WhatsApp da empresa"
                                        width={256}
                                        height={256}
                                        className="size-64 max-w-full"
                                    />
                                </div>
                            </div>
                        ) : (
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="max-w-2xl">
                                    <p className="text-sm font-medium">
                                        {whatsAppStatus === 'connected'
                                            ? 'Pronto para enviar confirmações e lembretes.'
                                            : 'Conecte um número para iniciar os envios automáticos.'}
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {whatsAppStatus === 'connected'
                                            ? 'Os envios usarão exclusivamente o número desta empresa.'
                                            : 'O QR Code é temporário e não fica armazenado no AgendaFlow.'}
                                    </p>
                                    {connectionError && (
                                        <p className="mt-2 text-sm text-destructive">
                                            {connectionError}
                                        </p>
                                    )}
                                </div>
                                <div className="flex flex-col gap-2 sm:flex-row">
                                    {whatsapp.instance_name && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="min-h-11"
                                            onClick={() =>
                                                void checkWhatsAppStatus()
                                            }
                                            disabled={checkingConnection}
                                        >
                                            {checkingConnection ? (
                                                <Spinner />
                                            ) : (
                                                <RefreshCw className="size-4" />
                                            )}
                                            Atualizar estado
                                        </Button>
                                    )}
                                    {whatsAppStatus === 'connected' ? (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="min-h-11"
                                            disabled={disconnectForm.processing}
                                            onClick={() => {
                                                if (
                                                    window.confirm(
                                                        'Desconectar este WhatsApp? Os envios ficarão suspensos até uma nova conexão.',
                                                    )
                                                ) {
                                                    disconnectForm.delete(
                                                        '/comunicacao/whatsapp',
                                                        {
                                                            preserveScroll: true,
                                                            onSuccess: (
                                                                page,
                                                            ) => {
                                                                const next =
                                                                    page.props
                                                                        .whatsapp as WhatsAppState;
                                                                setWhatsAppStatus(
                                                                    next.status,
                                                                );
                                                                setQrCode(
                                                                    next.qr_code,
                                                                );
                                                                setConnectionError(
                                                                    next.last_error,
                                                                );
                                                            },
                                                        },
                                                    );
                                                }
                                            }}
                                        >
                                            {disconnectForm.processing ? (
                                                <Spinner />
                                            ) : (
                                                <Unplug className="size-4" />
                                            )}
                                            Desconectar
                                        </Button>
                                    ) : (
                                        <Button
                                            type="button"
                                            className="min-h-11"
                                            disabled={connectForm.processing}
                                            onClick={() =>
                                                connectForm.post(
                                                    '/comunicacao/whatsapp/conectar',
                                                    {
                                                        preserveScroll: true,
                                                        onSuccess: (page) => {
                                                            const next = page
                                                                .props
                                                                .whatsapp as WhatsAppState;
                                                            setWhatsAppStatus(
                                                                next.status,
                                                            );
                                                            setQrCode(
                                                                next.qr_code,
                                                            );
                                                            setConnectionError(
                                                                next.last_error,
                                                            );
                                                        },
                                                    },
                                                )
                                            }
                                        >
                                            {connectForm.processing ? (
                                                <Spinner />
                                            ) : (
                                                <QrCode className="size-4" />
                                            )}
                                            Gerar QR Code
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <form onSubmit={submit} className="grid gap-6 xl:grid-cols-2">
                    <Card className="shadow-none">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Confirmação de presença
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Envie um link exclusivo para o cliente confirmar
                                ou cancelar o atendimento.
                            </p>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <label className="flex items-start gap-3 rounded-xl border p-4">
                                <Checkbox
                                    checked={form.data.confirmation_enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'confirmation_enabled',
                                            checked === true,
                                        )
                                    }
                                />
                                <span>
                                    <span className="block text-sm font-medium">
                                        Solicitar confirmação automaticamente
                                    </span>
                                    <span className="mt-1 block text-xs text-muted-foreground">
                                        O status muda para aguardando
                                        confirmação quando a mensagem é enviada.
                                    </span>
                                </span>
                            </label>
                            <div className="space-y-2">
                                <Label htmlFor="confirmation-offset">
                                    Enviar com antecedência
                                </Label>
                                <select
                                    id="confirmation-offset"
                                    value={
                                        form.data.confirmation_minutes_before
                                    }
                                    disabled={!form.data.confirmation_enabled}
                                    onChange={(event) =>
                                        form.setData(
                                            'confirmation_minutes_before',
                                            Number(event.target.value),
                                        )
                                    }
                                    className="min-h-11 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    {offsetOptions.map((option) => (
                                        <option
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="shadow-none">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Lembretes
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Escolha até cinco momentos para reforçar o
                                compromisso.
                            </p>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <label className="flex items-center gap-3 rounded-xl border p-4 text-sm font-medium">
                                <Checkbox
                                    checked={form.data.reminders_enabled}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'reminders_enabled',
                                            checked === true,
                                        )
                                    }
                                />
                                Ativar lembretes automáticos
                            </label>
                            <fieldset
                                className="grid gap-3 sm:grid-cols-2"
                                disabled={!form.data.reminders_enabled}
                            >
                                <legend className="mb-2 text-sm font-medium">
                                    Momentos de envio
                                </legend>
                                {offsetOptions.map((option) => (
                                    <label
                                        key={option.value}
                                        className="flex min-h-11 items-center gap-3 rounded-lg border px-3 text-sm"
                                    >
                                        <Checkbox
                                            checked={form.data.reminder_offsets.includes(
                                                option.value,
                                            )}
                                            onCheckedChange={(checked) =>
                                                toggleArray(
                                                    'reminder_offsets',
                                                    option.value,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        {option.label}
                                    </label>
                                ))}
                            </fieldset>
                            <InputError message={errors.reminder_offsets} />
                        </CardContent>
                    </Card>

                    <Card className="shadow-none xl:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base">Canais</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2">
                            {[
                                {
                                    value: 'whatsapp',
                                    label: 'WhatsApp',
                                    icon: MessageCircle,
                                },
                                { value: 'mail', label: 'E-mail', icon: Mail },
                            ].map(({ value, label, icon: Icon }) => (
                                <label
                                    key={value}
                                    className="flex min-h-14 items-center gap-3 rounded-xl border px-4"
                                >
                                    <Checkbox
                                        checked={form.data.channels.includes(
                                            value,
                                        )}
                                        onCheckedChange={(checked) =>
                                            toggleArray(
                                                'channels',
                                                value,
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Icon className="size-5 text-blue-700 dark:text-blue-300" />
                                    <span className="text-sm font-medium">
                                        {label}
                                    </span>
                                </label>
                            ))}
                            <InputError message={errors.channels} />
                        </CardContent>
                    </Card>

                    <div className="flex justify-end xl:col-span-2">
                        <Button
                            type="submit"
                            className="min-h-11 min-w-44"
                            disabled={form.processing}
                        >
                            {form.processing ? (
                                <Spinner />
                            ) : (
                                <Save className="size-4" />
                            )}
                            Salvar configurações
                        </Button>
                    </div>
                </form>

                <Card className="overflow-hidden py-0 shadow-none">
                    <CardHeader className="border-b px-5 py-5 sm:px-6">
                        <CardTitle className="text-base">
                            Histórico de envios
                        </CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Últimas 50 tentativas, incluindo falhas e canais sem
                            destinatário.
                        </p>
                    </CardHeader>
                    <CardContent className="p-0">
                        {logs.length === 0 ? (
                            <p className="px-6 py-12 text-center text-sm text-muted-foreground">
                                Nenhuma comunicação registrada ainda.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-muted/40 text-xs text-muted-foreground">
                                        <tr>
                                            <th className="px-5 py-3 font-medium">
                                                Cliente
                                            </th>
                                            <th className="px-5 py-3 font-medium">
                                                Mensagem
                                            </th>
                                            <th className="px-5 py-3 font-medium">
                                                Canal
                                            </th>
                                            <th className="px-5 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="px-5 py-3 font-medium">
                                                Envio
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {logs.map((log) => (
                                            <tr key={log.id}>
                                                <td className="px-5 py-4">
                                                    <p className="font-medium">
                                                        {log.customer}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {log.service}
                                                    </p>
                                                </td>
                                                <td className="px-5 py-4">
                                                    {log.type}
                                                </td>
                                                <td className="px-5 py-4">
                                                    {log.channel}
                                                </td>
                                                <td className="px-5 py-4">
                                                    <Badge
                                                        variant={
                                                            log.status ===
                                                            'failed'
                                                                ? 'destructive'
                                                                : 'outline'
                                                        }
                                                    >
                                                        {statusLabel[
                                                            log.status
                                                        ] ?? log.status}
                                                    </Badge>
                                                    {log.error && (
                                                        <p className="mt-1 max-w-xs text-xs text-rose-600">
                                                            {log.error}
                                                        </p>
                                                    )}
                                                </td>
                                                <td className="px-5 py-4 text-muted-foreground">
                                                    {log.sent_at ??
                                                        log.scheduled_for ??
                                                        '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </main>
        </>
    );
}
