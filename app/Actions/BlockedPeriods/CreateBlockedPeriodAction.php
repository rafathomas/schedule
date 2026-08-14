<?php

declare(strict_types=1);

namespace App\Actions\BlockedPeriods;

use App\Models\BlockedPeriod;
use App\Models\Professional;
use App\Models\User;
use App\Support\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

readonly class CreateBlockedPeriodAction
{
    public function __construct(private CurrentCompany $currentCompany) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, User $actor): BlockedPeriod
    {
        $professional = isset($data['professional_uuid'])
            ? Professional::query()->where('uuid', $data['professional_uuid'])->firstOrFail()
            : null;
        $timezone = (string) $this->currentCompany->getOrFail()->timezone;

        return BlockedPeriod::query()->create([
            'professional_id' => $professional?->getKey(),
            'starts_at' => $this->localDateTime((string) $data['starts_at'], $timezone)->utc(),
            'ends_at' => $this->localDateTime((string) $data['ends_at'], $timezone)->utc(),
            'reason' => $data['reason'],
            'created_by' => $actor->getKey(),
        ]);
    }

    private function localDateTime(string $value, string $timezone): CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $value, $timezone);

        if ($date === null) {
            throw new InvalidArgumentException('Data ou horário inválido.');
        }

        return $date;
    }
}
