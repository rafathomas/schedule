<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BlockedPeriod;
use App\Models\Company;
use App\Models\Professional;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BlockedPeriod> */
class BlockedPeriodFactory extends Factory
{
    protected $model = BlockedPeriod::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $startsAt = now()->addDays(fake()->numberBetween(1, 15))->setTime(14, 0);

        return [
            'company_id' => Company::factory(),
            'professional_id' => Professional::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'reason' => fake()->randomElement(['Reunião', 'Compromisso', 'Indisponibilidade']),
        ];
    }
}
