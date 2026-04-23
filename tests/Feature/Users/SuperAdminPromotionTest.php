<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_promote_another_user_to_admin(): void
    {
        /** @var Authenticatable&User $admin */
        $admin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);
        $target = User::factory()->createOne([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($admin)->patch(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'is_admin' => true,
        ]);

        $response->assertRedirect(route('users.edit', $target));
        $this->assertFalse($target->fresh()->is_admin);
    }

    public function test_super_admin_can_promote_user_to_admin(): void
    {
        /** @var Authenticatable&User $superAdmin */
        $superAdmin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        $target = User::factory()->createOne([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($superAdmin)->patch(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'is_admin' => true,
        ]);

        $response->assertRedirect(route('users.edit', $target));
        $this->assertTrue($target->fresh()->is_admin);
    }

    public function test_admin_cannot_create_user_as_admin(): void
    {
        /** @var Authenticatable&User $admin */
        $admin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Novo Usuario',
            'email' => 'novo.usuario@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'theme' => 'system',
            'is_admin' => true,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'novo.usuario@example.com',
            'is_admin' => false,
            'is_super_admin' => false,
        ]);
    }

    public function test_admin_cannot_open_edit_screen_of_super_admin(): void
    {
        /** @var Authenticatable&User $superAdmin */
        $superAdmin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        /** @var Authenticatable&User $admin */
        $admin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('users.edit', $superAdmin));

        $response->assertForbidden();
    }

    public function test_admin_cannot_delete_super_admin(): void
    {
        /** @var Authenticatable&User $superAdmin */
        $superAdmin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        /** @var Authenticatable&User $admin */
        $admin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('users.destroy', $superAdmin));

        $response->assertForbidden();
        $this->assertNotNull($superAdmin->fresh());
    }

    public function test_super_admin_cannot_remove_own_admin_flag(): void
    {
        /** @var Authenticatable&User $superAdmin */
        $superAdmin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($superAdmin)->patch(route('users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'is_admin' => false,
        ]);

        $response->assertRedirect(route('users.edit', $superAdmin));
        $this->assertTrue((bool) $superAdmin->fresh()?->is_admin);
        $this->assertTrue((bool) $superAdmin->fresh()?->is_super_admin);
    }

    public function test_super_admin_user_cannot_be_deleted(): void
    {
        /** @var Authenticatable&User $superAdmin */
        $superAdmin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);
        /** @var Authenticatable&User $otherSuperAdmin */
        $otherSuperAdmin = User::factory()->createOne([
            'is_admin' => true,
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($otherSuperAdmin)->delete(route('users.destroy', $superAdmin));

        $response->assertStatus(422);
        $this->assertNotNull($superAdmin->fresh());
    }
}
