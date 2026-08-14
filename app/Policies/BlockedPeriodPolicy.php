<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BlockedPeriod;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;

class BlockedPeriodPolicy
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function viewAny(User $user): bool
    {
        return $user->hasCompanyRole($this->currentCompany->id(), ['owner', 'admin', 'professional']);
    }

    public function create(User $user): bool
    {
        return $user->hasCompanyRole($this->currentCompany->id(), ['owner', 'admin']);
    }

    public function delete(User $user, BlockedPeriod $blockedPeriod): bool
    {
        return $user->hasCompanyRole((int) $blockedPeriod->company_id, ['owner', 'admin']);
    }
}
