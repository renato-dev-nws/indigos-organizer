<?php

namespace Tests\Feature\Modules;

use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_status_update_requires_status_from_same_user(): void
    {
        /** @var User $owner */
        $owner = User::factory()->createOne();
        /** @var User $other */
        $other = User::factory()->createOne();

        $ownerStatusA = TaskStatus::create(['user_id' => $owner->id, 'name' => 'Pendente', 'color' => '#94a3b8', 'order' => 1]);
        $ownerStatusB = TaskStatus::create(['user_id' => $owner->id, 'name' => 'Concluido', 'color' => '#10b981', 'order' => 2]);
        $otherStatus = TaskStatus::create(['user_id' => $other->id, 'name' => 'Outro', 'color' => '#3b82f6', 'order' => 1]);

        $task = Task::create([
            'user_id' => $owner->id,
            'title' => 'Task 1',
            'description' => 'desc',
            'type' => 'administrative',
            'task_status_id' => $ownerStatusA->id,
            'priority' => 'medium',
        ]);

        $invalidResponse = $this->actingAs($owner)->patch(route('tasks.status', $task), [
            'task_status_id' => $otherStatus->id,
        ]);

        $invalidResponse->assertStatus(422);

        $validResponse = $this->actingAs($owner)->patch(route('tasks.status', $task), [
            'task_status_id' => $ownerStatusB->id,
        ]);

        $validResponse->assertStatus(302);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'task_status_id' => $ownerStatusB->id,
        ]);
    }

    public function test_user_cannot_update_status_of_task_from_another_user(): void
    {
        /** @var User $owner */
        $owner = User::factory()->createOne();
        /** @var User $other */
        $other = User::factory()->createOne();

        $ownerStatus = TaskStatus::create(['user_id' => $owner->id, 'name' => 'Pendente', 'color' => '#94a3b8', 'order' => 1]);
        $otherStatus = TaskStatus::create(['user_id' => $other->id, 'name' => 'Outro', 'color' => '#3b82f6', 'order' => 1]);

        $task = Task::create([
            'user_id' => $owner->id,
            'title' => 'Task owner',
            'description' => 'desc',
            'type' => 'administrative',
            'task_status_id' => $ownerStatus->id,
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($other)->patch(route('tasks.status', $task), [
            'task_status_id' => $otherStatus->id,
        ]);

        $response->assertForbidden();
    }
}
