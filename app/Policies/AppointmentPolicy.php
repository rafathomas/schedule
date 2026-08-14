<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;

class AppointmentPolicy
{
    public function __construct(private readonly CurrentCompany $currentCompany) {}

    public function viewAny(User $user): bool
    {
        return $user->hasCompanyRole($this->currentCompany->id(), ['owner', 'admin', 'professional']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($this->canManage($user, (int) $appointment->company_id)) {
            return true;
        }

        return $user->hasCompanyRole((int) $appointment->company_id, ['professional'])
            && $appointment->professional()->where('user_id', $user->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $this->canManage($user, $this->currentCompany->id());
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->canManage($user, (int) $appointment->company_id);
    }

    public function updateStatus(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    private function canManage(User $user, int $companyId): bool
    {
        return $user->hasCompanyRole($companyId, ['owner', 'admin']);
    }
}
