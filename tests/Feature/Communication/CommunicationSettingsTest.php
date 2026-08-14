<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithCompanies;
use Tests\TestCase;

class CommunicationSettingsTest extends TestCase
{
    use InteractsWithCompanies, RefreshDatabase;

    public function test_owner_can_view_and_update_communication_settings(): void
    {
        [$company, $owner] = $this->companyUser();

        $this->actingAs($owner)
            ->get(route('communication.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('communication/index')
                ->where('settings.confirmation_enabled', true)
                ->has('logs', 0));

        $this->actingAs($owner)
            ->put(route('communication.update'), [
                'confirmation_enabled' => true,
                'confirmation_minutes_before' => 720,
                'reminders_enabled' => true,
                'reminder_offsets' => [2880, 120],
                'channels' => ['whatsapp'],
            ])
            ->assertRedirect(route('communication.index'));

        $this->assertDatabaseHas('reminder_settings', [
            'company_id' => $company->getKey(),
            'confirmation_minutes_before' => 720,
        ]);
    }

    public function test_professional_cannot_manage_communication_settings(): void
    {
        [, $professional] = $this->companyUser('professional');

        $this->actingAs($professional)
            ->get(route('communication.index'))
            ->assertForbidden();
    }
}
