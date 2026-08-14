<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->randomElement(['Cabelo', 'Unhas', 'Estética']),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'buffer_minutes' => fake()->randomElement([0, 5, 10, 15]),
            'price' => fake()->randomFloat(2, 35, 350),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
