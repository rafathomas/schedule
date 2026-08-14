<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::AwaitingConfirmation => 'Aguardando confirmação',
            self::Confirmed => 'Confirmado',
            self::Cancelled => 'Cancelado',
            self::Completed => 'Concluído',
            self::NoShow => 'Não compareceu',
        };
    }

    public function occupiesSchedule(): bool
    {
        return $this !== self::Cancelled;
    }

    /** @return array<int, string> */
    public static function occupyingValues(): array
    {
        return array_values(array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->occupiesSchedule()),
        ));
    }
}
