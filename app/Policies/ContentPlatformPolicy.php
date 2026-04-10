<?php

namespace App\Policies;

use App\Models\ContentPlatform;
use App\Models\User;

class ContentPlatformPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, ContentPlatform $contentPlatform): bool { return $user->id === $contentPlatform->user_id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, ContentPlatform $contentPlatform): bool { return $user->id === $contentPlatform->user_id; }
    public function delete(User $user, ContentPlatform $contentPlatform): bool { return $user->id === $contentPlatform->user_id; }
}
