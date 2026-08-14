<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Professional;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;

class ProfessionalPolicy
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function viewAny(User $user): bool
    {
        return $user->hasCompanyRole($this->currentCompany->id(), ['owner', 'admin', 'professional']);
    }

    public function view(User $user, Professional $professional): bool
    {
        return $user->hasCompanyRole((int) $professional->company_id, ['owner', 'admin', 'professional']);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user, $this->currentCompany->id());
    }

    public function update(User $user, Professional $professional): bool
    {
        return $this->canManage($user, (int) $professional->company_id);
    }

    public function delete(User $user, Professional $professional): bool
    {
        return $this->update($user, $professional);
    }

    private function canManage(User $user, int $companyId): bool
    {
        return $user->hasCompanyRole($companyId, ['owner', 'admin']);
    }
}
