<?php

namespace App\Policies;

use App\Models\Idea;
use App\Models\User;

class IdeaPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Idea $idea): bool
    {
        if ((string) $user->id === (string) $idea->user_id) {
            return true;
        }

        if (! $idea->is_private) {
            return true;
        }

        return false;
    }
    public function create(User $user): bool { return true; }

    public function update(User $user, Idea $idea): bool
    {
        if ((string) $user->id === (string) $idea->user_id) {
            return true;
        }

        if ($idea->is_private) {
            return false;
        }

        return $idea->collaborators()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, Idea $idea): bool { return (string) $user->id === (string) $idea->user_id; }
    public function restore(User $user, Idea $idea): bool { return (string) $user->id === (string) $idea->user_id; }
    public function forceDelete(User $user, Idea $idea): bool { return (string) $user->id === (string) $idea->user_id; }
}
