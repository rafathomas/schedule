<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $phone = fake()->numerify('(##) #####-####');

        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'phone' => $phone,
            'whatsapp' => $phone,
            'email' => fake()->optional()->safeEmail(),
            'birth_date' => fake()->optional()->dateTimeBetween('-70 years', '-16 years'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
