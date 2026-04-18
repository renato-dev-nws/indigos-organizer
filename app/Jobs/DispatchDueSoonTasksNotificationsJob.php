<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskUserNotificationLog;
use App\Models\User;
use App\Notifications\TaskDueSoonNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchDueSoonTasksNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $tasks = Task::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->whereDate('due_date', '<=', now()->addDay()->toDateString())
            ->with('assignedUsers:id')
            ->get();

        foreach ($tasks as $task) {
            $targets = $task->assignedUsers->isNotEmpty()
                ? $task->assignedUsers
                : User::query()->get();

            foreach ($targets as $user) {
                $created = TaskUserNotificationLog::query()->firstOrCreate(
                    [
                        'task_id' => $task->id,
                        'user_id' => $user->id,
                        'event_type' => 'due_soon',
                    ],
                    ['sent_at' => now()],
                );

                if ($created->wasRecentlyCreated) {
                    $user->notify(new TaskDueSoonNotification($task));
                }
            }

            $task->forceFill(['due_soon_notified_at' => now()])->save();
        }
    }
}
