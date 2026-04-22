<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TaskAssignedNotification extends Notification
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
}
