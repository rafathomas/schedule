<?php

declare(strict_types=1);

namespace Tests\Feature\Branding;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithCompanies;
use Tests\TestCase;

class CompanyBrandingTest extends TestCase
{
    use InteractsWithCompanies, RefreshDatabase;

    public function test_owner_can_open_branding_settings(): void
    {
        [$company, $owner] = $this->companyUser();

        $this->actingAs($owner)
            ->get(route('branding.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('branding/edit')
                ->where('branding.company_name', $company->name)
                ->where('branding.primary_color', '#1E3A8A')
                ->where('branding.logo_url', null));
    }

    public function test_branding_is_shared_on_account_settings_without_company_middleware(): void
    {
        [$company, $owner] = $this->companyUser();
        $company->update(['primary_color' => '#7C3AED']);

        $this->actingAs($owner)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('company.name', $company->name)
                ->where('company.primary_color', '#7C3AED')
                ->where('permissions.branding', true));
    }

    public function test_password_confirmation_receives_current_company_branding(): void
    {
        [$company, $owner] = $this->companyUser();
        $company->update(['primary_color' => '#7C3AED']);

        $this->actingAs($owner)
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/confirm-password')
                ->where('company.primary_color', '#7C3AED'));
    }

    public function test_owner_can_update_color_and_upload_logo(): void
    {
        Storage::fake('public');
        [$company, $owner] = $this->companyUser();

        $this->actingAs($owner)
            ->post(route('branding.update'), [
                'primary_color' => '#7c3aed',
                'logo' => UploadedFile::fake()->image('marca.png', 3000, 1500),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $company->refresh();

        $this->assertSame('#7C3AED', $company->primary_color);
        $this->assertNotNull($company->logo_path);
        $this->assertStringStartsWith("companies/{$company->uuid}/branding/", $company->logo_path);
        Storage::disk('public')->assertExists($company->logo_path);

        $this->actingAs($owner)
            ->get(route('branding.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('branding.logo_url', '/storage/'.$company->logo_path)
                ->where('company.logo_url', '/storage/'.$company->logo_path));
    }

    public function test_replacing_or_removing_logo_cleans_up_previous_file(): void
    {
        Storage::fake('public');
        [$company, $owner] = $this->companyUser();
        $oldPath = "companies/{$company->uuid}/branding/old.png";
        Storage::disk('public')->put($oldPath, 'old-logo');
        $company->update(['logo_path' => $oldPath]);

        $this->actingAs($owner)->post(route('branding.update'), [
            'primary_color' => '#047857',
            'logo' => UploadedFile::fake()->image('nova.png', 600, 300),
        ])->assertSessionHasNoErrors();

        $newPath = $company->fresh()->logo_path;
        $this->assertNotNull($newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);

        $this->actingAs($owner)->post(route('branding.update'), [
            'primary_color' => '#047857',
            'remove_logo' => true,
        ])->assertSessionHasNoErrors();

        $this->assertNull($company->fresh()->logo_path);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_branding_input_is_validated(): void
    {
        Storage::fake('public');
        [, $owner] = $this->companyUser();

        $this->actingAs($owner)
            ->post(route('branding.update'), [
                'primary_color' => 'violet',
                'logo' => UploadedFile::fake()->create('marca.svg', 10, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors(['primary_color', 'logo']);
    }

    public function test_professional_cannot_manage_company_branding(): void
    {
        [, $professional] = $this->companyUser('professional');

        $this->actingAs($professional)->get(route('branding.edit'))->assertForbidden();
        $this->actingAs($professional)
            ->post(route('branding.update'), ['primary_color' => '#7C3AED'])
            ->assertForbidden();
    }

    public function test_update_only_changes_current_company(): void
    {
        [$company, $owner] = $this->companyUser();
        $foreign = Company::factory()->create(['primary_color' => '#BE185D']);

        $this->actingAs($owner)
            ->post(route('branding.update'), ['primary_color' => '#047857'])
            ->assertSessionHasNoErrors();

        $this->assertSame('#047857', $company->fresh()->primary_color);
        $this->assertSame('#BE185D', $foreign->fresh()->primary_color);
    }

    public function test_public_booking_receives_company_branding(): void
    {
        [$company] = $this->companyUser();
        $company->update(['primary_color' => '#BE185D']);

        $this->get(route('public-booking.index', ['company' => $company->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/booking')
                ->where('company.primary_color', '#BE185D')
                ->where('company.logo_url', null));
    }
}
