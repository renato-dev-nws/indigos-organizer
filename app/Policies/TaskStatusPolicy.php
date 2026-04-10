<?php

namespace App\Policies;

use App\Models\TaskStatus;
use App\Models\User;

class TaskStatusPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, TaskStatus $taskStatus): bool { return $user->id === $taskStatus->user_id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, TaskStatus $taskStatus): bool { return $user->id === $taskStatus->user_id; }
    public function delete(User $user, TaskStatus $taskStatus): bool { return $user->id === $taskStatus->user_id; }
}
