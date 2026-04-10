<?php

namespace App\Policies;

use App\Models\IdeaType;
use App\Models\User;

class IdeaTypePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, IdeaType $ideaType): bool { return $user->id === $ideaType->user_id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, IdeaType $ideaType): bool { return $user->id === $ideaType->user_id; }
    public function delete(User $user, IdeaType $ideaType): bool { return $user->id === $ideaType->user_id; }
}
