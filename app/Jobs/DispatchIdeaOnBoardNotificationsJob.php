<?php

namespace App\Jobs;

use App\Models\Idea;
use App\Models\User;
use App\Notifications\IdeaOnBoardNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchIdeaOnBoardNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $ideaId)
    {
    }

    public function handle(): void
    {
        $idea = Idea::query()->with('voterUsers')->find($this->ideaId);

        if (! $idea) {
            return;
        }

        $targets = $idea->voterUsers->isNotEmpty()
            ? $idea->voterUsers
            : User::query()->where('id', '!=', $idea->user_id)->get();

        foreach ($targets as $user) {
            $user->notify(new IdeaOnBoardNotification($idea));
        }
    }
}
