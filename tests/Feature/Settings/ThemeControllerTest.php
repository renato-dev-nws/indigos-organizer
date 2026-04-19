<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_theme_and_sets_success_flash_when_theme_changes(): void
    {
        /** @var Authenticatable&User $user */
        $user = User::factory()->createOne([
            'theme' => 'dark',
        ]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->put(route('settings.theme'), [
                'theme' => 'light',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Tema atualizado com sucesso.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'theme' => 'light',
        ]);
    }

    public function test_it_does_not_set_success_flash_when_theme_value_did_not_change(): void
    {
        /** @var Authenticatable&User $user */
        $user = User::factory()->createOne([
            'theme' => 'dark',
        ]);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->put(route('settings.theme'), [
                'theme' => 'dark',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'theme' => 'dark',
        ]);
    }
}
