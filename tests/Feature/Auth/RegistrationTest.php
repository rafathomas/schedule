<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'company_name' => 'Studio Teste',
            'phone' => '(11) 99999-0000',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/app');
        $this->assertDatabaseHas('companies', [
            'name' => 'Studio Teste',
            'slug' => 'studio-teste',
        ]);
        $this->assertDatabaseHas('company_user', [
            'role' => 'owner',
            'is_active' => true,
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertNotNull($user->current_company_id);
    }
}
