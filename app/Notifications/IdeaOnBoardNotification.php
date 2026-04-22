<?php

namespace App\Notifications;

use App\Models\Idea;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Contracts\ShouldSendWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class IdeaOnBoardNotification extends Notification implements ShouldQueue, ShouldSendWhatsApp
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

        if ((bool) ($notifiable->whatsapp_enabled ?? false)) {
            $channels[] = WhatsAppChannel::class;
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

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'content' => 'Nova ideia no quadro: "'.$this->idea->title.'". Veja em: '.url('/ideas'),
        ];
    }
}
