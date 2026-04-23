<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Event;
use App\Models\Task;
use App\Support\SystemSettingsRegistry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GeneralCalendarController extends Controller
{
    public function __invoke(): Response
    {
        $moduleColors = SystemSettingsRegistry::moduleColors();
        $contentColor = SystemSettingsRegistry::tokenToHex($moduleColors['contents'] ?? 'purple-500');
        $eventColor = SystemSettingsRegistry::tokenToHex($moduleColors['events'] ?? 'orange-500');
        $scheduledTaskColor = SystemSettingsRegistry::tokenToHex($moduleColors['tasks'] ?? 'indigo-500');
        $deadlineColor = '#ef4444';

        $contentItems = Content::query()
            ->whereNotNull('planned_publish_at')
            ->get(['id', 'title', 'planned_publish_at'])
            ->map(fn (Content $content) => [
                'id' => 'content-'.$content->id,
                'title' => $content->title,
                'start' => optional($content->planned_publish_at)->toDateString(),
                'allDay' => true,
                'type' => 'content',
                'color' => $contentColor,
                'display' => 'block',
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
                'color' => $eventColor,
                'display' => 'block',
                'url' => route('events.show', $event),
            ]);

        $currentUserId = (string) Auth::id();

        $userTaskItems = Task::query()
            ->where(fn (Builder $query) => $query
                ->whereHas('assignedUsers', fn (Builder $assignedUsers) => $assignedUsers->where('users.id', $currentUserId))
                ->orWhereDoesntHave('assignedUsers')
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
                'color' => $scheduledTaskColor,
                'display' => 'block',
            ]);

        $deadlineTaskItems = $userTaskItems
            ->filter(function (Task $task): bool {
                if (! filled($task->due_date)) {
                    return false;
                }

                if (! filled($task->scheduled_for)) {
                    return true;
                }

                return Carbon::parse($task->due_date)->lt(Carbon::today());
            })
            ->map(fn (Task $task) => [
                'id' => 'task-deadline-'.$task->id,
                'title' => 'Deadline: '.$task->title,
                'start' => optional($task->due_date)->toDateString(),
                'allDay' => true,
                'display' => 'block',
                'type' => 'task_deadline',
                'task_id' => $task->id,
                'color' => $deadlineColor,
            ]);

        return Inertia::render('Calendar/Index', [
            'calendarItems' => $contentItems
                ->concat($eventItems)
                ->concat($scheduledTaskItems)
                ->concat($deadlineTaskItems)
                ->values(),
            'legend' => [
                ['label' => 'Conteúdos', 'color' => $contentColor],
                ['label' => 'Eventos', 'color' => $eventColor],
                ['label' => 'Tarefas agendadas', 'color' => $scheduledTaskColor],
                ['label' => 'Deadlines de tarefas', 'color' => $deadlineColor],
            ],
        ]);
    }
}