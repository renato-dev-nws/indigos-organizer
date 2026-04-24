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

class TaskDueSoonNotification extends Notification implements ShouldQueue, ShouldSendWhatsApp
{
    use Queueable;

    public function __construct(private readonly Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('task_due_soon', 'push')) {
            $channels[] = WebPushChannel::class;
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('task_due_soon', 'email')) {
            $channels[] = 'mail';
        }

        if ($notifiable instanceof User && $notifiable->notificationChannelEnabled('task_due_soon', 'whatsapp')) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_due_soon',
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'due_date' => optional($this->task->due_date)->toDateString(),
            'message' => 'Esta tarefa vence em menos de 24 horas.',
        ];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Tarefa vence em breve')
            ->body('"' . $this->task->title . '" vence em menos de 24 horas.')
            ->icon('/icons/icon-192x192.png')
            ->data(['url' => '/tasks/' . $this->task->id]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tarefa vence em breve')
            ->line('"'.$this->task->title.'" vence em menos de 24 horas.')
            ->action('Abrir tarefa', url('/tasks/'.$this->task->id));
    }

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'content' => 'Lembrete: a tarefa "'.$this->task->title.'" vence em menos de 24h. Acesse: '.url('/tasks/'.$this->task->id),
        ];
    }
}
