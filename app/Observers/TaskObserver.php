<?php

namespace App\Observers;

use App\Jobs\DispatchTaskAssignedNotificationsJob;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskObserver
{
    public function created(Task $task): void
    {
        $newlyAssignedUserIds = $task->syncLegacyAssignedUsersIfPending();

        if ($newlyAssignedUserIds !== []) {
            DispatchTaskAssignedNotificationsJob::dispatchSync($task->id, $newlyAssignedUserIds, Auth::id() ? (string) Auth::id() : null);
        }
    }

    public function updated(Task $task): void
    {
        $newlyAssignedUserIds = $task->syncLegacyAssignedUsersIfPending();

        if ($newlyAssignedUserIds !== []) {
            DispatchTaskAssignedNotificationsJob::dispatchSync($task->id, $newlyAssignedUserIds, Auth::id() ? (string) Auth::id() : null);
        }
    }
}
