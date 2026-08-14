<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasPublicUuid;
use Database\Factories\ProfessionalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['company_id', 'user_id', 'name', 'email', 'phone', 'description', 'avatar_path', 'status'])]
class Professional extends CompanyOwnedModel
{
    /** @use HasFactory<ProfessionalFactory> */
    use HasFactory, HasPublicUuid, SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Service, $this, Pivot, 'pivot'> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot('company_id')
            ->withTimestamps();
    }

    /** @return HasMany<ProfessionalHour, $this> */
    public function hours(): HasMany
    {
        return $this->hasMany(ProfessionalHour::class);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return HasMany<BlockedPeriod, $this> */
    public function blockedPeriods(): HasMany
    {
        return $this->hasMany(BlockedPeriod::class);
    }
}
