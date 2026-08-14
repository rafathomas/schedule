<?php

namespace App\Models;

use App\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Base model for every record that belongs to a tenant.
 *
 * Domain models introduced in the next stages must extend this class so the
 * tenant scope and automatic company assignment remain consistent.
 */
abstract class CompanyOwnedModel extends Model
{
    use BelongsToCompany;
}
