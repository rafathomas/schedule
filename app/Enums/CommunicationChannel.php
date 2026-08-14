<?php

declare(strict_types=1);

namespace App\Enums;

enum CommunicationChannel: string
{
    case WhatsApp = 'whatsapp';
    case Mail = 'mail';

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Mail => 'E-mail',
        };
    }
}
