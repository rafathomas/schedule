<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;

class ServicePolicy
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function viewAny(User $user): bool
    {
        return $user->hasCompanyRole($this->currentCompany->id(), ['owner', 'admin', 'professional']);
    }

    public function view(User $user, Service $service): bool
    {
        return $user->hasCompanyRole((int) $service->company_id, ['owner', 'admin', 'professional']);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user, $this->currentCompany->id());
    }

    public function update(User $user, Service $service): bool
    {
        return $this->canManage($user, (int) $service->company_id);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }

    private function canManage(User $user, int $companyId): bool
    {
        return $user->hasCompanyRole($companyId, ['owner', 'admin']);
    }
}
