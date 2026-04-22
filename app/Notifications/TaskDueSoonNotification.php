<?php

namespace App\Notifications;

use App\Models\Task;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Contracts\ShouldSendWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    public function toWhatsApp(object $notifiable): array
    {
        return [
            'content' => 'Lembrete: a tarefa "'.$this->task->title.'" vence em menos de 24h. Acesse: '.url('/tasks/'.$this->task->id),
        ];
    }
}
