<?php

namespace App\Notifications;

use App\Models\Idea;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IdeaOnBoardNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Idea $idea)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'idea_on_board',
            'idea_id' => $this->idea->id,
            'title' => $this->idea->title,
            'message' => 'Uma ideia elegível para você entrou no quadro de votação.',
        ];
    }
}
