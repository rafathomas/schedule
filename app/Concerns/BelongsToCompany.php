<?php

namespace App\Concerns;

use App\Models\Company;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', static function (Builder $builder): void {
            $company = app(CurrentCompany::class)->get();

            if ($company !== null) {
                $builder->where($builder->qualifyColumn('company_id'), $company->getKey());
            }
        });

        static::creating(static function (self $model): void {
            $company = app(CurrentCompany::class)->get();

            if ($company !== null && $model->getAttribute('company_id') === null) {
                $model->setAttribute('company_id', $company->getKey());
            }
        });
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
