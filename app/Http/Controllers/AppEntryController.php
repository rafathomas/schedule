<?php

namespace App\Http\Controllers;

use App\Support\Tenancy\CurrentCompany;
use Illuminate\Http\RedirectResponse;

class AppEntryController extends Controller
{
    public function __invoke(CurrentCompany $currentCompany): RedirectResponse
    {
        return redirect()->route(
            $currentCompany->getOrFail()->onboarding_completed_at === null
                ? 'onboarding.index'
                : 'dashboard',
        );
    }
}
