<?php

namespace App\Observers;

use App\Jobs\DispatchTaskAssignedNotificationsJob;
use App\Models\Task;

class TaskObserver
{
    public function created(Task $task): void
    {
        $newlyAssignedUserIds = $task->syncLegacyAssignedUsersIfPending();

        if ($newlyAssignedUserIds !== []) {
            DispatchTaskAssignedNotificationsJob::dispatchSync($task->id, $newlyAssignedUserIds);
        }
    }

    public function updated(Task $task): void
    {
        $newlyAssignedUserIds = $task->syncLegacyAssignedUsersIfPending();

        if ($newlyAssignedUserIds !== []) {
            DispatchTaskAssignedNotificationsJob::dispatchSync($task->id, $newlyAssignedUserIds);
        }
    }
}
