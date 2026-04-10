<?php

namespace App\Policies;

use App\Models\ContentCategory;
use App\Models\User;

class ContentCategoryPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, ContentCategory $contentCategory): bool { return $user->id === $contentCategory->user_id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, ContentCategory $contentCategory): bool { return $user->id === $contentCategory->user_id; }
    public function delete(User $user, ContentCategory $contentCategory): bool { return $user->id === $contentCategory->user_id; }
}
