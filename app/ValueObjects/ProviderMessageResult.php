<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class ProviderMessageResult
{
    public function __construct(public string $messageId) {}
}
