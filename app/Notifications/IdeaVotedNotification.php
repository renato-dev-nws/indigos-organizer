<?php

namespace App\Notifications;

use App\Models\Idea;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Contracts\ShouldSendWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('idea_voted', 'push')) {
            $channels[] = WebPushChannel::class;
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('idea_voted', 'email')) {
            $channels[] = 'mail';
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('idea_voted', 'whatsapp')) {
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Voto recebido')
            ->line($this->voter->name.' votou na sua ideia "'.$this->idea->title.'".')
            ->action('Abrir ideias', url('/ideas'));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'content' => $this->voter->name.' votou na sua ideia "'.$this->idea->title.'". Veja em: '.url('/ideas'),
        ];
    }
}
