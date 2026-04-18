<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskUserNotificationLog;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchTaskReminderNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $tasks = Task::query()
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
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
                        'event_type' => 'reminder',
                    ],
                    ['sent_at' => now()],
                );

                if ($created->wasRecentlyCreated) {
                    $user->notify(new TaskReminderNotification($task));
                }
            }

            $task->forceFill(['reminder_notified_at' => now()])->save();
        }
    }
}
