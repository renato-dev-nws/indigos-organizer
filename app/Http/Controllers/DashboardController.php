<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Content;
use App\Models\Event;
use App\Models\Idea;
use App\Models\Plan;
use App\Models\Task;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $userId = (string) Auth::id();
        $now = Carbon::now();
        $today = $now->toDateString();

        $activeTaskIds = Task::query()
            ->whereHas('status')
            ->with('status:id,name')
            ->get(['id', 'task_status_id'])
            ->filter(fn (Task $task) => ! $this->isFinishedTask($task->status?->name))
            ->pluck('id');

        $taskScope = Task::query()
            ->where(fn (Builder $query) => $query
                ->where('assigned_user_id', $userId)
                ->orWhereNull('assigned_user_id')
            )
            ->with(['status:id,name,color', 'assignedUser:id,name']);

        $summary = [
            'tasksTotal' => (clone $taskScope)->count(),
            'tasksScheduled' => (clone $taskScope)
                ->whereIn('id', $activeTaskIds)
                ->whereNotNull('scheduled_for')
                ->count(),
            'tasksMine' => (clone $taskScope)
                ->whereIn('id', $activeTaskIds)
                ->where('assigned_user_id', $userId)
                ->count(),
            'tasksEveryone' => (clone $taskScope)
                ->whereIn('id', $activeTaskIds)
                ->whereNull('assigned_user_id')
                ->count(),
            'tasksOverdue' => (clone $taskScope)
                ->whereIn('id', $activeTaskIds)
                ->whereDate('due_date', '<', $today)
                ->count(),
            'contentsTotal' => Content::query()->count(),
            'contentsQueued' => Content::query()->where('status', 'queued')->count(),
            'contentsInProduction' => Content::query()->where('status', 'in_production')->count(),
            'contentsFinalized' => Content::query()->where('status', 'finalized')->count(),
            'contentsPublished' => Content::query()->where('status', 'published')->count(),
            'ideasTotal' => Idea::query()->where('is_private', false)->count(),
            'ideasMine' => Idea::query()->where('user_id', $userId)->count(),
            'ideasInDrawer' => Idea::query()->where('is_private', false)->where('status', 'in_drawer')->count(),
            'ideasOnTable' => Idea::query()->where('is_private', false)->where('status', 'on_table')->count(),
            'ideasOnBoard' => Idea::query()->where('is_private', false)->where('status', 'on_board')->count(),
            'plansRunning' => Plan::query()->where('status', 'running')->count(),
            'eventsActive' => Event::query()->whereRaw("(event_date + event_time) >= ?", [$now])->count(),
            'venuesTotal' => Venue::query()->count(),
            'contactsTotal' => Contact::query()->count(),
        ];

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = (clone $weekStart)->endOfWeek(Carbon::SATURDAY);

        $weeklyTasks = Task::query()
            ->where(fn (Builder $query) => $query
                ->where('assigned_user_id', $userId)
                ->orWhereNull('assigned_user_id')
            )
            ->where('archived', false)
            ->with(['status:id,name,color'])
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query
                    ->whereBetween('scheduled_for', [$weekStart, $weekEnd])
                    ->orWhereBetween('due_date', [$weekStart->toDateString(), $weekEnd->toDateString()]);
            })
            ->orderByRaw('coalesce(scheduled_for, due_date::timestamp) asc')
            ->get(['id', 'title', 'task_status_id', 'scheduled_for', 'due_date'])
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'kind' => 'task',
                'title' => $task->title,
                'status' => $task->status,
                'scheduled_for' => optional($task->scheduled_for)?->toIso8601String(),
                'due_date' => optional($task->due_date)?->toDateString(),
                'url' => route('tasks.edit', $task),
            ]);

        $weeklyContents = Content::query()
            ->whereBetween('planned_publish_at', [$weekStart, $weekEnd])
            ->orderBy('planned_publish_at')
            ->get(['id', 'title', 'status', 'planned_publish_at'])
            ->map(fn (Content $content) => [
                'id' => $content->id,
                'kind' => 'content',
                'title' => $content->title,
                'status' => $content->status,
                'planned_publish_at' => optional($content->planned_publish_at)?->toIso8601String(),
                'url' => route('contents.show', $content),
            ]);

        return Inertia::render('Dashboard', [
            'summary' => $summary,
            'boardIdeas' => Idea::query()
                ->where('status', 'on_board')
                ->where('is_private', false)
                ->where(function ($q) {
                    $q->whereDoesntHave('voterUsers')
                        ->orWhereHas('voterUsers', fn ($q2) => $q2->where('user_id', Auth::id()));
                })
                ->whereDoesntHave('votes', fn ($q) => $q->where('user_id', Auth::id()))
                ->with(['user', 'votes'])
                ->latest('updated_at')
                ->take(5)
                ->get(),
            'nextScheduledTasks' => (clone $taskScope)
                ->whereIn('id', $activeTaskIds)
                ->whereNotNull('scheduled_for')
                ->where('scheduled_for', '>=', $now)
                ->orderBy('scheduled_for')
                ->take(5)
                ->get(),
            'deadlineSoonTasks' => (clone $taskScope)
                ->whereIn('id', $activeTaskIds)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '>=', $today)
                ->whereDate('due_date', '<=', Carbon::now()->addDays(3)->toDateString())
                ->orderBy('due_date')
                ->take(5)
                ->get(),
            'nextEvents' => Event::query()
                ->with('type:id,name,color')
                ->whereRaw("(event_date + event_time) >= ?", [$now])
                ->orderBy('event_date')
                ->orderBy('event_time')
                ->take(5)
                ->get(),
            'contentsInProduction' => Content::query()
                ->with(['platforms:id,name'])
                ->where('status', 'in_production')
                ->orderBy('planned_publish_at')
                ->take(5)
                ->get(),
            'plansQueue' => Plan::query()
                ->whereIn('status', ['running', 'queued'])
                ->orderByRaw("case when status = 'running' then 0 else 1 end")
                ->orderByDesc('updated_at')
                ->take(5)
                ->get(),
            'weeklyProgramItems' => $weeklyTasks->concat($weeklyContents)->values(),
        ]);
    }

    private function isFinishedTask(?string $statusName): bool
    {
        $normalized = Str::of($statusName ?? '')
            ->ascii()
            ->lower()
            ->toString();

        return Str::contains($normalized, ['concluida', 'concluido', 'finalizada', 'finalizado', 'done', 'completed']);
    }
}
