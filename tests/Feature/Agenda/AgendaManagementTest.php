<?php

declare(strict_types=1);

namespace Tests\Feature\Agenda;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\BusinessHour;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithCompanies;
use Tests\TestCase;

class AgendaManagementTest extends TestCase
{
    use InteractsWithCompanies, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-11 12:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_owner_can_view_weekly_agenda_with_only_company_records(): void
    {
        [$company, $user] = $this->companyUser();
        [$professional, $service, $customer] = $this->catalog($company);
        $appointment = $this->appointment($company, $professional, $service, $customer, '2026-08-17 12:00:00');

        [$otherCompany] = $this->companyUser();
        [$otherProfessional, $otherService, $otherCustomer] = $this->catalog($otherCompany);
        $foreign = $this->appointment($otherCompany, $otherProfessional, $otherService, $otherCustomer, '2026-08-17 12:00:00');

        $this->actingAs($user)
            ->get(route('agenda.index', ['date' => '2026-08-17', 'view' => 'week']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('agenda/index')
                ->where('view', 'week')
                ->has('days', 7)
                ->has('appointments', 1)
                ->where('appointments.0.uuid', $appointment->uuid)
                ->missing('appointments.1')
                ->whereNot('appointments.0.uuid', $foreign->uuid));
    }

    public function test_availability_combines_hours_breaks_appointments_and_blocks(): void
    {
        [$company, $user] = $this->companyUser();
        $company->update([
            'minimum_notice_minutes' => 0,
            'maximum_notice_days' => 30,
            'appointment_interval_minutes' => 30,
        ]);
        [$professional, $service, $customer] = $this->catalog($company);
        BusinessHour::query()->create([
            'company_id' => $company->getKey(),
            'day_of_week' => 1,
            'is_closed' => false,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'break_starts_at' => '12:00',
            'break_ends_at' => '13:00',
        ]);
        $this->appointment($company, $professional, $service, $customer, '2026-08-17 13:00:00');
        BlockedPeriod::factory()->create([
            'company_id' => $company->getKey(),
            'professional_id' => $professional->getKey(),
            'starts_at' => '2026-08-17 17:00:00',
            'ends_at' => '2026-08-17 18:00:00',
        ]);

        $response = $this->actingAs($user)->getJson(route('agenda.availability', [
            'professional' => $professional->uuid,
            'service' => $service->uuid,
            'date' => '2026-08-17',
        ]));

        $response->assertOk();
        $values = collect($response->json('slots'))->pluck('value');
        $this->assertTrue($values->contains('15:00'));
        $this->assertFalse($values->contains('10:00'), 'Existing appointment must occupy 10:00 local time.');
        $this->assertFalse($values->contains('12:00'), 'Company break must not be available.');
        $this->assertFalse($values->contains('14:00'), 'Blocked period must not be available.');
    }

    public function test_owner_can_create_manual_appointment_with_inline_customer_and_utc_snapshot(): void
    {
        [$company, $user] = $this->companyUser();
        $company->update(['minimum_notice_minutes' => 0]);
        [$professional, $service] = $this->catalog($company);
        $this->mondayHours($company);

        $this->actingAs($user)->post(route('appointments.store'), [
            'customer_mode' => 'new',
            'customer_uuid' => null,
            'new_customer' => [
                'name' => 'Marina Alves',
                'phone' => '(11) 99999-1234',
                'whatsapp' => '(11) 99999-1234',
                'email' => 'marina@example.test',
            ],
            'professional_uuid' => $professional->uuid,
            'service_uuid' => $service->uuid,
            'date' => '2026-08-17',
            'time' => '09:00',
            'notes' => 'Primeira visita.',
        ])->assertRedirect();

        $customer = Customer::query()->where('email', 'marina@example.test')->firstOrFail();
        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame($customer->getKey(), $appointment->customer_id);
        $this->assertSame('2026-08-17T12:00:00.000000Z', $appointment->scheduled_at->toISOString());
        $this->assertSame(60, $appointment->duration_minutes);
        $this->assertSame('125.00', $appointment->price);
        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointment->getKey(),
            'type' => 'created',
        ]);
        $this->assertNotNull($customer->fresh()->first_appointment_at);
    }

    public function test_backend_rejects_a_second_appointment_in_the_same_occupied_interval(): void
    {
        [$company, $user] = $this->companyUser();
        $company->update(['minimum_notice_minutes' => 0]);
        [$professional, $service, $customer] = $this->catalog($company);
        $this->mondayHours($company);
        $this->appointment($company, $professional, $service, $customer, '2026-08-17 12:00:00');
        $otherCustomer = Customer::factory()->create(['company_id' => $company->getKey()]);

        $this->actingAs($user)->post(route('appointments.store'), [
            'customer_mode' => 'existing',
            'customer_uuid' => $otherCustomer->uuid,
            'new_customer' => null,
            'professional_uuid' => $professional->uuid,
            'service_uuid' => $service->uuid,
            'date' => '2026-08-17',
            'time' => '09:30',
            'notes' => null,
        ])->assertInvalid('time');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_owner_can_transition_an_appointment_through_valid_statuses(): void
    {
        [$company, $user] = $this->companyUser();
        [$professional, $service, $customer] = $this->catalog($company);
        $appointment = $this->appointment($company, $professional, $service, $customer, '2026-08-17 12:00:00');

        $this->actingAs($user)->patch(route('appointments.status.update', $appointment->uuid), [
            'status' => 'confirmed',
        ])->assertRedirect();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);

        $this->actingAs($user)->patch(route('appointments.status.update', $appointment->uuid), [
            'status' => 'completed',
        ])->assertRedirect();
        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);

        $this->actingAs($user)->patch(route('appointments.status.update', $appointment->uuid), [
            'status' => 'confirmed',
        ])->assertInvalid('status');
    }

    public function test_professional_sees_only_own_agenda_and_cannot_manage_blocks(): void
    {
        [$company, $professionalUser] = $this->companyUser('professional');
        [$ownProfessional, $service, $customer] = $this->catalog($company, $professionalUser);
        $otherProfessional = Professional::factory()->create(['company_id' => $company->getKey()]);
        $otherProfessional->services()->attach($service, ['company_id' => $company->getKey()]);
        $own = $this->appointment($company, $ownProfessional, $service, $customer, '2026-08-17 12:00:00');
        $other = $this->appointment($company, $otherProfessional, $service, $customer, '2026-08-17 15:00:00');

        $this->actingAs($professionalUser)
            ->get(route('agenda.index', ['date' => '2026-08-17']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('appointments', 1)
                ->where('appointments.0.uuid', $own->uuid)
                ->whereNot('appointments.0.uuid', $other->uuid)
                ->where('canManage', false));

        $this->actingAs($professionalUser)->post(route('blocked-periods.store'), [
            'professional_uuid' => $ownProfessional->uuid,
            'starts_at' => '2026-08-17T14:00',
            'ends_at' => '2026-08-17T15:00',
            'reason' => 'Particular',
        ])->assertForbidden();
    }

    /** @return array{Professional, Service, Customer} */
    private function catalog(Company $company, ?User $professionalUser = null): array
    {
        $professional = Professional::factory()->create([
            'company_id' => $company->getKey(),
            'user_id' => $professionalUser?->getKey(),
        ]);
        $service = Service::factory()->create([
            'company_id' => $company->getKey(),
            'duration_minutes' => 60,
            'buffer_minutes' => 10,
            'price' => 125,
        ]);
        $professional->services()->attach($service, ['company_id' => $company->getKey()]);
        $customer = Customer::factory()->create(['company_id' => $company->getKey()]);

        return [$professional, $service, $customer];
    }

    private function mondayHours(Company $company): BusinessHour
    {
        return BusinessHour::query()->create([
            'company_id' => $company->getKey(),
            'day_of_week' => 1,
            'is_closed' => false,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'break_starts_at' => '12:00',
            'break_ends_at' => '13:00',
        ]);
    }

    private function appointment(
        Company $company,
        Professional $professional,
        Service $service,
        Customer $customer,
        string $scheduledAt,
    ): Appointment {
        $startsAt = CarbonImmutable::parse($scheduledAt, 'UTC');

        return Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'professional_id' => $professional->getKey(),
            'service_id' => $service->getKey(),
            'customer_id' => $customer->getKey(),
            'scheduled_at' => $startsAt,
            'ends_at' => $startsAt->addHour(),
            'blocks_until' => $startsAt->addMinutes(70),
            'duration_minutes' => 60,
            'price' => 125,
            'status' => AppointmentStatus::Pending,
        ]);
    }
}
