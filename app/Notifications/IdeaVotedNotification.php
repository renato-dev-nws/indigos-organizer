<?php

namespace App\Notifications;

use App\Models\Idea;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Contracts\ShouldSendWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class IdeaVotedNotification extends Notification implements ShouldQueue, ShouldSendWhatsApp
{
    use Queueable;

    public function __construct(private readonly Idea $idea, private readonly User $voter)
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
            'type' => 'idea_voted',
            'idea_id' => $this->idea->id,
            'title' => $this->idea->title,
            'voter_name' => $this->voter->name,
            'message' => 'Sua ideia recebeu um novo voto.',
        ];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Voto recebido')
            ->body($this->voter->name . ' votou na sua ideia "' . $this->idea->title . '".')
            ->icon('/icons/icon-192x192.png')
            ->data(['url' => '/ideas']);
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'content' => $this->voter->name.' votou na sua ideia "'.$this->idea->title.'". Veja em: '.url('/ideas'),
        ];
    }
}
