<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasPublicUuid;
use Carbon\CarbonImmutable;
use Database\Factories\BlockedPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $company_id
 * @property int|null $professional_id
 * @property string $uuid
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property string $reason
 */
#[Fillable(['company_id', 'professional_id', 'starts_at', 'ends_at', 'reason', 'created_by'])]
class BlockedPeriod extends CompanyOwnedModel
{
    /** @use HasFactory<BlockedPeriodFactory> */
    use HasFactory, HasPublicUuid, SoftDeletes;

    /** @return BelongsTo<Professional, $this> */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }
}
