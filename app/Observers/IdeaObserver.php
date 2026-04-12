<?php

namespace App\Observers;

use App\Jobs\DispatchIdeaOnBoardNotificationsJob;
use App\Models\Idea;

class IdeaObserver
{
    public function updated(Idea $idea): void
    {
        if ($idea->wasChanged('status') && $idea->status === 'on_board') {
            DispatchIdeaOnBoardNotificationsJob::dispatch($idea->id);
        }
    }
}
