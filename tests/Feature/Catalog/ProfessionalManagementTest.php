<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCompanies;
use Tests\TestCase;

class ProfessionalManagementTest extends TestCase
{
    use InteractsWithCompanies, RefreshDatabase;

    public function test_an_owner_can_create_a_professional_and_attach_tenant_services(): void
    {
        [$company, $user] = $this->companyUser();
        $service = Service::factory()->create(['company_id' => $company->getKey()]);

        $this->actingAs($user)->post(route('professionals.store'), [
            'name' => 'Marina Alves',
            'email' => 'marina@example.test',
            'phone' => '(11) 98888-0101',
            'description' => 'Especialista em coloração.',
            'status' => 'active',
            'service_uuids' => [$service->uuid],
        ])->assertRedirect(route('professionals.index'));

        $professional = Professional::withoutGlobalScopes()->where('email', 'marina@example.test')->firstOrFail();
        $this->assertSame($company->getKey(), $professional->company_id);
        $this->assertDatabaseHas('professional_service', [
            'company_id' => $company->getKey(),
            'professional_id' => $professional->getKey(),
            'service_id' => $service->getKey(),
        ]);
        $this->assertTrue((bool) $company->fresh()->onboarding_steps['first_professional']);
    }

    public function test_a_professional_cannot_manage_the_team(): void
    {
        [, $user] = $this->companyUser('professional');

        $this->actingAs($user)->post(route('professionals.store'), [
            'name' => 'Sem permissão',
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_foreign_services_cannot_be_attached(): void
    {
        [, $user] = $this->companyUser();
        [$foreignCompany] = $this->companyUser();
        $service = Service::factory()->create(['company_id' => $foreignCompany->getKey()]);

        $this->actingAs($user)->post(route('professionals.store'), [
            'name' => 'Marina Alves',
            'status' => 'active',
            'service_uuids' => [$service->uuid],
        ])->assertInvalid('service_uuids.0');
    }

    public function test_a_foreign_professional_uuid_is_not_disclosed(): void
    {
        [, $user] = $this->companyUser();
        [$foreignCompany] = $this->companyUser();
        $professional = Professional::factory()->create(['company_id' => $foreignCompany->getKey()]);

        $this->actingAs($user)->patch(route('professionals.update', $professional->uuid), [
            'name' => 'Tentativa',
            'status' => 'active',
        ])->assertNotFound();
    }
}
