<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<string, bool>|null $onboarding_steps
 * @property string $primary_color
 * @property string|null $logo_path
 */
#[Fillable([
    'uuid',
    'name',
    'slug',
    'status',
    'primary_color',
    'logo_path',
    'segment',
    'phone',
    'whatsapp',
    'postal_code',
    'address',
    'address_number',
    'address_complement',
    'city',
    'state',
    'timezone',
    'appointment_interval_minutes',
    'minimum_notice_minutes',
    'maximum_notice_days',
    'onboarding_steps',
    'onboarding_completed_at',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    public function logoUrl(): ?string
    {
        return $this->logo_path === null
            ? null
            : '/storage/'.ltrim($this->logo_path, '/');
    }

    public function completeOnboardingStep(string $step): void
    {
        $steps = $this->onboarding_steps ?? [];
        $steps[$step] = true;

        $this->forceFill(['onboarding_steps' => $steps])->save();
    }

    /** @return BelongsToMany<User, $this, Pivot, 'pivot'> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    /** @return HasMany<BusinessHour, $this> */
    public function businessHours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }

    /** @return HasOne<ReminderSetting, $this> */
    public function reminderSetting(): HasOne
    {
        return $this->hasOne(ReminderSetting::class);
    }

    /** @return HasMany<NotificationLog, $this> */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /** @return HasOne<WhatsAppConnection, $this> */
    public function whatsAppConnection(): HasOne
    {
        return $this->hasOne(WhatsAppConnection::class);
    }

    protected function casts(): array
    {
        return [
            'onboarding_steps' => 'array',
            'onboarding_completed_at' => 'immutable_datetime',
        ];
    }
}
