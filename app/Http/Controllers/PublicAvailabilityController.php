<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PublicBooking\PublicAvailabilityRequest;
use App\Models\Professional;
use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;

class PublicAvailabilityController extends Controller
{
    public function __invoke(
        PublicAvailabilityRequest $request,
        string $company,
        AvailabilityService $availability,
    ): JsonResponse {
        $service = Service::query()->where('uuid', $request->validated('service'))->firstOrFail();
        $professionalUuid = $request->validated('professional');
        $professionals = Professional::query()
            ->where('status', 'active')
            ->whereHas('services', fn ($query) => $query->whereKey($service->getKey()))
            ->when(is_string($professionalUuid), fn ($query) => $query->where('uuid', $professionalUuid))
            ->orderBy('name')
            ->get();

        $slots = $professionals
            ->flatMap(fn (Professional $professional): array => $availability->slots(
                $service,
                $professional,
                (string) $request->validated('date'),
            ))
            ->unique('value')
            ->sortBy('value')
            ->values();

        return response()->json(['slots' => $slots]);
    }
}
