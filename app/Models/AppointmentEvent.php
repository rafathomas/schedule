<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $company_id
 * @property int $appointment_id
 * @property int|null $actor_id
 * @property string $type
 * @property array<string, mixed>|null $metadata
 * @property CarbonImmutable $created_at
 */
#[Fillable(['company_id', 'appointment_id', 'actor_id', 'type', 'metadata', 'created_at'])]
class AppointmentEvent extends CompanyOwnedModel
{
    public const UPDATED_AT = null;

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
