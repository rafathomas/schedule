<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function update(User $user, Company $company): bool
    {
        return $user->companies()
            ->whereKey($company->getKey())
            ->wherePivot('is_active', true)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
