<?php

declare(strict_types=1);

namespace Tests\Feature\Hours;

use App\Models\BusinessHour;
use App\Models\Professional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithCompanies;
use Tests\TestCase;

class HoursManagementTest extends TestCase
{
    use InteractsWithCompanies, RefreshDatabase;

    public function test_an_owner_can_save_all_company_hours(): void
    {
        [$company, $user] = $this->companyUser();

        $this->actingAs($user)->put(route('hours.company.update'), ['hours' => $this->validWeek()])
            ->assertRedirect(route('hours.index'));

        $this->assertDatabaseCount('business_hours', 7);
        $this->assertDatabaseHas('business_hours', [
            'company_id' => $company->getKey(),
            'day_of_week' => 1,
        ]);
        $monday = BusinessHour::withoutGlobalScopes()->where('company_id', $company->getKey())->where('day_of_week', 1)->firstOrFail();
        $this->assertSame('09:00', substr((string) $monday->starts_at, 0, 5));
        $this->assertSame('18:00', substr((string) $monday->ends_at, 0, 5));
        $this->assertTrue((bool) $company->fresh()->onboarding_steps['business_hours']);
    }

    public function test_invalid_time_ranges_are_rejected(): void
    {
        [, $user] = $this->companyUser();
        $hours = $this->validWeek();
        $hours[1]['ends_at'] = '08:00';

        $this->actingAs($user)->put(route('hours.company.update'), ['hours' => $hours])
            ->assertInvalid('hours.1.ends_at');
        $this->assertDatabaseCount('business_hours', 0);
    }

    public function test_professional_overrides_are_tenant_scoped(): void
    {
        [$company, $user] = $this->companyUser();
        $professional = Professional::factory()->create(['company_id' => $company->getKey()]);

        $this->actingAs($user)->put(route('hours.professionals.update', $professional->uuid), ['hours' => $this->validWeek()])
            ->assertRedirect();
        $this->assertDatabaseCount('professional_hours', 7);

        [$foreignCompany] = $this->companyUser();
        $foreign = Professional::factory()->create(['company_id' => $foreignCompany->getKey()]);
        $this->actingAs($user)->put(route('hours.professionals.update', $foreign->uuid), ['hours' => $this->validWeek()])
            ->assertNotFound();
    }

    /** @return array<int, array<string, mixed>> */
    private function validWeek(): array
    {
        return collect(range(0, 6))->map(fn (int $day): array => [
            'day_of_week' => $day,
            'is_closed' => $day === 0,
            'starts_at' => $day === 0 ? null : '09:00',
            'ends_at' => $day === 0 ? null : '18:00',
            'break_starts_at' => $day > 0 && $day < 6 ? '12:00' : null,
            'break_ends_at' => $day > 0 && $day < 6 ? '13:00' : null,
        ])->all();
    }
}
