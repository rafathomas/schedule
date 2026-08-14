<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['company_id', 'day_of_week', 'is_closed', 'starts_at', 'ends_at', 'break_starts_at', 'break_ends_at'])]
class BusinessHour extends CompanyOwnedModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_closed' => 'boolean'];
    }
}
