<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Professional;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Professional> */
class ProfessionalFactory extends Factory
{
    protected $model = Professional::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('(##) #####-####'),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => 'inactive']);
    }
}
