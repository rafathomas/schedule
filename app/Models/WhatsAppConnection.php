<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * @property int $company_id
 * @property string $instance_name
 * @property string $status
 * @property string|null $phone
 * @property CarbonImmutable|null $connected_at
 * @property CarbonImmutable|null $last_checked_at
 * @property string|null $last_error
 */
#[Fillable([
    'company_id',
    'instance_name',
    'status',
    'phone',
    'connected_at',
    'last_checked_at',
    'last_error',
])]
class WhatsAppConnection extends CompanyOwnedModel
{
    protected $table = 'whatsapp_connections';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'connected_at' => 'immutable_datetime',
            'last_checked_at' => 'immutable_datetime',
        ];
    }
}
