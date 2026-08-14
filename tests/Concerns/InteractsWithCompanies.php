<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Company;
use App\Models\User;

trait InteractsWithCompanies
{
    /** @return array{Company, User} */
    protected function companyUser(string $role = 'owner'): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->getKey()]);
        $company->users()->attach($user, ['role' => $role, 'is_active' => true]);

        return [$company, $user];
    }
}
