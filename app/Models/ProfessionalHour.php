<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'professional_id', 'day_of_week', 'is_closed', 'starts_at', 'ends_at', 'break_starts_at', 'break_ends_at'])]
class ProfessionalHour extends CompanyOwnedModel
{
    /** @return BelongsTo<Professional, $this> */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_closed' => 'boolean'];
    }
}
