<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Enums\CommunicationType;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentTokenService;
use App\ValueObjects\AppointmentMessage;
use Carbon\CarbonImmutable;

readonly class AppointmentMessageFactory
{
    public function __construct(private AppointmentTokenService $tokens) {}

    public function make(
        Appointment $appointment,
        CommunicationType $type,
    ): AppointmentMessage {
        $appointment->loadMissing(['company', 'customer', 'professional', 'service']);
        $timezone = (string) $appointment->company->timezone;
        $startsAt = $appointment->scheduled_at->setTimezone($timezone);
        $token = $this->tokens->getOrIssue($appointment);
        $confirmUrl = $this->publicRoute('booking.confirm.show', ['token' => $token]);
        $cancelUrl = $this->publicRoute('booking.cancel.show', ['token' => $token]);
        $bookingUrl = $this->publicRoute('public-booking.index', [
            'company' => $appointment->company->slug,
        ]);
        $details = implode("\n", [
            'Data: '.$startsAt->format('d/m/Y'),
            'Horário: '.$startsAt->format('H:i'),
            'Serviço: '.$appointment->service->name,
            'Profissional: '.$appointment->professional->name,
        ]);

        return match ($type) {
            CommunicationType::Created => new AppointmentMessage(
                'Seu horário foi reservado',
                "Olá, {$appointment->customer->name}!\n\nSeu horário na {$appointment->company->name} foi reservado.\n\n{$details}\n\n*Confirmar presença:*\n{$confirmUrl}\n\n*Cancelar agendamento:*\n{$cancelUrl}",
                $confirmUrl,
                'Confirmar presença',
                $cancelUrl,
                'Cancelar agendamento',
            ),
            CommunicationType::ConfirmationRequest => new AppointmentMessage(
                'Confirme seu atendimento',
                "Olá, {$appointment->customer->name}!\n\nSeu atendimento está próximo.\n\n{$details}\n\n*Confirmar presença:*\n{$confirmUrl}\n\n*Cancelar agendamento:*\n{$cancelUrl}",
                $confirmUrl,
                'Confirmar presença',
                $cancelUrl,
                'Cancelar agendamento',
            ),
            CommunicationType::Reminder => new AppointmentMessage(
                'Lembrete de atendimento',
                "Olá, {$appointment->customer->name}!\n\nEste é um lembrete do seu atendimento.\n\n{$details}\n\n*Ver agendamento:*\n{$confirmUrl}\n\n*Cancelar agendamento:*\n{$cancelUrl}",
                $confirmUrl,
                'Ver agendamento',
                $cancelUrl,
                'Cancelar agendamento',
            ),
            CommunicationType::Confirmed => new AppointmentMessage(
                'Atendimento confirmado',
                "Olá, {$appointment->customer->name}!\n\nSua presença foi confirmada.\n\n{$details}\n\nAté breve!",
            ),
            CommunicationType::Cancelled => new AppointmentMessage(
                'Atendimento cancelado',
                "Olá, {$appointment->customer->name}.\n\nSeu atendimento foi cancelado.\n\n{$details}\n\n*Fazer novo agendamento:*\n{$bookingUrl}",
                $bookingUrl,
                'Fazer novo agendamento',
            ),
            CommunicationType::Rescheduled => new AppointmentMessage(
                'Atendimento reagendado',
                "Olá, {$appointment->customer->name}!\n\nSeu atendimento foi reagendado.\n\n{$details}\n\n*Confirmar nova data:*\n{$confirmUrl}\n\n*Cancelar agendamento:*\n{$cancelUrl}",
                $confirmUrl,
                'Confirmar nova data',
                $cancelUrl,
                'Cancelar agendamento',
            ),
        };
    }

    public function scheduledFor(Appointment $appointment, int $offsetMinutes): CarbonImmutable
    {
        return $appointment->scheduled_at->subMinutes($offsetMinutes);
    }

    /** @param array<string, string> $parameters */
    private function publicRoute(string $name, array $parameters): string
    {
        $baseUrl = rtrim((string) config('app.public_url', config('app.url')), '/');
        $path = route($name, $parameters, false);

        return $baseUrl.'/'.ltrim($path, '/');
    }
}
