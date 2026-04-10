<?php

namespace App\Policies;

use App\Models\IdeaCategory;
use App\Models\User;

class IdeaCategoryPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, IdeaCategory $ideaCategory): bool { return $user->id === $ideaCategory->user_id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, IdeaCategory $ideaCategory): bool { return $user->id === $ideaCategory->user_id; }
    public function delete(User $user, IdeaCategory $ideaCategory): bool { return $user->id === $ideaCategory->user_id; }
}
