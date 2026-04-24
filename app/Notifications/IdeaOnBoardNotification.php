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

class IdeaOnBoardNotification extends Notification implements ShouldQueue, ShouldSendWhatsApp
{
    use Queueable;

    public function __construct(private readonly Idea $idea)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('idea_on_board', 'push')) {
            $channels[] = WebPushChannel::class;
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('idea_on_board', 'email')) {
            $channels[] = 'mail';
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('idea_on_board', 'whatsapp')) {
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nova ideia no quadro')
            ->line('"'.$this->idea->title.'" entrou no quadro de votação.')
            ->action('Abrir ideias', url('/ideas'));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'content' => 'Nova ideia no quadro: "'.$this->idea->title.'". Veja em: '.url('/ideas'),
        ];
    }
}
