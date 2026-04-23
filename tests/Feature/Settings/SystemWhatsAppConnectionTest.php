<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Services\EvolutionApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SystemWhatsAppConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_whatsapp_qr_code_from_settings_endpoint(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne(['is_admin' => true]);

        $service = Mockery::mock(EvolutionApiService::class);
        $service->shouldReceive('fetchQrCode')
            ->once()
            ->with('main', null)
            ->andReturn([
                'ok' => true,
                'status' => 200,
                'message' => '',
                'data' => [
                    'base64' => 'iVBORw0KGgoAAAANSUhEUgA',
                    'pairingCode' => 'ABCDEFGH',
                    'instance' => [
                        'state' => 'connecting',
                    ],
                ],
            ]);
        $service->shouldReceive('resolveInstanceName')
            ->once()
            ->with('main')
            ->andReturn('main');

        $this->app->instance(EvolutionApiService::class, $service);

        $this->actingAs($admin)
            ->getJson(route('settings.system.whatsapp.qr', ['instance' => 'main']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('instance', 'main')
            ->assertJsonPath('data.base64', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgA')
            ->assertJsonPath('data.pairingCode', 'ABCDEFGH')
            ->assertJsonPath('data.connectionState', 'connecting');
    }

    public function test_admin_can_poll_whatsapp_connection_status_from_settings_endpoint(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne(['is_admin' => true]);

        $service = Mockery::mock(EvolutionApiService::class);
        $service->shouldReceive('fetchConnectionState')
            ->once()
            ->with('main')
            ->andReturn([
                'ok' => true,
                'status' => 200,
                'message' => '',
                'data' => [
                    'instance' => [
                        'state' => 'open',
                    ],
                ],
            ]);
        $service->shouldReceive('fetchInstanceInfo')
            ->once()
            ->with('main')
            ->andReturn([
                'ok' => true,
                'status' => 200,
                'message' => '',
                'data' => [
                    'ownerJid' => '5511999999999@s.whatsapp.net',
                    'profileName' => 'Test User',
                ],
            ]);
        $service->shouldReceive('resolveInstanceName')
            ->once()
            ->with('main')
            ->andReturn('main');

        $this->app->instance(EvolutionApiService::class, $service);

        $this->actingAs($admin)
            ->getJson(route('settings.system.whatsapp.status', ['instance' => 'main']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('instance', 'main')
            ->assertJsonPath('data.state', 'open')
            ->assertJsonPath('data.ownerJid', '5511999999999@s.whatsapp.net')
            ->assertJsonPath('data.profileName', 'Test User');
    }

    public function test_admin_can_send_whatsapp_test_message(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne(['is_admin' => true]);

        $service = Mockery::mock(EvolutionApiService::class);
        $service->shouldReceive('sendTextMessage')
            ->once()
            ->with('5511999999999', Mockery::type('string'), 'main')
            ->andReturn(true);

        $this->app->instance(EvolutionApiService::class, $service);

        $this->actingAs($admin)
            ->postJson(route('settings.system.whatsapp.send-test'), [
                'number' => '5511999999999',
                'message' => 'Teste',
                'instance' => 'main',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_reconnect_whatsapp_instance_from_settings_endpoint(): void
    {
        /** @var User $admin */
        $admin = User::factory()->createOne(['is_admin' => true]);

        $service = Mockery::mock(EvolutionApiService::class);
        $service->shouldReceive('reconnectInstance')
            ->once()
            ->with('main', null)
            ->andReturn([
                'ok' => true,
                'status' => 200,
                'message' => 'Instancia reiniciada.',
                'data' => [
                    'code' => 'ABCD-EFGH',
                    'pairingCode' => '11223344',
                    'instance' => [
                        'state' => 'connecting',
                    ],
                ],
            ]);
        $service->shouldReceive('resolveInstanceName')
            ->once()
            ->with('main')
            ->andReturn('main');

        $this->app->instance(EvolutionApiService::class, $service);

        $this->actingAs($admin)
            ->postJson(route('settings.system.whatsapp.reconnect'), ['instance' => 'main'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('instance', 'main')
            ->assertJsonPath('message', 'Instancia reiniciada.')
            ->assertJsonPath('data.code', 'ABCD-EFGH')
            ->assertJsonPath('data.pairingCode', '11223344')
            ->assertJsonPath('data.connectionState', 'connecting');
    }

    public function test_non_admin_cannot_access_whatsapp_connection_endpoints(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson(route('settings.system.whatsapp.status', ['instance' => 'main']))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('settings.system.whatsapp.reconnect'), ['instance' => 'main'])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('settings.system.whatsapp.send-test'), ['number' => '5511999999999', 'message' => 'test'])
            ->assertForbidden();
    }
}
