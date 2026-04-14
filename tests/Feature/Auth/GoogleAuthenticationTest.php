<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_gmail_user_can_login_with_google_callback(): void
    {
        $user = User::factory()->createOne([
            'email' => 'musico@gmail.com',
        ]);

        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn('musico@gmail.com');

        Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_google_callback_rejects_non_gmail_addresses(): void
    {
        User::factory()->createOne([
            'email' => 'artist@empresa.com',
        ]);

        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn('artist@empresa.com');

        Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

        $response = $this->get(route('auth.google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    }

    public function test_google_callback_rejects_unregistered_gmail_user(): void
    {
        $socialiteUser = Mockery::mock(SocialiteUserContract::class);
        $socialiteUser->shouldReceive('getEmail')->andReturn('naocadastrado@gmail.com');

        Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

        $response = $this->get(route('auth.google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('oauth');
    }
}
