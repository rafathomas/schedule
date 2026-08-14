<?php

declare(strict_types=1);

namespace Tests\Feature\PublicBooking;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_public_page_exposes_only_the_company_catalog(): void
    {
        [$company, , $service] = $this->catalog();
        [$foreignCompany, , $foreignService] = $this->catalog();

        $this->get(route('public-booking.index', $company->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/booking')
                ->where('company.slug', $company->slug)
                ->has('services', 1)
                ->where('services.0.uuid', $service->uuid)
                ->whereNot('services.0.uuid', $foreignService->uuid));

        $this->get(route('public-booking.index', $foreignCompany->slug))->assertOk();
    }

    public function test_any_professional_availability_uses_the_free_member_of_the_team(): void
    {
        [$company, $professionals, $service] = $this->catalog(2);
        $this->occupy($company, $professionals[0], $service, '2026-08-17 12:00:00');

        $this->getJson(route('public-booking.availability', [
            'company' => $company->slug,
            'service' => $service->uuid,
            'date' => '2026-08-17',
        ]))
            ->assertOk()
            ->assertJsonFragment(['value' => '09:00']);

        $this->getJson(route('public-booking.availability', [
            'company' => $company->slug,
            'service' => $service->uuid,
            'professional' => $professionals[0]->uuid,
            'date' => '2026-08-17',
        ]))
            ->assertOk()
            ->assertJsonMissing(['value' => '09:00']);
    }

    public function test_guest_can_book_with_any_available_professional(): void
    {
        [$company, $professionals, $service] = $this->catalog(2);
        $this->occupy($company, $professionals[0], $service, '2026-08-17 12:00:00');

        $response = $this->post(route('public-booking.store', $company->slug), [
            'service_uuid' => $service->uuid,
            'professional_uuid' => null,
            'date' => '2026-08-17',
            'time' => '09:00',
            'name' => 'Joana Pereira',
            'whatsapp' => '(11) 98888-4455',
            'email' => 'joana@example.test',
        ]);

        $appointment = Appointment::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('public-booking.success', [
            'company' => $company->slug,
            'appointment' => $appointment->uuid,
        ]));
        $this->assertSame($professionals[1]->getKey(), $appointment->professional_id);
        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertSame('2026-08-17T12:00:00.000000Z', $appointment->scheduled_at->toISOString());
        $this->assertDatabaseHas('customers', [
            'company_id' => $company->getKey(),
            'email' => 'joana@example.test',
        ]);
        $this->assertDatabaseHas('appointment_events', [
            'company_id' => $company->getKey(),
            'appointment_id' => $appointment->getKey(),
            'type' => 'created',
        ]);
        $this->assertSame('public', $appointment->events()->firstOrFail()->metadata['source']);
    }

    public function test_public_booking_revalidates_an_unavailable_slot(): void
    {
        [$company, $professionals, $service] = $this->catalog();
        $this->occupy($company, $professionals[0], $service, '2026-08-17 12:00:00');

        $this->from(route('public-booking.index', $company->slug))
            ->post(route('public-booking.store', $company->slug), [
                'service_uuid' => $service->uuid,
                'professional_uuid' => $professionals[0]->uuid,
                'date' => '2026-08-17',
                'time' => '09:00',
                'name' => 'Joana Pereira',
                'whatsapp' => '(11) 98888-4455',
                'email' => null,
            ])
            ->assertRedirect(route('public-booking.index', $company->slug))
            ->assertSessionHasErrors('time');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_success_page_and_calendar_are_scoped_to_the_slug(): void
    {
        [$company, $professionals, $service] = $this->catalog();
        $appointment = $this->occupy($company, $professionals[0], $service, '2026-08-17 12:00:00');
        [$foreignCompany] = $this->catalog();

        $this->get(route('public-booking.success', [
            'company' => $company->slug,
            'appointment' => $appointment->uuid,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/success')
                ->where('appointment.uuid', $appointment->uuid)
                ->where('appointment.time', '09:00'));

        $this->get(route('public-booking.calendar', [
            'company' => $company->slug,
            'appointment' => $appointment->uuid,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/calendar; charset=UTF-8')
            ->assertSee('BEGIN:VCALENDAR')
            ->assertSee('DTSTART:20260817T120000Z');

        $this->get(route('public-booking.success', [
            'company' => $foreignCompany->slug,
            'appointment' => $appointment->uuid,
        ]))->assertNotFound();
    }

    public function test_inactive_company_has_no_public_booking_page(): void
    {
        [$company] = $this->catalog();
        $company->update(['status' => 'inactive']);

        $this->get(route('public-booking.index', $company->slug))->assertNotFound();
    }

    /** @return array{Company, array<int, Professional>, Service} */
    private function catalog(int $professionalCount = 1): array
    {
        $company = Company::factory()->create([
            'minimum_notice_minutes' => 0,
            'maximum_notice_days' => 30,
        ]);
        $service = Service::factory()->create([
            'company_id' => $company->getKey(),
            'duration_minutes' => 60,
            'buffer_minutes' => 10,
            'price' => 120,
        ]);
        $professionals = Professional::factory()->count($professionalCount)->create([
            'company_id' => $company->getKey(),
        ])->each(fn (Professional $professional) => $professional->services()->attach($service, [
            'company_id' => $company->getKey(),
        ]))->all();
        BusinessHour::query()->create([
            'company_id' => $company->getKey(),
            'day_of_week' => 1,
            'is_closed' => false,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'break_starts_at' => '12:00',
            'break_ends_at' => '13:00',
        ]);

        return [$company, $professionals, $service];
    }

    private function occupy(
        Company $company,
        Professional $professional,
        Service $service,
        string $startsAt,
    ): Appointment {
        $start = CarbonImmutable::parse($startsAt, 'UTC');
        $customer = Customer::factory()->create(['company_id' => $company->getKey()]);

        return Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'professional_id' => $professional->getKey(),
            'service_id' => $service->getKey(),
            'scheduled_at' => $start,
            'ends_at' => $start->addHour(),
            'blocks_until' => $start->addMinutes(70),
            'status' => AppointmentStatus::Confirmed,
        ]);
    }
}
