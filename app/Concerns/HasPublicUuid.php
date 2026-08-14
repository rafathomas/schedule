<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Str;

trait HasPublicUuid
{
    public static function bootHasPublicUuid(): void
    {
        static::creating(static function (self $model): void {
            if ($model->getAttribute('uuid') === null) {
                $model->setAttribute('uuid', (string) Str::uuid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
