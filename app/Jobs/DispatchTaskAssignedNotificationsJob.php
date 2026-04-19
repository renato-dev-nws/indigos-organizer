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

    public function __construct(
        private readonly string $taskId,
        private readonly array $targetUserIds = [],
    )
    {
    }

    public function handle(): void
    {
        $task = Task::query()->with('assignedUsers:id')->find($this->taskId);

        if (! $task) {
            return;
        }

        $targets = $task->assignedUsers->isNotEmpty()
            ? $task->assignedUsers
            : User::query()->get();

        if ($this->targetUserIds !== []) {
            $allowedTargetUserIds = collect($this->targetUserIds)
                ->map(fn ($id) => (string) $id)
                ->intersect($targets->pluck('id')->map(fn ($id) => (string) $id))
                ->values();

            if ($allowedTargetUserIds->isEmpty()) {
                return;
            }

            $targets = User::query()
                ->whereIn('id', $allowedTargetUserIds->all())
                ->get();
        }

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
