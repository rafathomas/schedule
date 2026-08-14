<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BlockedPeriods\CreateBlockedPeriodAction;
use App\Http\Requests\BlockedPeriods\StoreBlockedPeriodRequest;
use App\Models\BlockedPeriod;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BlockedPeriodController extends Controller
{
    public function store(StoreBlockedPeriodRequest $request, CreateBlockedPeriodAction $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $action->execute($request->validated(), $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horário bloqueado.']);

        return back();
    }

    public function destroy(string $blockedPeriod): RedirectResponse
    {
        $model = BlockedPeriod::query()->where('uuid', $blockedPeriod)->firstOrFail();
        Gate::authorize('delete', $model);
        $model->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Bloqueio removido.']);

        return back();
    }
}
