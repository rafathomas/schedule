<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;

class CustomerPolicy
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function viewAny(User $user): bool
    {
        return $this->canManage($user, $this->currentCompany->id());
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->canManage($user, (int) $customer->company_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }

    private function canManage(User $user, int $companyId): bool
    {
        return $user->hasCompanyRole($companyId, ['owner', 'admin']);
    }
}
