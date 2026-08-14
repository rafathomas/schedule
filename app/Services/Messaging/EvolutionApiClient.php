<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Exceptions\EvolutionApiException;
use App\ValueObjects\ProviderMessageResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class EvolutionApiClient
{
    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '' && $this->apiKey() !== '';
    }

    /** @return array<string, mixed> */
    public function createInstance(string $instanceName): array
    {
        return $this->sendRequest(
            fn (PendingRequest $request): Response => $request->post('/instance/create', [
                'instanceName' => $instanceName,
                'integration' => 'WHATSAPP-BAILEYS',
                'qrcode' => true,
            ]),
            'Não foi possível criar a conexão com o WhatsApp.',
        );
    }

    /** @return array<string, mixed> */
    public function connect(string $instanceName): array
    {
        return $this->sendRequest(
            fn (PendingRequest $request): Response => $request->get('/instance/connect/'.rawurlencode($instanceName)),
            'Não foi possível gerar o QR Code do WhatsApp.',
        );
    }

    public function connectionState(string $instanceName): string
    {
        $payload = $this->sendRequest(
            fn (PendingRequest $request): Response => $request->get('/instance/connectionState/'.rawurlencode($instanceName)),
            'Não foi possível consultar a conexão do WhatsApp.',
        );

        return strtolower((string) data_get($payload, 'instance.state', 'close'));
    }

    public function logout(string $instanceName): void
    {
        $this->sendRequest(
            fn (PendingRequest $request): Response => $request->delete('/instance/logout/'.rawurlencode($instanceName)),
            'Não foi possível desconectar o WhatsApp.',
        );
    }

    public function deleteInstance(string $instanceName): void
    {
        $this->sendRequest(
            fn (PendingRequest $request): Response => $request->delete('/instance/delete/'.rawurlencode($instanceName)),
            'Não foi possível remover a instância de teste do WhatsApp.',
        );
    }

    public function sendText(string $instanceName, string $recipient, string $message): ProviderMessageResult
    {
        $payload = $this->sendRequest(
            fn (PendingRequest $request): Response => $request->post('/message/sendText/'.rawurlencode($instanceName), [
                'number' => $recipient,
                'text' => $message,
                'delay' => (int) config('services.evolution.message_delay', 800),
                'linkPreview' => true,
            ]),
            'A Evolution API não conseguiu enviar a mensagem.',
        );
        $messageId = data_get($payload, 'key.id');

        if (! is_string($messageId) || $messageId === '') {
            throw new EvolutionApiException('A Evolution API aceitou o envio, mas não retornou o identificador da mensagem.');
        }

        return new ProviderMessageResult($messageId);
    }

    /**
     * @param  callable(PendingRequest): Response  $callback
     * @return array<string, mixed>
     */
    private function sendRequest(callable $callback, string $fallbackMessage): array
    {
        if (! $this->isConfigured()) {
            throw new EvolutionApiException('Configure EVOLUTION_API_URL e EVOLUTION_API_KEY antes de conectar o WhatsApp.');
        }

        try {
            $response = $callback($this->request());
            $response->throw();
        } catch (ConnectionException) {
            throw new EvolutionApiException('A Evolution API está indisponível. Verifique se o serviço está em execução.');
        } catch (RequestException $exception) {
            $message = data_get($exception->response->json(), 'error.message')
                ?? data_get($exception->response->json(), 'message');

            throw new EvolutionApiException(is_string($message) && $message !== '' ? $message : $fallbackMessage);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new EvolutionApiException('A Evolution API retornou uma resposta inválida.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->asJson()
            ->withHeader('apikey', $this->apiKey())
            ->connectTimeout((int) config('services.evolution.connect_timeout', 5))
            ->timeout((int) config('services.evolution.timeout', 15));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.evolution.base_url'), '/');
    }

    private function apiKey(): string
    {
        return (string) config('services.evolution.api_key');
    }
}
