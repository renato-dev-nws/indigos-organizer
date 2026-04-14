<?php

namespace App\Observers;

use App\Jobs\DispatchTaskAssignedNotificationsJob;
use App\Models\Task;

class TaskObserver
{
    public function created(Task $task): void
    {
        DispatchTaskAssignedNotificationsJob::dispatchSync($task->id);
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged('assigned_user_id')) {
            DispatchTaskAssignedNotificationsJob::dispatchSync($task->id);
        }
    }
}
