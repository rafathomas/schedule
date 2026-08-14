<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommunicationChannel;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * @property int $company_id
 * @property bool $confirmation_enabled
 * @property int $confirmation_minutes_before
 * @property bool $reminders_enabled
 * @property array<int, int>|null $reminder_offsets
 * @property array<int, string>|null $channels
 */
#[Fillable([
    'company_id',
    'confirmation_enabled',
    'confirmation_minutes_before',
    'reminders_enabled',
    'reminder_offsets',
    'channels',
])]
class ReminderSetting extends CompanyOwnedModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'confirmation_enabled' => true,
        'confirmation_minutes_before' => 1440,
        'reminders_enabled' => true,
        'reminder_offsets' => '[2880,1440,120]',
        'channels' => '["whatsapp","mail"]',
    ];

    /** @return array<int, int> */
    public function normalizedOffsets(): array
    {
        $offsets = $this->reminder_offsets ?? [2880, 1440, 120];
        $offsets = array_values(array_unique(array_map('intval', $offsets)));
        rsort($offsets);

        return $offsets;
    }

    /** @return array<int, CommunicationChannel> */
    public function normalizedChannels(): array
    {
        return array_values(array_filter(array_map(
            fn (string $channel): ?CommunicationChannel => CommunicationChannel::tryFrom($channel),
            $this->channels ?? [CommunicationChannel::WhatsApp->value, CommunicationChannel::Mail->value],
        )));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'confirmation_enabled' => 'boolean',
            'confirmation_minutes_before' => 'integer',
            'reminders_enabled' => 'boolean',
            'reminder_offsets' => 'array',
            'channels' => 'array',
        ];
    }
}
