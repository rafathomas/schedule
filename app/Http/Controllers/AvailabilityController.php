<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Appointments\AvailabilityRequest;
use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AvailabilityController extends Controller
{
    public function __invoke(AvailabilityRequest $request, AvailabilityService $availability): JsonResponse
    {
        $professional = Professional::query()->where('uuid', $request->validated('professional'))->firstOrFail();
        $service = Service::query()->where('uuid', $request->validated('service'))->firstOrFail();
        Gate::authorize('view', $professional);
        $excluding = $request->validated('exclude') === null
            ? null
            : Appointment::query()->where('uuid', $request->validated('exclude'))->firstOrFail();

        if (! $professional->services()->whereKey($service->getKey())->exists()) {
            return response()->json(['slots' => []]);
        }

        return response()->json([
            'slots' => $availability->slots($service, $professional, $request->validated('date'), $excluding),
        ]);
    }
}
