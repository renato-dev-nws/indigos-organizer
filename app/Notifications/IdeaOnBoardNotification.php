<?php

namespace App\Notifications;

use App\Models\Idea;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class IdeaOnBoardNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Idea $idea)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ((bool) ($notifiable->push_enabled ?? true)) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
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

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Nova ideia no quadro')
            ->body('"' . $this->idea->title . '" entrou no quadro de votação.')
            ->icon('/icons/icon-192x192.png')
            ->data(['url' => '/ideas']);
    }
}
