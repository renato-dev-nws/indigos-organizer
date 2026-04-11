<?php

namespace App\Policies;

use App\Models\SharedInfo;
use App\Models\User;

class SharedInfoPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, SharedInfo $sharedInfo): bool { return $user->id === $sharedInfo->user_id; }
    public function create(User $user): bool { return true; }
    public function update(User $user, SharedInfo $sharedInfo): bool { return $user->id === $sharedInfo->user_id; }
    public function delete(User $user, SharedInfo $sharedInfo): bool { return $user->id === $sharedInfo->user_id; }
    public function restore(User $user, SharedInfo $sharedInfo): bool { return $user->id === $sharedInfo->user_id; }
    public function forceDelete(User $user, SharedInfo $sharedInfo): bool { return $user->id === $sharedInfo->user_id; }
}
