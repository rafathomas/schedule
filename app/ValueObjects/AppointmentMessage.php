<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class AppointmentMessage
{
    public function __construct(
        public string $subject,
        public string $body,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $secondaryActionUrl = null,
        public ?string $secondaryActionLabel = null,
    ) {}
}
