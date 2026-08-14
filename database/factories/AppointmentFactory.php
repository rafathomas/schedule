<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Appointment> */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $scheduledAt = now()->addDays(fake()->numberBetween(1, 20))->setTime(fake()->numberBetween(9, 16), 0);
        $duration = 60;
        $token = Str::random(64);

        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'professional_id' => Professional::factory(),
            'service_id' => Service::factory(),
            'scheduled_at' => $scheduledAt,
            'ends_at' => $scheduledAt->copy()->addMinutes($duration),
            'blocks_until' => $scheduledAt->copy()->addMinutes($duration + 10),
            'duration_minutes' => $duration,
            'price' => 100,
            'status' => AppointmentStatus::Pending,
            'confirmation_token' => hash('sha256', $token),
            'confirmation_token_encrypted' => $token,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
