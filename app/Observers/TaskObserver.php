<?php

namespace App\Observers;

use App\Jobs\DispatchTaskAssignedNotificationsJob;
use App\Models\Task;

class TaskObserver
{
    public function created(Task $task): void
    {
        if ($task->syncLegacyAssignedUsersIfPending()) {
            DispatchTaskAssignedNotificationsJob::dispatchSync($task->id);
        }
    }

    public function updated(Task $task): void
    {
        if ($task->syncLegacyAssignedUsersIfPending()) {
            DispatchTaskAssignedNotificationsJob::dispatchSync($task->id);
        }
    }
}
