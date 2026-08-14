<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProfessionalHour;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;

class ProfessionalHourPolicy
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function updateAny(User $user): bool
    {
        return $user->hasCompanyRole($this->currentCompany->id(), ['owner', 'admin']);
    }

    public function update(User $user, ProfessionalHour $hour): bool
    {
        return $user->hasCompanyRole((int) $hour->company_id, ['owner', 'admin']);
    }
}
