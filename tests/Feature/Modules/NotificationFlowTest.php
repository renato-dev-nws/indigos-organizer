<?php

namespace Tests\Feature\Modules;

use App\Jobs\DispatchDueSoonTasksNotificationsJob;
use App\Jobs\DispatchTaskReminderNotificationsJob;
use App\Models\Idea;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Notifications\IdeaOnBoardNotification;
use App\Notifications\IdeaVotedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskDueSoonNotification;
use App\Notifications\TaskReminderNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_assignment_dispatches_notification_to_assigned_user(): void
    {
        $owner = User::factory()->createOne();
        $assignee = User::factory()->createOne();

        $status = TaskStatus::create([
            'user_id' => $owner->id,
            'name' => 'Pendente',
            'color' => '#94a3b8',
            'order' => 1,
        ]);

        $task = Task::create([
            'user_id' => $owner->id,
            'assigned_user_id' => $assignee->id,
            'related_type' => 'administrative',
            'title' => 'Tarefa atribuida',
            'task_status_id' => $status->id,
            'priority' => 'medium',
        ]);

        $this->assertDatabaseHas('notifications', [
            'type' => TaskAssignedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $assignee->id,
        ]);

        $this->assertNotNull($task->fresh()?->assignment_notified_at);
    }

    public function test_due_soon_job_dispatches_notification_for_tasks_within_window(): void
    {
        $owner = User::factory()->createOne();
        $assignee = User::factory()->createOne();

        $status = TaskStatus::create([
            'user_id' => $owner->id,
            'name' => 'Pendente',
            'color' => '#94a3b8',
            'order' => 1,
        ]);

        $task = Task::create([
            'user_id' => $owner->id,
            'assigned_user_id' => $assignee->id,
            'related_type' => 'administrative',
            'title' => 'Tarefa vencendo',
            'task_status_id' => $status->id,
            'priority' => 'medium',
            'due_date' => now()->addDay()->toDateString(),
        ]);

        (new DispatchDueSoonTasksNotificationsJob)->handle();

        $this->assertDatabaseHas('notifications', [
            'type' => TaskDueSoonNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $assignee->id,
        ]);

        $this->assertDatabaseHas('task_user_notification_logs', [
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'event_type' => 'due_soon',
        ]);
    }

    public function test_reminder_job_dispatches_notification_when_reminder_is_due(): void
    {
        $owner = User::factory()->createOne();
        $assignee = User::factory()->createOne();

        $status = TaskStatus::create([
            'user_id' => $owner->id,
            'name' => 'Pendente',
            'color' => '#94a3b8',
            'order' => 1,
        ]);

        $task = Task::create([
            'user_id' => $owner->id,
            'assigned_user_id' => $assignee->id,
            'related_type' => 'administrative',
            'title' => 'Tarefa com lembrete',
            'task_status_id' => $status->id,
            'priority' => 'medium',
            'reminder_at' => now()->subMinute(),
        ]);

        (new DispatchTaskReminderNotificationsJob)->handle();

        $this->assertDatabaseHas('notifications', [
            'type' => TaskReminderNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $assignee->id,
        ]);

        $this->assertDatabaseHas('task_user_notification_logs', [
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'event_type' => 'reminder',
        ]);
    }

    public function test_task_update_notifies_only_newly_added_assignees(): void
    {
        /** @var Authenticatable $owner */
        $owner = User::factory()->createOne();
        $assigneeA = User::factory()->createOne();
        $assigneeB = User::factory()->createOne();

        $status = TaskStatus::create([
            'user_id' => $owner->id,
            'name' => 'Pendente',
            'color' => '#94a3b8',
            'order' => 1,
        ]);

        $task = Task::create([
            'user_id' => $owner->id,
            'assigned_user_id' => $assigneeA->id,
            'related_type' => 'administrative',
            'title' => 'Tarefa para atualizar',
            'task_status_id' => $status->id,
            'priority' => 'medium',
        ]);

        $this->assertDatabaseCount('notifications', 1);

        $this->actingAs($owner)->put(route('tasks.update', $task), [
            'assigned_user_ids' => [$assigneeA->id],
            'related_type' => 'administrative',
            'title' => 'Tarefa para atualizar - editada',
            'description' => 'Descricao',
            'task_status_id' => $status->id,
            'priority' => 'medium',
            'archived' => false,
            'scheduled_for' => null,
            'due_date' => null,
            'reminder_at' => null,
            'subtasks' => [],
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseCount('notifications', 1);

        $this->actingAs($owner)->put(route('tasks.update', $task), [
            'assigned_user_ids' => [$assigneeA->id, $assigneeB->id],
            'related_type' => 'administrative',
            'title' => 'Tarefa para atualizar - com novo responsavel',
            'description' => 'Descricao',
            'task_status_id' => $status->id,
            'priority' => 'medium',
            'archived' => false,
            'scheduled_for' => null,
            'due_date' => null,
            'reminder_at' => null,
            'subtasks' => [],
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('notifications', [
            'type' => TaskAssignedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $assigneeB->id,
        ]);

        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_task_creation_does_not_notify_the_same_user_who_created_and_assigned_the_task(): void
    {
        /** @var Authenticatable $owner */
        $owner = User::factory()->createOne();

        $status = TaskStatus::create([
            'user_id' => $owner->id,
            'name' => 'Pendente',
            'color' => '#94a3b8',
            'order' => 1,
        ]);

        $this->actingAs($owner)->post(route('tasks.store'), [
            'assigned_user_ids' => [$owner->id],
            'related_type' => 'administrative',
            'title' => 'Tarefa própria',
            'description' => 'Descricao',
            'task_status_id' => $status->id,
            'priority' => 'medium',
            'archived' => false,
            'scheduled_for' => null,
            'due_date' => null,
            'reminder_at' => null,
            'subtasks' => [],
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseMissing('notifications', [
            'type' => TaskAssignedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
        ]);
    }

    public function test_idea_on_board_notifies_only_eligible_voters(): void
    {
        $owner = User::factory()->createOne();
        $eligibleVoter = User::factory()->createOne();
        $otherUser = User::factory()->createOne();

        $idea = Idea::create([
            'user_id' => $owner->id,
            'title' => 'Nova ideia',
            'description' => 'Descricao',
            'status' => 'in_drawer',
            'related_type' => 'none',
        ]);

        $idea->voterUsers()->sync([$eligibleVoter->id]);
        $idea->update(['status' => 'on_board']);

        $this->assertDatabaseHas('notifications', [
            'type' => IdeaOnBoardNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $eligibleVoter->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'type' => IdeaOnBoardNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $otherUser->id,
        ]);
    }

    public function test_idea_vote_notifies_only_creator(): void
    {
        $creator = User::factory()->createOne();
        /** @var Authenticatable $voter */
        $voter = User::factory()->createOne();

        $idea = Idea::create([
            'user_id' => $creator->id,
            'title' => 'Ideia no quadro',
            'description' => 'Descricao',
            'status' => 'on_board',
            'related_type' => 'none',
        ]);

        $idea->voterUsers()->sync([$voter->id]);

        $response = $this->actingAs($voter)->post(route('ideas.vote', $idea), [
            'vote' => 'on_table',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'type' => IdeaVotedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $creator->id,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'type' => IdeaVotedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $voter->id,
        ]);
    }

    public function test_user_can_remove_single_notification(): void
    {
        $owner = User::factory()->createOne();
        $assignee = User::factory()->createOne();

        $status = TaskStatus::create([
            'user_id' => $owner->id,
            'name' => 'Pendente',
            'color' => '#94a3b8',
            'order' => 1,
        ]);

        Task::create([
            'user_id' => $owner->id,
            'assigned_user_id' => $assignee->id,
            'related_type' => 'administrative',
            'title' => 'Tarefa com notificação removível',
            'task_status_id' => $status->id,
            'priority' => 'medium',
        ]);

        $notificationId = $assignee->notifications()->latest()->value('id');
        $this->assertNotNull($notificationId);

        $this->actingAs($assignee)->delete(route('notifications.destroy', $notificationId))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
            'notifiable_type' => User::class,
            'notifiable_id' => $assignee->id,
        ]);
    }

    public function test_dashboard_shares_unread_count_for_bell_badge(): void
    {
        $owner = User::factory()->createOne();
        /** @var Authenticatable $assignee */
        $assignee = User::factory()->createOne();

        $status = TaskStatus::create([
            'user_id' => $owner->id,
            'name' => 'Pendente',
            'color' => '#94a3b8',
            'order' => 1,
        ]);

        Task::create([
            'user_id' => $owner->id,
            'assigned_user_id' => $assignee->id,
            'related_type' => 'administrative',
            'title' => 'Tarefa com badge',
            'task_status_id' => $status->id,
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($assignee)->get(route('dashboard'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('unreadNotificationsCount', 1)
        );
    }
}
