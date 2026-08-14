<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'current_company_id' => $company->getKey(),
        ]);
        $company->users()->attach($user, [
            'role' => 'owner',
            'is_active' => true,
        ]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_shows_real_today_metrics_and_upcoming_appointments(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 12:00:00', 'UTC'));
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->getKey()]);
        $company->users()->attach($user, ['role' => 'owner', 'is_active' => true]);
        $customer = Customer::factory()->create(['company_id' => $company->getKey()]);
        $professional = Professional::factory()->create(['company_id' => $company->getKey()]);
        $service = Service::factory()->create(['company_id' => $company->getKey()]);
        $start = CarbonImmutable::parse('2026-08-12 15:00:00', 'UTC');
        Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'professional_id' => $professional->getKey(),
            'service_id' => $service->getKey(),
            'status' => AppointmentStatus::Confirmed,
            'price' => 125,
            'scheduled_at' => $start,
            'ends_at' => $start->addHour(),
            'blocks_until' => $start->addMinutes(70),
            'confirmed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('operationalSummary.today.appointments', 1)
                ->where('operationalSummary.today.expected_revenue_cents', 12500)
                ->where('operationalSummary.today.confirmed', 1)
                ->has('operationalSummary.upcoming', 1)
                ->where('operationalSummary.upcoming.0.customer', $customer->name)
                ->where('canManage', true));

        CarbonImmutable::setTestNow();
    }

    public function test_unlinked_professional_does_not_receive_company_metrics(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 12:00:00', 'UTC'));
        $company = Company::factory()->create();
        $user = User::factory()->create(['current_company_id' => $company->getKey()]);
        $company->users()->attach($user, ['role' => 'professional', 'is_active' => true]);
        $customer = Customer::factory()->create(['company_id' => $company->getKey()]);
        $professional = Professional::factory()->create(['company_id' => $company->getKey()]);
        $service = Service::factory()->create(['company_id' => $company->getKey()]);
        Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'professional_id' => $professional->getKey(),
            'service_id' => $service->getKey(),
            'scheduled_at' => CarbonImmutable::parse('2026-08-12 15:00:00', 'UTC'),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('operationalSummary.today.appointments', 0)
                ->has('operationalSummary.upcoming', 0)
                ->where('canManage', false));

        CarbonImmutable::setTestNow();
    }
}
