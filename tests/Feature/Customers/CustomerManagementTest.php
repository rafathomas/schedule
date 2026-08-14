<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithCompanies;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use InteractsWithCompanies, RefreshDatabase;

    public function test_an_owner_can_create_search_and_open_a_customer_profile(): void
    {
        [$company, $user] = $this->companyUser();

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Luciana Prado',
            'phone' => '(11) 99999-0101',
            'whatsapp' => '(11) 99999-0101',
            'email' => 'luciana@example.test',
            'birth_date' => '1992-05-20',
            'notes' => 'Prefere atendimento pela manhã.',
        ])->assertRedirect(route('customers.index'));

        $customer = Customer::withoutGlobalScopes()->where('email', 'luciana@example.test')->firstOrFail();
        $this->assertSame($company->getKey(), $customer->company_id);

        $this->actingAs($user)->get(route('customers.index', ['search' => '99999-0101']))
            ->assertInertia(fn (Assert $page) => $page->has('customers.data', 1));
        $this->actingAs($user)->get(route('customers.show', $customer->uuid))
            ->assertInertia(fn (Assert $page) => $page
                ->component('customers/show')
                ->where('customer.name', 'Luciana Prado')
                ->where('customer.birth_date', '1992-05-20'));
    }

    public function test_a_professional_cannot_view_customer_records_before_agenda_scope_exists(): void
    {
        [, $user] = $this->companyUser('professional');

        $this->actingAs($user)->get(route('customers.index'))->assertForbidden();
    }

    public function test_a_foreign_customer_uuid_returns_not_found(): void
    {
        [, $user] = $this->companyUser();
        [$foreignCompany] = $this->companyUser();
        $customer = Customer::factory()->create(['company_id' => $foreignCompany->getKey()]);

        $this->actingAs($user)->get(route('customers.show', $customer->uuid))->assertNotFound();
    }
}
