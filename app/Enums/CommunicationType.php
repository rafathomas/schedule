<?php

declare(strict_types=1);

namespace App\Enums;

enum CommunicationType: string
{
    case Created = 'created';
    case ConfirmationRequest = 'confirmation_request';
    case Reminder = 'reminder';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Agendamento criado',
            self::ConfirmationRequest => 'Solicitação de confirmação',
            self::Reminder => 'Lembrete',
            self::Confirmed => 'Confirmação',
            self::Cancelled => 'Cancelamento',
            self::Rescheduled => 'Reagendamento',
        };
    }
}
