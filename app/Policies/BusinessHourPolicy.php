<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BusinessHour;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;

class BusinessHourPolicy
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function viewAny(User $user): bool
    {
        return $user->hasCompanyRole($this->currentCompany->id(), ['owner', 'admin', 'professional']);
    }

    public function updateAny(User $user): bool
    {
        return $user->hasCompanyRole($this->currentCompany->id(), ['owner', 'admin']);
    }

    public function update(User $user, BusinessHour $hour): bool
    {
        return $user->hasCompanyRole((int) $hour->company_id, ['owner', 'admin']);
    }
}
