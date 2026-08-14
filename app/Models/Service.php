<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasPublicUuid;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['company_id', 'name', 'description', 'category', 'duration_minutes', 'buffer_minutes', 'price', 'is_active'])]
class Service extends CompanyOwnedModel
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, HasPublicUuid, SoftDeletes;

    /** @return BelongsToMany<Professional, $this, Pivot, 'pivot'> */
    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class)
            ->withPivot('company_id')
            ->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'decimal:2',
        ];
    }
}
