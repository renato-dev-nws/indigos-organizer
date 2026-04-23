<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_available_when_there_are_no_users(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_first_user_becomes_super_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_admin' => true,
            'is_super_admin' => true,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_screen_is_not_available_after_first_user(): void
    {
        User::factory()->createOne();

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_can_not_register_after_first_user(): void
    {
        User::factory()->createOne();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertStatus(404);
    }
}
