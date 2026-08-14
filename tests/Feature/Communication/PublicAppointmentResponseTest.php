<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Enums\AppointmentStatus;
use App\Jobs\SendAppointmentMessageJob;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicAppointmentResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_open_token_page_and_confirm_presence(): void
    {
        Queue::fake();
        [, $appointment, $token] = $this->appointment();

        $this->get(route('booking.confirm.show', $token))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/appointment-response')
                ->where('mode', 'confirm')
                ->where('appointment.status', AppointmentStatus::Pending->value)
                ->where('canConfirm', true));

        $this->post(route('booking.confirm.store', $token))
            ->assertRedirect(route('booking.confirm.show', [
                'token' => $token,
                'result' => 'confirmed',
            ]));

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->refresh()->status);
        $this->assertNotNull($appointment->confirmed_at);
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointment->getKey(),
            'actor_id' => null,
            'type' => AppointmentStatus::Confirmed->value,
        ]);
        Queue::assertPushed(SendAppointmentMessageJob::class);
    }

    public function test_client_cancellation_notifies_customer_and_company_admin(): void
    {
        Queue::fake();
        [$company, $appointment, $token] = $this->appointment();
        $owner = User::factory()->create(['current_company_id' => $company->getKey()]);
        $company->users()->attach($owner, ['role' => 'owner', 'is_active' => true]);

        $this->post(route('booking.cancel.store', $token))
            ->assertRedirect(route('booking.cancel.show', [
                'token' => $token,
                'result' => 'cancelled',
            ]));

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->refresh()->status);
        $this->assertNotNull($appointment->cancelled_at);
        $this->assertDatabaseHas('notification_logs', [
            'appointment_id' => $appointment->getKey(),
            'recipient' => $owner->email,
            'channel' => 'mail',
        ]);
        $this->assertSame(3, $appointment->notificationLogs()->count());
        Queue::assertPushed(SendAppointmentMessageJob::class, 3);
    }

    public function test_token_is_exclusive_and_unknown_tokens_return_not_found(): void
    {
        [, $appointment, $token] = $this->appointment();

        $this->get(route('booking.confirm.show', 'invalid-token-that-is-long-enough-0000000000'))
            ->assertNotFound();
        $this->assertNotSame($token, $appointment->confirmation_token);
        $this->assertSame(hash('sha256', $token), $appointment->confirmation_token);
    }

    /** @return array{Company, Appointment, string} */
    private function appointment(): array
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create([
            'company_id' => $company->getKey(),
            'phone' => '(11) 99999-8888',
            'whatsapp' => '(11) 99999-8888',
            'email' => 'cliente@example.test',
        ]);
        $professional = Professional::factory()->create(['company_id' => $company->getKey()]);
        $service = Service::factory()->create(['company_id' => $company->getKey()]);
        $appointment = Appointment::factory()->create([
            'company_id' => $company->getKey(),
            'customer_id' => $customer->getKey(),
            'professional_id' => $professional->getKey(),
            'service_id' => $service->getKey(),
            'status' => AppointmentStatus::Pending,
        ]);
        $token = (string) $appointment->confirmation_token_encrypted;

        return [$company, $appointment, $token];
    }
}
