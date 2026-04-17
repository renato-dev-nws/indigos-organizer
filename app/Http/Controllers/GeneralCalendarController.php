<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Event;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GeneralCalendarController extends Controller
{
    public function __invoke(): Response
    {
        $contentItems = Content::query()
            ->whereNotNull('planned_publish_at')
            ->get(['id', 'title', 'planned_publish_at'])
            ->map(fn (Content $content) => [
                'id' => 'content-'.$content->id,
                'title' => $content->title,
                'start' => optional($content->planned_publish_at)->toDateString(),
                'allDay' => true,
                'type' => 'content',
                'color' => '#4f46e5',
                'url' => route('contents.show', $content),
            ]);

        $eventItems = Event::query()
            ->with('type:id,name,color')
            ->get(['id', 'title', 'event_date', 'event_time', 'event_type_id'])
            ->map(fn (Event $event) => [
                'id' => 'event-'.$event->id,
                'title' => $event->title,
                'start' => $event->starts_at,
                'type' => 'event',
                'color' => $event->type?->color ?: '#059669',
                'url' => route('events.show', $event),
            ]);

        $currentUserId = (string) Auth::id();

        $userTaskItems = Task::query()
            ->where(fn ($query) => $query
                ->where('assigned_user_id', $currentUserId)
                ->orWhereNull('assigned_user_id')
            )
            ->with('status:id,name')
            ->get(['id', 'title', 'task_status_id', 'scheduled_for', 'due_date']);

        $scheduledTaskItems = $userTaskItems
            ->filter(fn (Task $task) => filled($task->scheduled_for))
            ->map(fn (Task $task) => [
                'id' => 'task-scheduled-'.$task->id,
                'title' => $task->title,
                'start' => optional($task->scheduled_for)->toIso8601String(),
                'type' => 'task_scheduled',
                'task_id' => $task->id,
                'color' => '#0ea5e9',
            ]);

        $deadlineTaskItems = $userTaskItems
            ->filter(function (Task $task): bool {
                if (! filled($task->due_date)) {
                    return false;
                }

                $statusName = Str::of($task->status?->name ?? '')->ascii()->lower()->toString();

                return Str::contains($statusName, ['execucao', 'executando', 'running']);
            })
            ->map(fn (Task $task) => [
                'id' => 'task-deadline-'.$task->id,
                'title' => 'Deadline: '.$task->title,
                'start' => optional($task->due_date)->toDateString(),
                'allDay' => true,
                'display' => 'list-item',
                'type' => 'task_deadline',
                'task_id' => $task->id,
                'color' => '#ef4444',
            ]);

        return Inertia::render('Calendar/Index', [
            'calendarItems' => $contentItems
                ->concat($eventItems)
                ->concat($scheduledTaskItems)
                ->concat($deadlineTaskItems)
                ->values(),
            'legend' => [
                ['label' => 'Conteúdos', 'color' => '#4f46e5'],
                ['label' => 'Eventos', 'color' => '#059669'],
                ['label' => 'Tarefas agendadas', 'color' => '#0ea5e9'],
                ['label' => 'Deadlines de tarefas', 'color' => '#ef4444'],
            ],
        ]);
    }
}