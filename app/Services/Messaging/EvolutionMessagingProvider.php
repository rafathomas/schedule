<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Contracts\MessagingProviderInterface;
use App\Exceptions\EvolutionApiException;
use App\Models\WhatsAppConnection;
use App\Support\Tenancy\CurrentCompany;
use App\ValueObjects\ProviderMessageResult;

class EvolutionMessagingProvider implements MessagingProviderInterface
{
    public function __construct(
        private readonly EvolutionApiClient $client,
        private readonly CurrentCompany $currentCompany,
    ) {}

    public function sendMessage(string $recipient, string $message): ProviderMessageResult
    {
        $connection = WhatsAppConnection::query()
            ->where('company_id', $this->currentCompany->id())
            ->first();

        if ($connection === null || $connection->status !== 'connected') {
            throw new EvolutionApiException('O WhatsApp da empresa não está conectado. Escaneie o QR Code em Comunicação.');
        }

        return $this->client->sendText($connection->instance_name, $recipient, $message);
    }
}
