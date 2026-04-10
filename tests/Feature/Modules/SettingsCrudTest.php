<?php

namespace Tests\Feature\Modules;

use App\Models\Content;
use App\Models\ContentCategory;
use App\Models\ContentPlatform;
use App\Models\ContentType;
use App\Models\Idea;
use App\Models\IdeaCategory;
use App\Models\IdeaType;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsCrudTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // IdeaType
    // -------------------------------------------------------------------------

    public function test_user_can_create_idea_type(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->post(route('settings.idea-types.store'), [
                'name'  => 'Rock Progressivo',
                'color' => '#ff5733',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('idea_types', [
            'user_id' => $user->id,
            'name'    => 'Rock Progressivo',
            'color'   => '#ff5733',
        ]);
    }

    public function test_user_can_update_idea_type(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $type = IdeaType::create(['user_id' => $user->id, 'name' => 'Original', 'color' => '#000000']);

        $this->actingAs($user)
            ->put(route('settings.idea-types.update', $type), [
                'name'  => 'Atualizado',
                'color' => '#aabbcc',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('idea_types', ['id' => $type->id, 'name' => 'Atualizado', 'color' => '#aabbcc']);
    }

    public function test_user_can_update_another_users_idea_type_in_collaborative_mode(): void
    {
        /** @var User $owner */
        $owner = User::factory()->createOne();
        /** @var User $other */
        $other = User::factory()->createOne();
        $type = IdeaType::create(['user_id' => $owner->id, 'name' => 'Dono', 'color' => '#111111']);

        $this->actingAs($other)
            ->put(route('settings.idea-types.update', $type), [
                'name'  => 'Invasao',
                'color' => '#ffffff',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('idea_types', ['id' => $type->id, 'name' => 'Invasao']);
    }

    public function test_user_can_delete_idea_type_without_linked_ideas(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $type = IdeaType::create(['user_id' => $user->id, 'name' => 'Apagar', 'color' => '#cccccc']);

        $this->actingAs($user)
            ->delete(route('settings.idea-types.destroy', $type))
            ->assertRedirect();

        $this->assertDatabaseMissing('idea_types', ['id' => $type->id]);
    }

    public function test_user_cannot_delete_idea_type_with_linked_ideas(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $type = IdeaType::create(['user_id' => $user->id, 'name' => 'Vinculado', 'color' => '#dddddd']);
        Idea::create([
            'user_id'      => $user->id,
            'idea_type_id' => $type->id,
            'title'        => 'Ideia vinculada',
            'status'       => 'pending',
        ]);

        $this->actingAs($user)
            ->delete(route('settings.idea-types.destroy', $type))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('idea_types', ['id' => $type->id]);
    }

    public function test_idea_type_store_validates_color_format(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->post(route('settings.idea-types.store'), [
                'name'  => 'Teste',
                'color' => 'not-a-color',
            ])
            ->assertSessionHasErrors('color');
    }

    // -------------------------------------------------------------------------
    // IdeaCategory
    // -------------------------------------------------------------------------

    public function test_user_can_create_idea_category(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->post(route('settings.idea-categories.store'), ['name' => 'Letra'])
            ->assertRedirect();

        $this->assertDatabaseHas('idea_categories', ['user_id' => $user->id, 'name' => 'Letra']);
    }

    public function test_user_can_update_idea_category(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $cat = IdeaCategory::create(['user_id' => $user->id, 'name' => 'Antes']);

        $this->actingAs($user)
            ->put(route('settings.idea-categories.update', $cat), ['name' => 'Depois'])
            ->assertRedirect();

        $this->assertDatabaseHas('idea_categories', ['id' => $cat->id, 'name' => 'Depois']);
    }

    public function test_user_can_delete_idea_category(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $cat = IdeaCategory::create(['user_id' => $user->id, 'name' => 'Deletar']);

        $this->actingAs($user)
            ->delete(route('settings.idea-categories.destroy', $cat))
            ->assertRedirect();

        $this->assertDatabaseMissing('idea_categories', ['id' => $cat->id]);
    }

    public function test_user_can_delete_another_users_idea_category_in_collaborative_mode(): void
    {
        /** @var User $owner */
        $owner = User::factory()->createOne();
        /** @var User $other */
        $other = User::factory()->createOne();
        $cat = IdeaCategory::create(['user_id' => $owner->id, 'name' => 'Alheio']);

        $this->actingAs($other)
            ->delete(route('settings.idea-categories.destroy', $cat))
            ->assertRedirect();

        $this->assertDatabaseMissing('idea_categories', ['id' => $cat->id]);
    }

    // -------------------------------------------------------------------------
    // ContentPlatform
    // -------------------------------------------------------------------------

    public function test_user_can_create_content_platform(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->post(route('settings.content-platforms.store'), ['name' => 'YouTube'])
            ->assertRedirect();

        $this->assertDatabaseHas('content_platforms', ['user_id' => $user->id, 'name' => 'YouTube']);
    }

    public function test_user_can_update_content_platform(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $platform = ContentPlatform::create(['user_id' => $user->id, 'name' => 'Insta']);

        $this->actingAs($user)
            ->put(route('settings.content-platforms.update', $platform), ['name' => 'Instagram'])
            ->assertRedirect();

        $this->assertDatabaseHas('content_platforms', ['id' => $platform->id, 'name' => 'Instagram']);
    }

    public function test_user_can_delete_content_platform(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $platform = ContentPlatform::create(['user_id' => $user->id, 'name' => 'TikTok']);

        $this->actingAs($user)
            ->delete(route('settings.content-platforms.destroy', $platform))
            ->assertRedirect();

        $this->assertDatabaseMissing('content_platforms', ['id' => $platform->id]);
    }

    public function test_user_can_delete_another_users_content_platform_in_collaborative_mode(): void
    {
        /** @var User $owner */
        $owner = User::factory()->createOne();
        /** @var User $other */
        $other = User::factory()->createOne();
        $platform = ContentPlatform::create(['user_id' => $owner->id, 'name' => 'Spotify']);

        $this->actingAs($other)
            ->delete(route('settings.content-platforms.destroy', $platform))
            ->assertRedirect();

        $this->assertDatabaseMissing('content_platforms', ['id' => $platform->id]);
    }

    // -------------------------------------------------------------------------
    // ContentType
    // -------------------------------------------------------------------------

    public function test_user_can_create_content_type(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->post(route('settings.content-types.store'), ['name' => 'Vídeo'])
            ->assertRedirect();

        $this->assertDatabaseHas('content_types', ['user_id' => $user->id, 'name' => 'Vídeo']);
    }

    public function test_user_can_update_content_type(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $type = ContentType::create(['user_id' => $user->id, 'name' => 'Post']);

        $this->actingAs($user)
            ->put(route('settings.content-types.update', $type), ['name' => 'Reels'])
            ->assertRedirect();

        $this->assertDatabaseHas('content_types', ['id' => $type->id, 'name' => 'Reels']);
    }

    public function test_user_can_delete_content_type(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $type = ContentType::create(['user_id' => $user->id, 'name' => 'Story']);

        $this->actingAs($user)
            ->delete(route('settings.content-types.destroy', $type))
            ->assertRedirect();

        $this->assertDatabaseMissing('content_types', ['id' => $type->id]);
    }

    // -------------------------------------------------------------------------
    // ContentCategory
    // -------------------------------------------------------------------------

    public function test_user_can_create_content_category(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->post(route('settings.content-categories.store'), [
                'name'  => 'Educativo',
                'color' => '#123456',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('content_categories', [
            'user_id' => $user->id,
            'name'    => 'Educativo',
            'color'   => '#123456',
        ]);
    }

    public function test_user_can_update_content_category(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $cat = ContentCategory::create(['user_id' => $user->id, 'name' => 'Antes', 'color' => '#aaaaaa']);

        $this->actingAs($user)
            ->put(route('settings.content-categories.update', $cat), [
                'name'  => 'Depois',
                'color' => '#bbbbbb',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('content_categories', ['id' => $cat->id, 'name' => 'Depois', 'color' => '#bbbbbb']);
    }

    public function test_user_can_delete_content_category(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $cat = ContentCategory::create(['user_id' => $user->id, 'name' => 'Remover', 'color' => '#eeeeee']);

        $this->actingAs($user)
            ->delete(route('settings.content-categories.destroy', $cat))
            ->assertRedirect();

        $this->assertDatabaseMissing('content_categories', ['id' => $cat->id]);
    }

    // -------------------------------------------------------------------------
    // TaskStatus
    // -------------------------------------------------------------------------

    public function test_user_can_create_task_status(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->post(route('settings.task-statuses.store'), [
                'name'  => 'Em Revisão',
                'color' => '#00aaff',
                'order' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_statuses', [
            'user_id' => $user->id,
            'name'    => 'Em Revisão',
            'color'   => '#00aaff',
            'order'   => 1,
        ]);
    }

    public function test_task_status_order_auto_increments_when_omitted(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        TaskStatus::create(['user_id' => $user->id, 'name' => 'Primeiro', 'color' => '#000001', 'order' => 1]);

        $this->actingAs($user)
            ->post(route('settings.task-statuses.store'), [
                'name'  => 'Segundo',
                'color' => '#000002',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_statuses', [
            'user_id' => $user->id,
            'name'    => 'Segundo',
            'order'   => 2,
        ]);
    }

    public function test_user_can_update_task_status(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $status = TaskStatus::create(['user_id' => $user->id, 'name' => 'Backlog', 'color' => '#ff0000', 'order' => 1]);

        $this->actingAs($user)
            ->put(route('settings.task-statuses.update', $status), [
                'name'  => 'Em Andamento',
                'color' => '#00ff00',
                'order' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_statuses', [
            'id'    => $status->id,
            'name'  => 'Em Andamento',
            'order' => 2,
        ]);
    }

    public function test_user_cannot_delete_task_status_with_linked_tasks(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $status = TaskStatus::create(['user_id' => $user->id, 'name' => 'Ocupado', 'color' => '#ff9900', 'order' => 1]);
        Task::create([
            'user_id'        => $user->id,
            'task_status_id' => $status->id,
            'title'          => 'Tarefa vinculada',
            'priority'       => 'medium',
        ]);

        $this->actingAs($user)
            ->delete(route('settings.task-statuses.destroy', $status))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('task_statuses', ['id' => $status->id]);
    }

    public function test_user_can_delete_task_status_without_linked_tasks(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $status = TaskStatus::create(['user_id' => $user->id, 'name' => 'Vazio', 'color' => '#888888', 'order' => 5]);

        $this->actingAs($user)
            ->delete(route('settings.task-statuses.destroy', $status))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_statuses', ['id' => $status->id]);
    }

    public function test_user_can_delete_another_users_task_status_in_collaborative_mode(): void
    {
        /** @var User $owner */
        $owner = User::factory()->createOne();
        /** @var User $other */
        $other = User::factory()->createOne();
        $status = TaskStatus::create(['user_id' => $owner->id, 'name' => 'Privado', 'color' => '#555555', 'order' => 1]);

        $this->actingAs($other)
            ->delete(route('settings.task-statuses.destroy', $status))
            ->assertRedirect();

        $this->assertDatabaseMissing('task_statuses', ['id' => $status->id]);
    }

    public function test_user_can_reorder_task_statuses(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $s1 = TaskStatus::create(['user_id' => $user->id, 'name' => 'A', 'color' => '#aaaaaa', 'order' => 1]);
        $s2 = TaskStatus::create(['user_id' => $user->id, 'name' => 'B', 'color' => '#bbbbbb', 'order' => 2]);
        $s3 = TaskStatus::create(['user_id' => $user->id, 'name' => 'C', 'color' => '#cccccc', 'order' => 3]);

        $this->actingAs($user)
            ->patch(route('settings.task-statuses.reorder'), [
                'ordered_ids' => [$s3->id, $s1->id, $s2->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_statuses', ['id' => $s3->id, 'order' => 1]);
        $this->assertDatabaseHas('task_statuses', ['id' => $s1->id, 'order' => 2]);
        $this->assertDatabaseHas('task_statuses', ['id' => $s2->id, 'order' => 3]);
    }

    public function test_user_can_reorder_another_users_task_statuses_in_collaborative_mode(): void
    {
        /** @var User $owner */
        $owner = User::factory()->createOne();
        /** @var User $attacker */
        $attacker = User::factory()->createOne();
        $s1 = TaskStatus::create(['user_id' => $owner->id, 'name' => 'S1', 'color' => '#111111', 'order' => 1]);
        $s2 = TaskStatus::create(['user_id' => $owner->id, 'name' => 'S2', 'color' => '#222222', 'order' => 2]);

        $this->actingAs($attacker)
            ->patch(route('settings.task-statuses.reorder'), [
                'ordered_ids' => [$s2->id, $s1->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_statuses', ['id' => $s2->id, 'order' => 1]);
        $this->assertDatabaseHas('task_statuses', ['id' => $s1->id, 'order' => 2]);
    }

    // -------------------------------------------------------------------------
    // Auth guard — unauthenticated visitors are redirected
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_access_settings_store(): void
    {
        $this->post(route('settings.idea-types.store'), ['name' => 'X', 'color' => '#ff0000'])
            ->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_cannot_reorder_task_statuses(): void
    {
        $this->patch(route('settings.task-statuses.reorder'), ['ordered_ids' => []])
            ->assertRedirect(route('login'));
    }
}
