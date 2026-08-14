<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\ReminderSetting;
use App\Models\Service;
use App\Models\User;
use App\Services\Appointments\CustomerAppointmentMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = Company::factory()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Studio Bella',
            'slug' => 'studio-bella',
            'segment' => 'beauty',
            'phone' => '(11) 3333-4400',
            'whatsapp' => '(11) 99999-4400',
            'postal_code' => '01310-100',
            'address' => 'Avenida Paulista',
            'address_number' => '1000',
            'city' => 'São Paulo',
            'state' => 'SP',
            'onboarding_completed_at' => now(),
            'onboarding_steps' => [
                'company_profile' => true,
                'first_professional' => true,
                'first_service' => true,
                'business_hours' => true,
            ],
        ]);

        $user = User::factory()->create([
            'name' => 'Ana Souza',
            'email' => 'ana@studiobella.test',
            'current_company_id' => $company->getKey(),
        ]);

        $company->users()->attach($user, [
            'role' => 'owner',
            'is_active' => true,
        ]);

        ReminderSetting::query()->create([
            'company_id' => $company->getKey(),
            'confirmation_enabled' => true,
            'confirmation_minutes_before' => 1440,
            'reminders_enabled' => true,
            'reminder_offsets' => [2880, 1440, 120],
            'channels' => ['whatsapp', 'mail'],
        ]);

        $services = collect([
            ['name' => 'Corte feminino', 'category' => 'Cabelo', 'duration_minutes' => 60, 'buffer_minutes' => 10, 'price' => 95],
            ['name' => 'Escova', 'category' => 'Cabelo', 'duration_minutes' => 45, 'buffer_minutes' => 5, 'price' => 70],
            ['name' => 'Coloração', 'category' => 'Cabelo', 'duration_minutes' => 120, 'buffer_minutes' => 15, 'price' => 220],
            ['name' => 'Manicure', 'category' => 'Unhas', 'duration_minutes' => 45, 'buffer_minutes' => 5, 'price' => 45],
            ['name' => 'Pedicure', 'category' => 'Unhas', 'duration_minutes' => 60, 'buffer_minutes' => 5, 'price' => 55],
            ['name' => 'Design de sobrancelhas', 'category' => 'Estética', 'duration_minutes' => 30, 'buffer_minutes' => 5, 'price' => 40],
        ])->map(fn (array $service): Service => Service::factory()->create([
            ...$service,
            'company_id' => $company->getKey(),
            'description' => null,
            'is_active' => true,
        ]));

        $professionals = collect([
            ['name' => 'Ana Souza', 'email' => 'ana@studiobella.test', 'phone' => '(11) 98888-1001'],
            ['name' => 'Beatriz Lima', 'email' => 'beatriz@studiobella.test', 'phone' => '(11) 98888-1002'],
            ['name' => 'Carla Mendes', 'email' => 'carla@studiobella.test', 'phone' => '(11) 98888-1003'],
        ])->map(fn (array $professional): Professional => Professional::factory()->create([
            ...$professional,
            'company_id' => $company->getKey(),
            'description' => 'Especialista do Studio Bella.',
        ]));

        $professionals->each(function (Professional $professional, int $index) use ($services, $company): void {
            $serviceIds = $services->slice($index * 2, 2)->pluck('id');
            $professional->services()->attach(
                $serviceIds->mapWithKeys(fn (int $id): array => [$id => ['company_id' => $company->getKey()]])->all(),
            );
        });

        $customers = Customer::factory()->count(15)->create(['company_id' => $company->getKey()]);

        foreach (range(0, 6) as $day) {
            $isClosed = $day === 0;
            $company->businessHours()->create([
                'day_of_week' => $day,
                'is_closed' => $isClosed,
                'starts_at' => $isClosed ? null : '09:00',
                'ends_at' => $isClosed ? null : ($day === 6 ? '13:00' : '18:00'),
                'break_starts_at' => $day > 0 && $day < 6 ? '12:00' : null,
                'break_ends_at' => $day > 0 && $day < 6 ? '13:00' : null,
            ]);
        }

        $today = CarbonImmutable::today('America/Sao_Paulo');
        $statuses = [
            AppointmentStatus::Confirmed,
            AppointmentStatus::Pending,
            AppointmentStatus::AwaitingConfirmation,
            AppointmentStatus::Completed,
            AppointmentStatus::NoShow,
            AppointmentStatus::Cancelled,
        ];

        foreach ($statuses as $index => $status) {
            $professional = $professionals[$index % $professionals->count()];
            $service = $professional->services()->get()[$index % 2];
            $scheduledAt = $today->addDays(intdiv($index, 3))->setTime(9 + ($index % 3) * 2, 0)->utc();
            $duration = (int) $service->duration_minutes;
            $appointment = Appointment::factory()->create([
                'company_id' => $company->getKey(),
                'customer_id' => $customers[$index]->getKey(),
                'professional_id' => $professional->getKey(),
                'service_id' => $service->getKey(),
                'scheduled_at' => $scheduledAt,
                'ends_at' => $scheduledAt->addMinutes($duration),
                'blocks_until' => $scheduledAt->addMinutes($duration + (int) $service->buffer_minutes),
                'duration_minutes' => $duration,
                'price' => $service->price,
                'status' => $status,
                'created_by' => $user->getKey(),
            ]);
            $appointment->events()->create([
                'company_id' => $company->getKey(),
                'actor_id' => $user->getKey(),
                'type' => 'created',
                'metadata' => ['source' => 'seed'],
            ]);
        }

        foreach (range(0, 4) as $index) {
            $professional = $professionals[$index % $professionals->count()];
            $service = $professional->services()->firstOrFail();
            $scheduledAt = $today->subDays(45 + $index)->setTime(10 + $index, 0)->utc();
            $appointment = Appointment::factory()->create([
                'company_id' => $company->getKey(),
                'customer_id' => $customers[6 + $index]->getKey(),
                'professional_id' => $professional->getKey(),
                'service_id' => $service->getKey(),
                'scheduled_at' => $scheduledAt,
                'ends_at' => $scheduledAt->addMinutes((int) $service->duration_minutes),
                'blocks_until' => $scheduledAt->addMinutes((int) $service->duration_minutes + (int) $service->buffer_minutes),
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'status' => AppointmentStatus::Completed,
                'completed_at' => $scheduledAt->addMinutes((int) $service->duration_minutes),
                'created_by' => $user->getKey(),
            ]);
            $appointment->events()->create([
                'company_id' => $company->getKey(),
                'actor_id' => $user->getKey(),
                'type' => 'created',
                'metadata' => ['source' => 'seed', 'historical' => true],
            ]);
        }

        foreach (range(1, 18) as $index) {
            $professional = $professionals[$index % $professionals->count()];
            $service = $professional->services()->get()[$index % 2];
            $scheduledAt = $today->subDays($index)->setTime(9 + ($index % 6), 0)->utc();
            $status = match (true) {
                $index % 7 === 0 => AppointmentStatus::Cancelled,
                $index % 5 === 0 => AppointmentStatus::NoShow,
                default => AppointmentStatus::Completed,
            };
            $appointment = Appointment::factory()->create([
                'company_id' => $company->getKey(),
                'customer_id' => $customers[6 + ($index % 5)]->getKey(),
                'professional_id' => $professional->getKey(),
                'service_id' => $service->getKey(),
                'scheduled_at' => $scheduledAt,
                'ends_at' => $scheduledAt->addMinutes((int) $service->duration_minutes),
                'blocks_until' => $scheduledAt->addMinutes((int) $service->duration_minutes + (int) $service->buffer_minutes),
                'duration_minutes' => $service->duration_minutes,
                'price' => $service->price,
                'status' => $status,
                'cancelled_at' => $status === AppointmentStatus::Cancelled ? $scheduledAt->subDay() : null,
                'completed_at' => $status === AppointmentStatus::Completed ? $scheduledAt->addMinutes((int) $service->duration_minutes) : null,
                'no_show_at' => $status === AppointmentStatus::NoShow ? $scheduledAt->addMinutes(15) : null,
                'created_by' => $user->getKey(),
            ]);
            $appointment->events()->create([
                'company_id' => $company->getKey(),
                'actor_id' => $user->getKey(),
                'type' => 'created',
                'metadata' => ['source' => 'seed', 'historical' => true],
            ]);
        }

        $metrics = app(CustomerAppointmentMetrics::class);
        $customers->each(fn (Customer $customer) => $metrics->refresh($customer));

        BlockedPeriod::factory()->create([
            'company_id' => $company->getKey(),
            'professional_id' => $professionals[1]->getKey(),
            'starts_at' => $today->setTime(15, 0)->utc(),
            'ends_at' => $today->setTime(16, 30)->utc(),
            'reason' => 'Reunião da equipe',
            'created_by' => $user->getKey(),
        ]);
    }
}
