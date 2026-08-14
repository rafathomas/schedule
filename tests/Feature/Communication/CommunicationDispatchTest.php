<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Enums\AppointmentStatus;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationType;
use App\Enums\NotificationLogStatus;
use App\Jobs\SendAppointmentMessageJob;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Professional;
use App\Models\ReminderSetting;
use App\Models\Service;
use App\Notifications\AppointmentMessageNotification;
use App\Services\Messaging\AppointmentCommunicationDispatcher;
use App\Services\Messaging\AppointmentMessageFactory;
use App\Support\Tenancy\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommunicationDispatchTest extends TestCase
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

    public function test_dispatcher_records_each_channel_once_before_queueing(): void
    {
        Queue::fake();
        [$company, $appointment] = $this->appointment();
        app(CurrentCompany::class)->set($company);

        $dispatcher = app(AppointmentCommunicationDispatcher::class);
        $this->assertSame(2, $dispatcher->dispatch($appointment, CommunicationType::Created));
        $this->assertSame(0, $dispatcher->dispatch($appointment, CommunicationType::Created));

        $this->assertDatabaseCount('notification_logs', 2);
        $this->assertDatabaseHas('notification_logs', [
            'appointment_id' => $appointment->getKey(),
            'channel' => CommunicationChannel::WhatsApp->value,
            'status' => NotificationLogStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'appointment_id' => $appointment->getKey(),
            'channel' => CommunicationChannel::Mail->value,
        ]);
        Queue::assertPushed(SendAppointmentMessageJob::class, 2);
    }

    public function test_scheduler_queues_only_due_reminders_and_is_idempotent(): void
    {
        Queue::fake();
        [$company, $appointment] = $this->appointment([
            'scheduled_at' => now()->addMinutes(120),
            'ends_at' => now()->addMinutes(180),
            'blocks_until' => now()->addMinutes(190),
        ]);
        app(CurrentCompany::class)->set($company);
        ReminderSetting::query()->create([
            'company_id' => $company->getKey(),
            'confirmation_enabled' => false,
            'reminders_enabled' => true,
            'reminder_offsets' => [120],
            'channels' => [CommunicationChannel::WhatsApp->value],
        ]);
        app(CurrentCompany::class)->forget();

        $this->artisan('appointments:dispatch-reminders')->assertSuccessful();
        $this->artisan('appointments:dispatch-reminders')->assertSuccessful();

        $this->assertDatabaseCount('notification_logs', 1);
        $this->assertDatabaseHas('notification_logs', [
            'appointment_id' => $appointment->getKey(),
            'type' => CommunicationType::Reminder->value,
            'channel' => CommunicationChannel::WhatsApp->value,
        ]);
        Queue::assertPushed(SendAppointmentMessageJob::class, 1);
    }

    public function test_confirmation_job_uses_local_provider_and_updates_status(): void
    {
        [$company, $appointment] = $this->appointment();
        app(CurrentCompany::class)->set($company);
        $log = NotificationLog::query()->create([
            'company_id' => $company->getKey(),
            'appointment_id' => $appointment->getKey(),
            'type' => CommunicationType::ConfirmationRequest,
            'channel' => CommunicationChannel::WhatsApp,
            'status' => NotificationLogStatus::Pending,
            'idempotency_key' => hash('sha256', 'confirmation-test'),
            'recipient' => '11999998888',
            'message' => 'Confirme seu atendimento.',
        ]);
        app(CurrentCompany::class)->forget();

        SendAppointmentMessageJob::dispatchSync((int) $log->getKey());

        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->getKey(),
            'status' => NotificationLogStatus::Sent->value,
            'attempt' => 1,
        ]);
        $this->assertSame(
            AppointmentStatus::AwaitingConfirmation,
            $appointment->refresh()->status,
        );
        $this->assertDatabaseHas('appointment_events', [
            'appointment_id' => $appointment->getKey(),
            'type' => 'confirmation_sent',
        ]);
    }

    public function test_confirmation_email_renders_confirm_and_cancel_as_clickable_links(): void
    {
        config(['app.public_url' => 'https://agenda.example.test']);
        [$company, $appointment] = $this->appointment();
        app(CurrentCompany::class)->set($company);

        $message = app(AppointmentMessageFactory::class)->make(
            $appointment,
            CommunicationType::ConfirmationRequest,
        );
        $html = (new AppointmentMessageNotification($message))
            ->toMail(new \stdClass)
            ->render()
            ->toHtml();

        $this->assertNotNull($message->actionUrl);
        $this->assertNotNull($message->secondaryActionUrl);
        $this->assertStringStartsWith('https://agenda.example.test/booking/', $message->actionUrl);
        $this->assertStringStartsWith('https://agenda.example.test/booking/', $message->secondaryActionUrl);
        $this->assertStringContainsString("*Confirmar presença:*\n{$message->actionUrl}", $message->body);
        $this->assertStringContainsString("*Cancelar agendamento:*\n{$message->secondaryActionUrl}", $message->body);
        $this->assertStringContainsString('href="'.$message->actionUrl.'"', $html);
        $this->assertStringContainsString('href="'.$message->secondaryActionUrl.'"', $html);
        $this->assertStringContainsString('Confirmar presença', $html);
        $this->assertStringContainsString('Cancelar agendamento', $html);
    }

    /** @param array<string, mixed> $overrides @return array{Company, Appointment} */
    private function appointment(array $overrides = []): array
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
            ...$overrides,
        ]);

        return [$company, $appointment];
    }
}
