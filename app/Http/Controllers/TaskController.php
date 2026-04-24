<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchTaskAssignedNotificationsJob;
use App\Models\Event;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Content;
use App\Models\Plan;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        $baseQuery = $this->applyTaskFilters(Task::query());
        $boardQuery = $this->applyTaskFilters(Task::query(), true);

        $tasks = (clone $baseQuery)
            ->with(['status', 'content', 'subtasks', 'assignedUsers', 'plan', 'planPhase', 'event'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $boardTasks = (clone $boardQuery)
            ->with(['status', 'content', 'subtasks', 'assignedUsers', 'plan', 'planPhase', 'event'])
            ->latest()
            ->get();

        $calendarSourceTasks = (clone $boardQuery)
            ->with(['status:id,name,color'])
            ->get(['id', 'title', 'task_status_id', 'scheduled_for', 'due_date', 'archived']);

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $weekEnd = (clone $weekStart)->addWeek()->endOfDay();

        $weeklyTaskItems = (clone $boardQuery)
            ->with(['status:id,name,color'])
            ->where(function ($query) use ($weekStart, $weekEnd) {
                $query
                    ->whereBetween('scheduled_for', [$weekStart, $weekEnd])
                    ->orWhereBetween('due_date', [$weekStart->toDateString(), $weekEnd->toDateString()]);
            })
            ->orderByRaw('coalesce(scheduled_for, due_date::timestamp) asc')
            ->get(['id', 'title', 'task_status_id', 'scheduled_for', 'due_date', 'archived']);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'boardTasks' => $boardTasks,
            'taskCalendarItems' => $this->buildTaskCalendarItems($calendarSourceTasks),
            'weeklyTaskItems' => $weeklyTaskItems,
            'taskCharts' => $this->buildTaskCharts(),
            'statuses' => TaskStatus::query()->orderBy('order')->get(),
            'contents' => Content::query()->orderBy('title')->get(['id', 'title']),
            'plans' => Plan::query()
                ->whereIn('status', ['queued', 'running'])
                ->with('phases')
                ->orderBy('title')
                ->get(['id', 'title', 'status']),
            'events' => Event::query()->orderBy('event_date')->orderBy('event_time')->get(['id', 'title']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'currentUserId' => (string) Auth::id(),
            'filters' => request()->only(['assigned_user_id', 'priority', 'related_type', 'content_id', 'search', 'include_archived', 'overdue_only', 'scheduled_only']),
        ]);
    }

    private function buildTaskCharts(): array
    {
        $statuses = TaskStatus::query()->orderBy('order')->get(['id', 'name', 'color']);
        $allTasksScope = Task::query();

        $statusCounts = $statuses
            ->mapWithKeys(function (TaskStatus $status) use ($allTasksScope) {
                $query = (clone $allTasksScope)->where('task_status_id', $status->id);

                if ($this->isCompletedStatusName($status->name)) {
                    $query->where('archived', false);
                }

                return [$status->id => (int) $query->count()];
            });

        $users = User::query()->orderBy('name')->get(['id', 'name']);
        $usersData = collect([
            ['id' => '__all__', 'name' => 'TODOS'],
            ...$users->map(fn (User $user) => ['id' => (string) $user->id, 'name' => $user->name])->all(),
            ['id' => '__unassigned__', 'name' => 'Sem responsável'],
        ]);

        $statusSeries = $statuses->map(function (TaskStatus $status) use ($usersData) {
            $series = $usersData->map(function (array $user) use ($status) {
                $query = Task::query()->where('task_status_id', $status->id);

                if ($this->isCompletedStatusName($status->name)) {
                    $query->where('archived', false);
                }

                if ($user['id'] === '__all__') {
                    return (int) $query->count();
                }

                if ($user['id'] === '__unassigned__') {
                    return (int) $query->whereDoesntHave('assignedUsers')->count();
                }

                return (int) $query->whereHas('assignedUsers', fn ($q) => $q->where('users.id', $user['id']))->count();
            })->values();

            return [
                'status_id' => $status->id,
                'name' => $status->name,
                'color' => $status->color,
                'values' => $series,
            ];
        })->values();

        return [
            'statusCounts' => [
                'labels' => $statuses->pluck('name')->values(),
                'colors' => $statuses->pluck('color')->values(),
                'data' => $statuses->map(fn (TaskStatus $status) => (int) ($statusCounts[$status->id] ?? 0))->values(),
                'overdue' => $statuses->map(function (TaskStatus $status) {
                    if ($this->isCompletedStatusName($status->name)) {
                        return 0;
                    }

                    return (int) Task::query()
                        ->where('task_status_id', $status->id)
                        ->where('archived', false)
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '<', Carbon::today()->toDateString())
                        ->count();
                })->values(),
            ],
            'tasksByUserStatus' => [
                'users' => $usersData,
                'statuses' => $statusSeries,
            ],
        ];
    }

    private function applyTaskFilters(Builder $query, bool $excludeArchived = false): Builder
    {
        $assignedUserFilter = request('assigned_user_id');
        if (blank($assignedUserFilter)) {
            $assignedUserFilter = null;
        }

        $currentUserId = (string) Auth::id();

        $includeArchived = request()->boolean('include_archived') || request()->boolean('include_completed');

        return $query
            ->when(
                $assignedUserFilter === null,
                fn ($q) => $q->where(fn ($inner) => $inner
                    ->whereHas('assignedUsers', fn ($innerUsers) => $innerUsers->where('users.id', $currentUserId))
                    ->orWhereDoesntHave('assignedUsers')
                )
            )
            ->when(
                $assignedUserFilter && $assignedUserFilter !== '__all__',
                function ($q) use ($assignedUserFilter) {
                    if ($assignedUserFilter === '__unassigned__') {
                        $q->whereDoesntHave('assignedUsers');
                        return;
                    }

                    $q->whereHas('assignedUsers', fn ($usersQuery) => $usersQuery->where('users.id', $assignedUserFilter));
                }
            )
            ->when(request('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->when(request('related_type'), fn ($q, $relatedType) => $q->where('related_type', $relatedType))
            ->when(request('content_id'), fn ($q, $contentId) => $q->where('content_id', $contentId))
            ->when(
                request()->boolean('overdue_only'),
                fn ($q) => $q
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', Carbon::today()->toDateString())
            )
            ->when(
                request()->boolean('scheduled_only'),
                fn ($q) => $q->whereNotNull('scheduled_for')
            )
            ->when(
                ! $includeArchived || $excludeArchived,
                fn ($q) => $q->where('archived', false)
            )
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"));
    }

    private function buildTaskCalendarItems($tasks)
    {
        $today = Carbon::today();

        $scheduledItems = $tasks
            ->filter(fn (Task $task) => filled($task->scheduled_for))
            ->map(fn (Task $task) => [
                'id' => 'task-scheduled-'.$task->id,
                'title' => $task->title,
                'start' => optional($task->scheduled_for)->toIso8601String(),
                'type' => 'task_scheduled',
                'task_id' => $task->id,
                'color' => '#0ea5e9',
            ]);

        $deadlineItems = $tasks
            ->filter(function (Task $task) use ($today): bool {
                if (! filled($task->due_date)) {
                    return false;
                }

                if (! filled($task->scheduled_for)) {
                    return true;
                }

                return Carbon::parse($task->due_date)->lt($today);
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

        return $scheduledItems->concat($deadlineItems)->values();
    }

    public function create(): Response
    {
        return Inertia::render('Tasks/Create', [
            'statuses' => TaskStatus::query()->orderBy('order')->get(),
            'contents' => Content::query()->whereIn('status', ['queued', 'in_production', 'finalized'])->orderBy('title')->get(['id', 'title']),
            'plans' => Plan::query()->whereIn('status', ['queued', 'running'])->with('phases')->orderBy('title')->get(),
            'events' => Event::query()->orderBy('event_date')->orderBy('event_time')->get(['id', 'title']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Task $task): JsonResponse|Response
    {
        $task->load(['status', 'content', 'subtasks', 'assignedUsers', 'plan', 'planPhase', 'event']);

        if (request()->expectsJson() || request()->wantsJson()) {
            return response()->json(['task' => $task]);
        }

        return Inertia::render('Tasks/Show', [
            'task' => $task,
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $payload = $request->safe()->all();
        unset($payload['assigned_user_ids']);
        if (($payload['related_type'] ?? null) !== 'content') {
            $payload['content_id'] = null;
        }
        if (($payload['related_type'] ?? null) !== 'plan') {
            $payload['plan_id'] = null;
            $payload['plan_phase_id'] = null;
        }
        if (($payload['related_type'] ?? null) !== 'event') {
            $payload['event_id'] = null;
        }

        $task = Task::create([
            ...$payload,
            'user_id' => (string) Auth::id(),
        ]);

        $task->assignedUsers()->sync($request->input('assigned_user_ids', []));
        DispatchTaskAssignedNotificationsJob::dispatchSync($task->id, [], (string) Auth::id());

        $subtasks = collect($request->input('subtasks', []))
            ->filter(fn (array $subtask) => filled($subtask['title'] ?? null))
            ->values()
            ->map(fn (array $subtask, int $index) => [
                'title' => trim((string) ($subtask['title'] ?? '')),
                'completed' => (bool) ($subtask['completed'] ?? false),
                'order' => $index + 1,
            ]);

        if ($subtasks->isNotEmpty()) {
            $task->subtasks()->createMany($subtasks->all());
        }

        $this->refreshTaskPlanProgress($task);

        return redirect()->route('tasks.index')->with('success', 'Tarefa criada com sucesso.');
    }

    public function edit(Task $task): Response
    {
        return Inertia::render('Tasks/Edit', [
            'task' => $task->load(['subtasks', 'assignedUsers']),
            'statuses' => TaskStatus::query()->orderBy('order')->get(),
            'contents' => Content::query()->whereIn('status', ['queued', 'in_production', 'finalized'])->orderBy('title')->get(['id', 'title']),
            'plans' => Plan::query()->whereIn('status', ['queued', 'running'])->with('phases')->orderBy('title')->get(),
            'events' => Event::query()->orderBy('event_date')->orderBy('event_time')->get(['id', 'title']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $oldPlanId = $task->plan_id;
        $previousAssignedUserIds = $task->assignedUsers()
            ->pluck('users.id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $newAssignedUserIds = collect($request->input('assigned_user_ids', []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $payload = $request->safe()->all();
        unset($payload['assigned_user_ids']);
        if (($payload['related_type'] ?? null) !== 'content') {
            $payload['content_id'] = null;
        }
        if (($payload['related_type'] ?? null) !== 'plan') {
            $payload['plan_id'] = null;
            $payload['plan_phase_id'] = null;
        }
        if (($payload['related_type'] ?? null) !== 'event') {
            $payload['event_id'] = null;
        }

        $task->update($payload);
        $task->assignedUsers()->sync($newAssignedUserIds);

        $newlyAssignedUserIds = array_values(array_diff($newAssignedUserIds, $previousAssignedUserIds));
        if ($newlyAssignedUserIds !== []) {
            DispatchTaskAssignedNotificationsJob::dispatchSync($task->id, $newlyAssignedUserIds, (string) Auth::id());
        }

        $subtasks = collect($request->input('subtasks', []))
            ->filter(fn (array $subtask) => filled($subtask['title'] ?? null))
            ->values()
            ->map(fn (array $subtask, int $index) => [
                'title' => trim((string) ($subtask['title'] ?? '')),
                'completed' => (bool) ($subtask['completed'] ?? false),
                'order' => $index + 1,
            ]);

        $task->subtasks()->delete();
        if ($subtasks->isNotEmpty()) {
            $task->subtasks()->createMany($subtasks->all());
        }

        $this->refreshTaskPlanProgress($task);
        $this->refreshPlanById($oldPlanId, [$task->plan_id]);

        return redirect()->route('tasks.index')->with('success', 'Tarefa atualizada com sucesso.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $planId = $task->plan_id;
        $task->delete();

        $this->refreshPlanById($planId);

        return redirect()->route('tasks.index')->with('success', 'Tarefa removida com sucesso.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $status = TaskStatus::where('id', $request->task_status_id)->first();
        abort_unless($status, 422, 'Status inválido.');

        $task->update([
            'task_status_id' => $request->task_status_id,
            'archived' => $this->isCompletedStatusName($status->name) ? $task->archived : false,
        ]);

        $this->refreshTaskPlanProgress($task);

        return back()->with('success', 'Status da tarefa atualizado.');
    }

    public function quickAction(Request $request, Task $task): RedirectResponse
    {
        $payload = $request->validate([
            'action' => ['required', 'in:complete,archive'],
        ]);

        if ($payload['action'] === 'complete') {
            $completedStatusId = TaskStatus::query()
                ->where(function ($query) {
                    $query
                        ->where('name', 'ilike', '%conclu%')
                        ->orWhere('name', 'ilike', '%finaliz%')
                        ->orWhere('name', 'ilike', '%done%')
                        ->orWhere('name', 'ilike', '%completed%');
                })
                ->orderByDesc('order')
                ->value('id');

            if (! $completedStatusId) {
                return back()->with('error', 'Nenhum status de conclusão foi encontrado.');
            }

            $task->update([
                'task_status_id' => $completedStatusId,
                'archived' => false,
            ]);

            $this->refreshTaskPlanProgress($task);

            return back()->with('success', 'Tarefa concluída com sucesso.');
        }

        $task->loadMissing('status');

        if (! $this->isCompletedTask($task)) {
            return back()->with('error', 'Só é possível arquivar tarefas concluídas.');
        }

        $task->update(['archived' => true]);

        $this->refreshTaskPlanProgress($task);

        return back()->with('success', 'Tarefa arquivada com sucesso.');
    }

    private function isCompletedTask(Task $task): bool
    {
        return $this->isCompletedStatusName($task->status?->name);
    }

    private function isCompletedStatusName(?string $name): bool
    {
        $statusName = Str::of($name ?? '')
            ->ascii()
            ->lower()
            ->toString();

        return Str::contains($statusName, ['conclu', 'finaliz', 'done', 'completed']);
    }

    private function refreshTaskPlanProgress(Task $task): void
    {
        $planId = $task->plan_id;
        if (blank($planId)) {
            return;
        }

        $plan = Plan::query()->find($planId);
        $plan?->refreshProgress();
    }

    private function refreshPlanById(?string $planId, array $except = []): void
    {
        if (blank($planId) || in_array($planId, $except, true)) {
            return;
        }

        $plan = Plan::query()->find($planId);
        $plan?->refreshProgress();
    }
}
