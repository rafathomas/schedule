<?php

namespace App\Support\Tenancy;

use App\Models\Company;
use LogicException;

class CurrentCompany
{
    private ?Company $company = null;

    public function set(Company $company): void
    {
        $this->company = $company;
    }

    public function get(): ?Company
    {
        return $this->company;
    }

    public function getOrFail(): Company
    {
        return $this->company
            ?? throw new LogicException('No company has been resolved for this request.');
    }

    public function id(): int
    {
        return $this->getOrFail()->getKey();
    }

    public function forget(): void
    {
        $this->company = null;
    }
}
