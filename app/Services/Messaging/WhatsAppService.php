<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Contracts\MessagingProviderInterface;
use App\ValueObjects\ProviderMessageResult;
use InvalidArgumentException;

class WhatsAppService
{
    public function __construct(private readonly MessagingProviderInterface $provider) {}

    public function sendMessage(string $recipient, string $message): ProviderMessageResult
    {
        $normalized = preg_replace('/\D+/', '', $recipient);

        if (! is_string($normalized) || strlen($normalized) < 10) {
            throw new InvalidArgumentException('O destinatário não possui um WhatsApp válido.');
        }

        if (! str_starts_with($normalized, '55')) {
            $normalized = '55'.$normalized;
        }

        return $this->provider->sendMessage($normalized, $message);
    }
}
