<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Content;
use App\Models\Event;
use App\Models\Idea;
use App\Models\Plan;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
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

        [$taskUserChartStart, $taskUserChartEnd, $taskUserChartPeriod] = $this->resolveFixedPeriod('dashboard_tasks_users_period', '7d');
        [$contentLineStart, $contentLineEnd, $contentLinePeriod] = $this->resolveFixedPeriod('dashboard_contents_line_period', '7d');

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
            'dashboardCharts' => [
                'taskStatus' => $this->buildTaskStatusChart(),
                'taskByUserStatus' => $this->buildTaskByUserStatusChart($taskUserChartStart, $taskUserChartEnd),
                'contentsLine' => $this->buildContentsLineChart($contentLineStart, $contentLineEnd),
                'contentStatuses' => $this->buildContentStatusesChart(),
            ],
            'dashboardChartPeriods' => [
                'taskByUserStatus' => $taskUserChartPeriod,
                'contentsLine' => $contentLinePeriod,
            ],
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

    private function resolveFixedPeriod(string $requestKey, string $default = '7d'): array
    {
        $period = request($requestKey, $default);
        $days = match ($period) {
            '7d' => 7,
            '15d' => 15,
            default => 30,
        };

        $start = Carbon::now()->subDays($days - 1)->startOfDay();
        $end = Carbon::now()->endOfDay();

        return [$start, $end, [
            'period' => in_array($period, ['7d', '15d', '30d'], true) ? $period : '7d',
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ]];
    }

    private function buildTaskStatusChart(): array
    {
        $statuses = TaskStatus::query()->orderBy('order')->get(['id', 'name', 'color']);
        $counts = $statuses->mapWithKeys(function (TaskStatus $status) {
            $query = Task::query()->where('task_status_id', $status->id);

            if ($this->isFinishedTask($status->name)) {
                $query->where('archived', false);
            }

            return [$status->id => (int) $query->count()];
        });

        return [
            'labels' => $statuses->pluck('name')->values(),
            'colors' => $statuses->pluck('color')->values(),
            'data' => $statuses->map(fn (TaskStatus $status) => (int) ($counts[$status->id] ?? 0))->values(),
        ];
    }

    private function buildTaskByUserStatusChart(Carbon $start, Carbon $end): array
    {
        $statuses = TaskStatus::query()->orderBy('order')->get(['id', 'name', 'color']);
        $users = User::query()->orderBy('name')->get(['id', 'name']);
        $rows = Task::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('coalesce(assigned_user_id::text, ?) as user_key, task_status_id, count(*) as total', ['__unassigned__'])
            ->groupBy('user_key', 'task_status_id')
            ->get();

        $userOptions = collect([
            ['id' => '__all__', 'name' => 'TODOS'],
            ...$users->map(fn (User $user) => ['id' => (string) $user->id, 'name' => $user->name])->all(),
            ['id' => '__unassigned__', 'name' => 'Sem responsável'],
        ]);

        $series = $statuses->map(function (TaskStatus $status) use ($userOptions, $rows) {
            return [
                'status_id' => $status->id,
                'name' => $status->name,
                'color' => $status->color,
                'values' => $userOptions->map(function (array $user) use ($status, $rows) {
                    if ($user['id'] === '__all__') {
                        return (int) $rows->where('task_status_id', $status->id)->sum('total');
                    }

                    return (int) optional(
                        $rows->where('task_status_id', $status->id)->where('user_key', $user['id'])->first()
                    )->total;
                })->values(),
            ];
        })->values();

        return [
            'users' => $userOptions,
            'statuses' => $series,
        ];
    }

    private function buildContentsLineChart(Carbon $start, Carbon $end): array
    {
        $labels = [];
        $dateKeys = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $labels[] = $cursor->format('d/m');
            $dateKeys[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        $createdByDate = Content::query()
            ->selectRaw("to_char(created_at::date, 'YYYY-MM-DD') as day, count(*) as total")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        $publishedByDate = Content::query()
            ->selectRaw("to_char(published_at::date, 'YYYY-MM-DD') as day, count(*) as total")
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        return [
            'labels' => $labels,
            'created' => collect($dateKeys)->map(fn (string $day) => (int) ($createdByDate[$day] ?? 0))->values(),
            'published' => collect($dateKeys)->map(fn (string $day) => (int) ($publishedByDate[$day] ?? 0))->values(),
        ];
    }

    private function buildContentStatusesChart(): array
    {
        $statusOrder = ['queued', 'in_production', 'finalized', 'published', 'cancelled', 'paused'];
        $statusLabels = [
            'queued' => 'Na fila',
            'in_production' => 'Em produção',
            'finalized' => 'Finalizado',
            'published' => 'Publicado',
            'cancelled' => 'Cancelado',
            'paused' => 'Pausado',
        ];

        $counts = Content::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => collect($statusOrder)->map(fn (string $status) => $statusLabels[$status])->values(),
            'data' => collect($statusOrder)->map(fn (string $status) => (int) ($counts[$status] ?? 0))->values(),
        ];
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
