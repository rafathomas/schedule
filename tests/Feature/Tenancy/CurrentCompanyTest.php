<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_never_uses_a_company_without_an_active_membership(): void
    {
        $allowedCompany = Company::factory()->create();
        $foreignCompany = Company::factory()->create();
        $user = User::factory()->create([
            'current_company_id' => $foreignCompany->getKey(),
        ]);

        $allowedCompany->users()->attach($user, [
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame($allowedCompany->getKey(), $user->fresh()->current_company_id);
    }

    public function test_it_rejects_a_user_without_an_active_company(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'current_company_id' => $company->getKey(),
        ]);

        $company->users()->attach($user, [
            'role' => 'professional',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_a_professional_cannot_update_company_settings(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'current_company_id' => $company->getKey(),
        ]);

        $company->users()->attach($user, [
            'role' => 'professional',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('onboarding.company.update'), $this->validCompanyData())
            ->assertForbidden();
    }

    public function test_an_owner_can_update_only_the_resolved_company(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create(['name' => 'Outra Empresa']);
        $user = User::factory()->create([
            'current_company_id' => $company->getKey(),
        ]);

        $company->users()->attach($user, [
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('onboarding.company.update'), $this->validCompanyData())
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'id' => $company->getKey(),
            'name' => 'Studio Atualizado',
        ]);
        $this->assertNotNull($company->fresh()->onboarding_completed_at);
        $this->assertDatabaseHas('companies', [
            'id' => $otherCompany->getKey(),
            'name' => 'Outra Empresa',
        ]);
    }

    /** @return array<string, mixed> */
    private function validCompanyData(): array
    {
        return [
            'name' => 'Studio Atualizado',
            'segment' => 'beauty',
            'phone' => '(11) 3333-4444',
            'whatsapp' => '(11) 99999-4444',
            'postal_code' => '01001-000',
            'address' => 'Praça da Sé',
            'address_number' => '100',
            'address_complement' => null,
            'city' => 'São Paulo',
            'state' => 'SP',
            'timezone' => 'America/Sao_Paulo',
            'appointment_interval_minutes' => 30,
            'minimum_notice_minutes' => 120,
            'maximum_notice_days' => 60,
        ];
    }
}
