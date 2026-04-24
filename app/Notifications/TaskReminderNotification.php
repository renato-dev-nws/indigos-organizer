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

class TaskReminderNotification extends Notification implements ShouldQueue, ShouldSendWhatsApp
{
    use Queueable;

    public function __construct(private readonly Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('task_reminder', 'push')) {
            $channels[] = WebPushChannel::class;
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('task_reminder', 'email')) {
            $channels[] = 'mail';
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('task_reminder', 'whatsapp')) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_reminder',
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'reminder_at' => optional($this->task->reminder_at)->toIso8601String(),
            'message' => 'Lembrete da tarefa.',
        ];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Lembrete')
            ->body('Lembrete: "' . $this->task->title . '"')
            ->icon('/icons/icon-192x192.png')
            ->data(['url' => '/tasks/' . $this->task->id]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Lembrete de tarefa')
            ->line('Lembrete: "'.$this->task->title.'".')
            ->action('Abrir tarefa', url('/tasks/'.$this->task->id));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'content' => 'Lembrete de tarefa: "'.$this->task->title.'". Confira: '.url('/tasks/'.$this->task->id),
        ];
    }
}
