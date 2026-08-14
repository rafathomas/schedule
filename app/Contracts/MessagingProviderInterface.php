<?php

declare(strict_types=1);

namespace App\Contracts;

use App\ValueObjects\ProviderMessageResult;

interface MessagingProviderInterface
{
    public function sendMessage(string $recipient, string $message): ProviderMessageResult;
}
