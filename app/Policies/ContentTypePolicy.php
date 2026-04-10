<?php

namespace App\Policies;

use App\Models\ContentType;
use App\Models\User;

class ContentTypePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, ContentType $contentType): bool { return $user->id === $contentType->user_id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, ContentType $contentType): bool { return $user->id === $contentType->user_id; }
    public function delete(User $user, ContentType $contentType): bool { return $user->id === $contentType->user_id; }
}
