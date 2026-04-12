<?php

namespace App\Notifications;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IdeaVotedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Idea $idea, private readonly User $voter)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'idea_voted',
            'idea_id' => $this->idea->id,
            'title' => $this->idea->title,
            'voter_name' => $this->voter->name,
            'message' => 'Sua ideia recebeu um novo voto.',
        ];
    }
}
