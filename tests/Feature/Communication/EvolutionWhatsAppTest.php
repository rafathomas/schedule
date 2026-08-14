<?php

declare(strict_types=1);

namespace Tests\Feature\Communication;

use App\Models\WhatsAppConnection;
use App\Services\Messaging\EvolutionApiClient;
use App\Services\Messaging\EvolutionMessagingProvider;
use App\Services\Messaging\WhatsAppService;
use App\Support\Tenancy\CurrentCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithCompanies;
use Tests\TestCase;

class EvolutionWhatsAppTest extends TestCase
{
    use InteractsWithCompanies, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.evolution.base_url', 'http://evolution.test');
        config()->set('services.evolution.api_key', 'test-api-key');
    }

    public function test_owner_can_create_an_isolated_instance_and_receive_the_qr_code(): void
    {
        [$company, $owner] = $this->companyUser();
        Http::fake([
            'evolution.test/instance/create' => Http::response([
                'instance' => ['instanceName' => 'created'],
                'qrcode' => ['base64' => 'data:image/png;base64,dGVzdA=='],
            ], 201),
        ]);

        $this->actingAs($owner)
            ->post(route('communication.whatsapp.connect'))
            ->assertRedirect()
            ->assertSessionHas('whatsapp_qr_code', 'data:image/png;base64,dGVzdA==');

        $connection = WhatsAppConnection::query()->withoutGlobalScopes()->firstOrFail();
        $this->assertSame($company->getKey(), $connection->company_id);
        $this->assertSame('connecting', $connection->status);
        $this->assertStringStartsWith('agendaflow-', $connection->instance_name);

        $this->actingAs($owner)
            ->get(route('communication.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('whatsapp.available', true)
                ->where('whatsapp.status', 'connecting')
                ->where('whatsapp.qr_code', 'data:image/png;base64,dGVzdA=='));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://evolution.test/instance/create'
            && $request->hasHeader('apikey', 'test-api-key')
            && $request['integration'] === 'WHATSAPP-BAILEYS');
    }

    public function test_status_check_marks_the_company_connection_as_connected(): void
    {
        [$company, $owner] = $this->companyUser();
        WhatsAppConnection::query()->withoutGlobalScopes()->create([
            'company_id' => $company->getKey(),
            'instance_name' => 'agendaflow-company-a',
            'status' => 'connecting',
        ]);
        Http::fake([
            'evolution.test/instance/connectionState/agendaflow-company-a' => Http::response([
                'instance' => ['instanceName' => 'agendaflow-company-a', 'state' => 'open'],
            ]),
        ]);

        $this->actingAs($owner)
            ->getJson(route('communication.whatsapp.status'))
            ->assertOk()
            ->assertJsonPath('status', 'connected');

        $this->assertDatabaseHas('whatsapp_connections', [
            'company_id' => $company->getKey(),
            'status' => 'connected',
        ]);
    }

    public function test_evolution_provider_sends_with_the_current_company_instance(): void
    {
        [$company] = $this->companyUser();
        app(CurrentCompany::class)->set($company);
        WhatsAppConnection::query()->create([
            'company_id' => $company->getKey(),
            'instance_name' => 'agendaflow-company-a',
            'status' => 'connected',
        ]);
        Http::fake([
            'evolution.test/message/sendText/agendaflow-company-a' => Http::response([
                'key' => ['id' => 'MESSAGE-123'],
                'status' => 'PENDING',
            ]),
        ]);

        $service = new WhatsAppService(new EvolutionMessagingProvider(
            app(EvolutionApiClient::class),
            app(CurrentCompany::class),
        ));
        $result = $service->sendMessage('(11) 99999-8888', 'Confirme seu horário.');

        $this->assertSame('MESSAGE-123', $result->messageId);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://evolution.test/message/sendText/agendaflow-company-a'
            && $request['number'] === '5511999998888'
            && $request['text'] === 'Confirme seu horário.');
    }

    public function test_professional_cannot_manage_the_whatsapp_connection(): void
    {
        [, $professional] = $this->companyUser('professional');

        $this->actingAs($professional)
            ->post(route('communication.whatsapp.connect'))
            ->assertForbidden();
    }
}
