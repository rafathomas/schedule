<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Enums\NotificationLogStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $company_id
 * @property int $appointment_id
 * @property CommunicationType $type
 * @property CommunicationChannel $channel
 * @property NotificationLogStatus $status
 * @property string $idempotency_key
 * @property string|null $recipient
 * @property string $message
 * @property CarbonImmutable|null $scheduled_for
 * @property CarbonImmutable|null $sent_at
 * @property int $attempt
 * @property string|null $provider_message_id
 * @property string|null $error
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'company_id',
    'appointment_id',
    'type',
    'channel',
    'status',
    'idempotency_key',
    'recipient',
    'message',
    'scheduled_for',
    'sent_at',
    'attempt',
    'provider_message_id',
    'error',
    'metadata',
])]
class NotificationLog extends CompanyOwnedModel
{
    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => CommunicationType::class,
            'channel' => CommunicationChannel::class,
            'status' => NotificationLogStatus::class,
            'scheduled_for' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'attempt' => 'integer',
            'metadata' => 'array',
        ];
    }
}
