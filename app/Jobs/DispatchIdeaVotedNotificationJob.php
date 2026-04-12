<?php

namespace App\Jobs;

use App\Models\Idea;
use App\Models\User;
use App\Notifications\IdeaVotedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchIdeaVotedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $ideaId, private readonly string $voterId)
    {
    }

    public function handle(): void
    {
        $idea = Idea::query()->find($this->ideaId);
        $voter = User::query()->find($this->voterId);

        if (! $idea || ! $voter) {
            return;
        }

        $creator = User::query()->find($idea->user_id);
        if (! $creator || $creator->id === $voter->id) {
            return;
        }

        $creator->notify(new IdeaVotedNotification($idea, $voter));
    }
}
