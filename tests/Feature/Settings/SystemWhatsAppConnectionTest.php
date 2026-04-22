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
            ->with('main')
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
            ->assertJsonPath('data.state', 'open');
    }

    public function test_non_admin_cannot_access_whatsapp_connection_endpoints(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson(route('settings.system.whatsapp.status', ['instance' => 'main']))
            ->assertForbidden();
    }
}
