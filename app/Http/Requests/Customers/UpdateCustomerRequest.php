<?php

declare(strict_types=1);

namespace App\Http\Requests\Customers;

use App\Support\Tenancy\CurrentCompany;

class UpdateCustomerRequest extends StoreCustomerRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCompanyRole(app(CurrentCompany::class)->id(), ['owner', 'admin']) === true;
    }
}
