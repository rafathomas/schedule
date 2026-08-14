<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\EvolutionApiException;
use App\Models\User;
use App\Models\WhatsAppConnection;
use App\Services\Messaging\EvolutionApiClient;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WhatsAppConnectionController extends Controller
{
    public function store(
        Request $request,
        CurrentCompany $currentCompany,
        EvolutionApiClient $evolution,
    ): RedirectResponse {
        $this->authorizeAccess($request->user(), $currentCompany);

        if (! $evolution->isConfigured()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'A Evolution API ainda não foi configurada no servidor.',
            ]);

            return back();
        }

        $company = $currentCompany->getOrFail();
        $connection = WhatsAppConnection::query()->firstOrCreate(
            ['company_id' => $company->getKey()],
            ['instance_name' => $this->instanceName((string) $company->uuid)],
        );

        try {
            $payload = $connection->wasRecentlyCreated
                ? $this->createOrRecover($evolution, $connection->instance_name)
                : $evolution->connect($connection->instance_name);
            $qrCode = $this->qrCode($payload);

            if ($qrCode === null) {
                $qrCode = $this->qrCode($evolution->connect($connection->instance_name));
            }

            if ($qrCode === null) {
                $state = $evolution->connectionState($connection->instance_name);

                if ($state === 'open') {
                    $this->markConnected($connection);
                    Inertia::flash('toast', ['type' => 'success', 'message' => 'WhatsApp já está conectado.']);

                    return back();
                }

                throw new EvolutionApiException('A Evolution API não retornou um QR Code. Tente novamente.');
            }

            $connection->update([
                'status' => 'connecting',
                'last_checked_at' => now(),
                'last_error' => null,
            ]);
            $request->session()->flash('whatsapp_qr_code', $qrCode);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'QR Code gerado. Escaneie-o pelo WhatsApp.']);
        } catch (EvolutionApiException $exception) {
            $connection->update([
                'status' => 'error',
                'last_checked_at' => now(),
                'last_error' => $exception->getMessage(),
            ]);
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        return back();
    }

    public function status(
        Request $request,
        CurrentCompany $currentCompany,
        EvolutionApiClient $evolution,
    ): JsonResponse {
        $this->authorizeAccess($request->user(), $currentCompany);
        $connection = WhatsAppConnection::query()->first();

        if ($connection === null) {
            return response()->json(['status' => 'disconnected']);
        }

        try {
            $status = match ($evolution->connectionState($connection->instance_name)) {
                'open' => 'connected',
                'connecting' => 'connecting',
                default => 'disconnected',
            };

            $connection->update([
                'status' => $status,
                'connected_at' => $status === 'connected' ? ($connection->connected_at ?? now()) : null,
                'last_checked_at' => now(),
                'last_error' => null,
            ]);

            return response()->json([
                'status' => $status,
                'last_checked_at' => $connection->last_checked_at?->toIso8601String(),
            ]);
        } catch (EvolutionApiException $exception) {
            $connection->update([
                'status' => 'error',
                'last_checked_at' => now(),
                'last_error' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'error' => $exception->getMessage(),
            ], 503);
        }
    }

    public function destroy(
        Request $request,
        CurrentCompany $currentCompany,
        EvolutionApiClient $evolution,
    ): RedirectResponse {
        $this->authorizeAccess($request->user(), $currentCompany);
        $connection = WhatsAppConnection::query()->first();

        if ($connection === null) {
            return back();
        }

        try {
            $evolution->logout($connection->instance_name);
            $connection->update([
                'status' => 'disconnected',
                'phone' => null,
                'connected_at' => null,
                'last_checked_at' => now(),
                'last_error' => null,
            ]);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'WhatsApp desconectado.']);
        } catch (EvolutionApiException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        return back();
    }

    /** @return array<string, mixed> */
    private function createOrRecover(EvolutionApiClient $evolution, string $instanceName): array
    {
        try {
            return $evolution->createInstance($instanceName);
        } catch (EvolutionApiException $createException) {
            try {
                return $evolution->connect($instanceName);
            } catch (EvolutionApiException) {
                throw $createException;
            }
        }
    }

    /** @param array<string, mixed> $payload */
    private function qrCode(array $payload): ?string
    {
        $qrCode = data_get($payload, 'qrcode.base64') ?? data_get($payload, 'base64');

        return is_string($qrCode) && str_starts_with($qrCode, 'data:image/') ? $qrCode : null;
    }

    private function markConnected(WhatsAppConnection $connection): void
    {
        $connection->update([
            'status' => 'connected',
            'connected_at' => $connection->connected_at ?? now(),
            'last_checked_at' => now(),
            'last_error' => null,
        ]);
    }

    private function instanceName(string $companyUuid): string
    {
        return 'agendaflow-'.Str::lower(str_replace('-', '', $companyUuid));
    }

    private function authorizeAccess(?User $user, CurrentCompany $currentCompany): void
    {
        abort_unless($user?->hasCompanyRole($currentCompany->id(), ['owner', 'admin']) === true, 403);
    }
}
