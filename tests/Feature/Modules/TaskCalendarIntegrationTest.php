<?php

namespace Tests\Feature\Modules;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TaskCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_index_defaults_to_current_users_assigned_tasks_and_unassigned(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        /** @var User $otherUser */
        $otherUser = User::factory()->createOne();

        $status = TaskStatus::create(['user_id' => $user->id, 'name' => 'Pendente', 'color' => '#94a3b8', 'order' => 1]);

        // Tarefa criada por outro usuário, mas atribuída ao usuário logado (deve aparecer no default)
        Task::create([
            'user_id' => $otherUser->id,
            'assigned_user_id' => $user->id,
            'related_type' => 'administrative',
            'title' => 'Task atribuída ao usuário logado',
            'task_status_id' => $status->id,
            'priority' => 'medium',
        ]);

        // Tarefa de outro usuário atribuída a "todos" (assigned_user_id NULL, deve aparecer no default)
        Task::create([
            'user_id' => $otherUser->id,
            'related_type' => 'administrative',
            'title' => 'Task atribuída a todos',
            'task_status_id' => $status->id,
            'priority' => 'medium',
            'assigned_user_id' => null,
        ]);

        // Tarefa criada pelo usuário logado, mas atribuída a outro usuário (não deve aparecer no default)
        Task::create([
            'user_id' => $user->id,
            'assigned_user_id' => $otherUser->id,
            'related_type' => 'administrative',
            'title' => 'Task exclusiva de outro usuário',
            'task_status_id' => $status->id,
            'priority' => 'medium',
        ]);

        $inertiaVersion = app(HandleInertiaRequests::class)->version(Request::create(route('tasks.index'), 'GET'));

        $defaultResponse = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $inertiaVersion,
            ])
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Tasks/Index');

        $defaultTitles = collect($defaultResponse->json('props.tasks.data'))->pluck('title');
        $this->assertTrue($defaultTitles->contains('Task atribuída ao usuário logado'), 'A tarefa atribuída ao usuário deve aparecer');
        $this->assertTrue($defaultTitles->contains('Task atribuída a todos'), 'Tarefa sem responsável deve aparecer no default');
        $this->assertFalse($defaultTitles->contains('Task exclusiva de outro usuário'), 'Tarefa exclusiva de outro usuário não deve aparecer');

        $allResponse = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $inertiaVersion,
            ])
            ->get(route('tasks.index', ['assigned_user_id' => '__all__']))
            ->assertOk();

        $allTitles = collect($allResponse->json('props.tasks.data'))->pluck('title');
        $this->assertTrue($allTitles->contains('Task atribuída ao usuário logado'));
        $this->assertTrue($allTitles->contains('Task atribuída a todos'));
        $this->assertTrue($allTitles->contains('Task exclusiva de outro usuário'), 'Com __all__, todas as tarefas devem aparecer');
    }

    public function test_tasks_calendar_respects_filters_and_includes_running_deadlines(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $pending = TaskStatus::create(['user_id' => $user->id, 'name' => 'Pendente', 'color' => '#94a3b8', 'order' => 1]);
        $running = TaskStatus::create(['user_id' => $user->id, 'name' => 'Em Execucao', 'color' => '#3b82f6', 'order' => 2]);

        Task::create([
            'user_id' => $user->id,
            'related_type' => 'administrative',
            'title' => 'Task agendada alta',
            'task_status_id' => $pending->id,
            'priority' => 'high',
            'scheduled_for' => now()->addHours(3),
        ]);

        Task::create([
            'user_id' => $user->id,
            'related_type' => 'administrative',
            'title' => 'Task deadline alta',
            'task_status_id' => $running->id,
            'priority' => 'high',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        Task::create([
            'user_id' => $user->id,
            'related_type' => 'administrative',
            'title' => 'Task agendada baixa',
            'task_status_id' => $pending->id,
            'priority' => 'low',
            'scheduled_for' => now()->addHours(6),
        ]);

        $inertiaVersion = app(HandleInertiaRequests::class)->version(Request::create(route('tasks.index'), 'GET'));

        $response = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $inertiaVersion,
            ])
            ->get(route('tasks.index', ['priority' => 'high']))
            ->assertOk()
            ->assertJsonPath('component', 'Tasks/Index');

        $calendarItems = collect($response->json('props.taskCalendarItems'));

        $this->assertCount(2, $calendarItems);
        $this->assertTrue($calendarItems->contains(fn (array $item) => $item['title'] === 'Task agendada alta' && $item['type'] === 'task_scheduled'));
        $this->assertTrue($calendarItems->contains(fn (array $item) => $item['title'] === 'Deadline: Task deadline alta' && $item['type'] === 'task_deadline'));
        $this->assertFalse($calendarItems->contains(fn (array $item) => str_contains($item['title'], 'baixa')));
    }

    public function test_general_calendar_includes_only_current_users_assigned_and_unassigned_task_items(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        /** @var User $otherUser */
        $otherUser = User::factory()->createOne();

        $running = TaskStatus::create(['user_id' => $user->id, 'name' => 'Em Execucao', 'color' => '#3b82f6', 'order' => 1]);
        $otherRunning = TaskStatus::create(['user_id' => $otherUser->id, 'name' => 'Em Execucao', 'color' => '#3b82f6', 'order' => 1]);

        Task::create([
            'user_id' => $otherUser->id,
            'assigned_user_id' => $user->id,
            'related_type' => 'administrative',
            'title' => 'Minha tarefa agendada',
            'task_status_id' => $running->id,
            'priority' => 'medium',
            'scheduled_for' => now()->addHours(2),
        ]);

        Task::create([
            'user_id' => $user->id,
            'assigned_user_id' => null,
            'related_type' => 'administrative',
            'title' => 'Meu deadline',
            'task_status_id' => $running->id,
            'priority' => 'medium',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Task::create([
            'user_id' => $user->id,
            'assigned_user_id' => $otherUser->id,
            'related_type' => 'administrative',
            'title' => 'Tarefa de outro usuário',
            'task_status_id' => $otherRunning->id,
            'priority' => 'medium',
            'scheduled_for' => now()->addHours(5),
        ]);

        $inertiaVersion = app(HandleInertiaRequests::class)->version(Request::create(route('calendar.index'), 'GET'));

        $response = $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $inertiaVersion,
            ])
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Calendar/Index');

        $taskItems = collect($response->json('props.calendarItems'))
            ->filter(fn (array $item) => str_starts_with((string) ($item['type'] ?? ''), 'task_'))
            ->values();

        $this->assertCount(2, $taskItems);
        $this->assertTrue($taskItems->contains(fn (array $item) => ($item['title'] ?? '') === 'Minha tarefa agendada'));
        $this->assertTrue($taskItems->contains(fn (array $item) => ($item['title'] ?? '') === 'Deadline: Meu deadline'));
        $this->assertFalse($taskItems->contains(fn (array $item) => str_contains((string) ($item['title'] ?? ''), 'outro usuário')));
    }
}
