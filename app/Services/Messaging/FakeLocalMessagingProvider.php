<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Contracts\MessagingProviderInterface;
use App\ValueObjects\ProviderMessageResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FakeLocalMessagingProvider implements MessagingProviderInterface
{
    public function sendMessage(string $recipient, string $message): ProviderMessageResult
    {
        $messageId = 'local_'.Str::uuid();
        Log::channel(config('services.messaging.log_channel', 'stack'))->info('Local WhatsApp message', [
            'message_id' => $messageId,
            'recipient' => $recipient,
            'message' => $message,
        ]);

        return new ProviderMessageResult($messageId);
    }
}
