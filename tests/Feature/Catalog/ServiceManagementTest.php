<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithCompanies;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use InteractsWithCompanies, RefreshDatabase;

    public function test_an_owner_can_create_update_and_search_services(): void
    {
        [$company, $user] = $this->companyUser();

        $this->actingAs($user)->post(route('services.store'), [
            'name' => 'Corte premium',
            'description' => 'Consulta e acabamento.',
            'category' => 'Cabelo',
            'duration_minutes' => 60,
            'buffer_minutes' => 10,
            'price' => 120,
            'is_active' => true,
        ])->assertRedirect(route('services.index'));

        $service = Service::withoutGlobalScopes()->where('name', 'Corte premium')->firstOrFail();
        $this->assertSame($company->getKey(), $service->company_id);
        $this->assertTrue((bool) $company->fresh()->onboarding_steps['first_service']);

        $this->actingAs($user)->patch(route('services.update', $service->uuid), [
            'name' => 'Corte signature',
            'description' => null,
            'category' => 'Cabelo',
            'duration_minutes' => 75,
            'buffer_minutes' => 15,
            'price' => 150,
            'is_active' => true,
        ])->assertRedirect(route('services.index'));

        $this->actingAs($user)->get(route('services.index', ['search' => 'signature']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('services/index')
                ->has('services.data', 1)
                ->where('services.data.0.name', 'Corte signature'));
    }

    public function test_service_names_are_unique_only_inside_the_current_company(): void
    {
        [$company, $user] = $this->companyUser();
        [$foreignCompany] = $this->companyUser();
        Service::factory()->create(['company_id' => $company->getKey(), 'name' => 'Manicure']);
        Service::factory()->create(['company_id' => $foreignCompany->getKey(), 'name' => 'Massagem']);

        $this->actingAs($user)->post(route('services.store'), $this->serviceData('Manicure'))
            ->assertInvalid('name');
        $this->actingAs($user)->post(route('services.store'), $this->serviceData('Massagem'))
            ->assertValid();
    }

    /** @return array<string, mixed> */
    private function serviceData(string $name): array
    {
        return [
            'name' => $name,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'price' => 50,
            'is_active' => true,
        ];
    }
}
