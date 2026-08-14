<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasPublicUuid;
use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $company_id
 * @property int $customer_id
 * @property int $professional_id
 * @property int $service_id
 * @property int $duration_minutes
 * @property string $uuid
 * @property string $price
 * @property string $confirmation_token
 * @property string|null $confirmation_token_encrypted
 * @property AppointmentStatus $status
 * @property CarbonImmutable $scheduled_at
 * @property CarbonImmutable $ends_at
 * @property CarbonImmutable $blocks_until
 * @property CarbonImmutable|null $confirmed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $no_show_at
 * @property string|null $notes
 * @property string|null $cancellation_reason
 */
#[Fillable([
    'company_id',
    'customer_id',
    'professional_id',
    'service_id',
    'scheduled_at',
    'ends_at',
    'blocks_until',
    'duration_minutes',
    'price',
    'status',
    'confirmation_token',
    'confirmation_token_encrypted',
    'confirmed_at',
    'cancelled_at',
    'completed_at',
    'no_show_at',
    'notes',
    'cancellation_reason',
    'created_by',
])]
#[Hidden(['confirmation_token', 'confirmation_token_encrypted'])]
class Appointment extends CompanyOwnedModel
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, HasPublicUuid, SoftDeletes;

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Professional, $this> */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AppointmentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(AppointmentEvent::class);
    }

    /** @return HasMany<NotificationLog, $this> */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'blocks_until' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'no_show_at' => 'immutable_datetime',
            'price' => 'decimal:2',
            'status' => AppointmentStatus::class,
            'confirmation_token_encrypted' => 'encrypted',
        ];
    }
}
