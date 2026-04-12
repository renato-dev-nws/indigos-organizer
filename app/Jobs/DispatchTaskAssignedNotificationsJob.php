<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskUserNotificationLog;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchTaskAssignedNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $taskId)
    {
    }

    public function handle(): void
    {
        $task = Task::query()->find($this->taskId);

        if (! $task) {
            return;
        }

        $targets = $task->assigned_user_id
            ? User::query()->where('id', $task->assigned_user_id)->get()
            : User::query()->get();

        foreach ($targets as $user) {
            $created = TaskUserNotificationLog::query()->firstOrCreate(
                [
                    'task_id' => $task->id,
                    'user_id' => $user->id,
                    'event_type' => 'assigned',
                ],
                ['sent_at' => now()],
            );

            if ($created->wasRecentlyCreated) {
                $user->notify(new TaskAssignedNotification($task));
            }
        }

        $task->forceFill(['assignment_notified_at' => now()])->save();
    }
}
