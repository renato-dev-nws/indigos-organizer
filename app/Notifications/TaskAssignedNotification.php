<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Task;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Contracts\ShouldSendWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TaskAssignedNotification extends Notification implements ShouldQueue, ShouldSendWhatsApp
{
    use Queueable;

    public function __construct(private readonly Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('task_assigned', 'push')) {
            $channels[] = WebPushChannel::class;
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('task_assigned', 'email')) {
            $channels[] = 'mail';
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('task_assigned', 'whatsapp')) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'message' => 'Uma tarefa foi atribuída para você.',
        ];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Tarefa atribuída')
            ->body('"' . $this->task->title . '" foi atribuída para você.')
            ->icon('/icons/icon-192x192.png')
            ->data(['url' => '/tasks/' . $this->task->id]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tarefa atribuída')
            ->line('"'.$this->task->title.'" foi atribuída para você.')
            ->action('Abrir tarefa', url('/tasks/'.$this->task->id));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'content' => 'Nova tarefa atribuida: "'.$this->task->title.'". Acesse: '.url('/tasks/'.$this->task->id),
        ];
    }
}
