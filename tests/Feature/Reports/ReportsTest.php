<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithCompanies;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use InteractsWithCompanies, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 15:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_owner_sees_exact_metrics_rankings_and_timeline_for_period(): void
    {
        [$company, $owner] = $this->companyUser();
        [$professional, $service] = $this->catalog($company, 'Ana', 'Corte');
        [$secondProfessional, $secondService] = $this->catalog($company, 'Beatriz', 'Manicure');
        $newCustomer = Customer::factory()->create([
            'company_id' => $company->getKey(),
            'first_appointment_at' => CarbonImmutable::parse('2026-08-11 12:00:00', 'UTC'),
        ]);
        $recurringCustomer = Customer::factory()->create([
            'company_id' => $company->getKey(),
            'first_appointment_at' => CarbonImmutable::parse('2026-07-01 12:00:00', 'UTC'),
        ]);

        $this->appointment($company, $newCustomer, $professional, $service, AppointmentStatus::Completed, 100, '2026-08-11 12:00:00', true);
        $this->appointment($company, $recurringCustomer, $professional, $service, AppointmentStatus::Confirmed, 80, '2026-08-12 13:00:00', true);
        $this->appointment($company, $recurringCustomer, $secondProfessional, $secondService, AppointmentStatus::Cancelled, 60, '2026-08-12 14:00:00');
        $this->appointment($company, $newCustomer, $secondProfessional, $secondService, AppointmentStatus::NoShow, 40, '2026-08-10 14:00:00');

        $this->actingAs($owner)
            ->get(route('reports.index', ['period' => 'last_7_days']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('reports/index')
                ->where('period.preset', 'last_7_days')
                ->where('metrics.appointments', 4)
                ->where('metrics.expected_revenue_cents', 18000)
                ->where('metrics.completed', 1)
                ->where('metrics.cancelled', 1)
                ->where('metrics.no_show', 1)
                ->where('metrics.confirmation_rate', 50)
                ->where('metrics.new_customers', 1)
                ->where('metrics.recurring_customers', 1)
                ->has('timeline', 7)
                ->where('top_services.0.name', 'Corte')
                ->where('top_services.0.appointments', 2)
                ->where('top_professionals.0.name', 'Ana')
                ->where('top_professionals.0.appointments', 2));
    }

    public function test_reports_are_tenant_scoped(): void
    {
        [$company, $owner] = $this->companyUser();
        [$professional, $service] = $this->catalog($company, 'Ana', 'Corte');
        $customer = Customer::factory()->create(['company_id' => $company->getKey()]);
        $this->appointment($company, $customer, $professional, $service, AppointmentStatus::Confirmed, 100, '2026-08-12 13:00:00');

        $foreign = Company::factory()->create();
        [$foreignProfessional, $foreignService] = $this->catalog($foreign, 'Externo', 'Outro');
        $foreignCustomer = Customer::factory()->create(['company_id' => $foreign->getKey()]);
        $this->appointment($foreign, $foreignCustomer, $foreignProfessional, $foreignService, AppointmentStatus::Completed, 900, '2026-08-12 13:00:00');

        $this->actingAs($owner)
            ->get(route('reports.index', ['period' => 'today']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('metrics.appointments', 1)
                ->where('metrics.expected_revenue_cents', 10000)
                ->has('top_services', 1)
                ->where('top_services.0.name', 'Corte'));
    }

    public function test_professional_cannot_access_company_reports(): void
    {
        [, $professional] = $this->companyUser('professional');

        $this->actingAs($professional)
            ->get(route('reports.index', ['period' => 'today']))
            ->assertForbidden();
    }

    public function test_custom_period_is_validated_and_limited_to_366_days(): void
    {
        [, $owner] = $this->companyUser();

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'period' => 'custom',
                'start_date' => '2025-01-01',
                'end_date' => '2026-08-12',
            ]))
            ->assertSessionHasErrors('end_date');

        $this->actingAs($owner)
            ->get(route('reports.index', [
                'period' => 'custom',
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-12',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('period.start_date', '2026-08-01')
                ->where('period.end_date', '2026-08-12')
                ->has('timeline', 12));
    }

    /** @return array{Professional, Service} */
    private function catalog(Company $company, string $professionalName, string $serviceName): array
    {
        return [
            Professional::factory()->create(['company_id' => $company->getKey(), 'name' => $professionalName]),
            Service::factory()->create(['company_id' => $company->getKey(), 'name' => $serviceName]),
        ];
    }

    private function appointment(
        Company $company,
        Customer $customer,
        Professional $professional,
        Service $service,
        AppointmentStatus $status,
        int $price,
        string $scheduledAt,
        bool $confirmed = false,
    ): Appointment {
        $start = CarbonImmutable::parse($scheduledAt, 'UTC');

        return Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'professional_id' => $professional->getKey(),
            'service_id' => $service->getKey(),
            'status' => $status,
            'price' => $price,
            'scheduled_at' => $start,
            'ends_at' => $start->addHour(),
            'blocks_until' => $start->addMinutes(70),
            'confirmed_at' => $confirmed ? now() : null,
        ]);
    }
}
