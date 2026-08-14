<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasPublicUuid;
use Carbon\CarbonImmutable;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CarbonImmutable|null $first_appointment_at
 * @property CarbonImmutable|null $last_appointment_at
 */
#[Fillable([
    'company_id',
    'name',
    'phone',
    'whatsapp',
    'email',
    'birth_date',
    'notes',
    'first_appointment_at',
    'last_appointment_at',
])]
class Customer extends CompanyOwnedModel
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasPublicUuid, SoftDeletes;

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'first_appointment_at' => 'immutable_datetime',
            'last_appointment_at' => 'immutable_datetime',
        ];
    }
}
