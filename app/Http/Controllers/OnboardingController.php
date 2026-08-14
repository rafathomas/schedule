<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function index(CurrentCompany $currentCompany): Response
    {
        return Inertia::render('onboarding/index', [
            'company' => $currentCompany->getOrFail(),
        ]);
    }

    public function update(
        UpdateCompanyProfileRequest $request,
        CurrentCompany $currentCompany,
    ): RedirectResponse {
        $company = $currentCompany->getOrFail();
        $steps = $company->onboarding_steps ?? [];
        $steps['company_profile'] = true;

        $company->update([
            ...$request->validated(),
            'onboarding_steps' => $steps,
            'onboarding_completed_at' => $company->onboarding_completed_at ?? now(),
        ]);

        return back()->with('success', 'Dados da empresa salvos com sucesso.');
    }
}
