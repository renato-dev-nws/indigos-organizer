<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Content;
use App\Models\Plan;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        $tasks = $this->applyTaskFilters(Task::query())
            ->with(['status', 'content', 'subtasks', 'assignedUser', 'plan', 'planPhase', 'event'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $calendarSourceTasks = $this->applyTaskFilters(Task::query())
            ->with(['status:id,name,color'])
            ->get(['id', 'title', 'task_status_id', 'scheduled_for', 'due_date']);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'taskCalendarItems' => $this->buildTaskCalendarItems($calendarSourceTasks),
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
            'filters' => request()->only(['assigned_user_id', 'priority', 'related_type', 'content_id', 'search']),
        ]);
    }

    private function applyTaskFilters(Builder $query): Builder
    {
        $assignedUserFilter = request('assigned_user_id');
        if (blank($assignedUserFilter)) {
            $assignedUserFilter = null;
        }

        $currentUserId = (string) Auth::id();

        return $query
            ->when(
                $assignedUserFilter === null,
                fn ($q) => $q->where(fn ($inner) => $inner
                    ->where('assigned_user_id', $currentUserId)
                    ->orWhereNull('assigned_user_id')
                )
            )
            ->when(
                $assignedUserFilter && $assignedUserFilter !== '__all__',
                fn ($q) => $q->where('assigned_user_id', $assignedUserFilter)
            )
            ->when(request('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->when(request('related_type'), fn ($q, $relatedType) => $q->where('related_type', $relatedType))
            ->when(request('content_id'), fn ($q, $contentId) => $q->where('content_id', $contentId))
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"));
    }

    private function buildTaskCalendarItems($tasks)
    {
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
            ->filter(fn (Task $task) => filled($task->due_date) && $this->isRunningTask($task))
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

    private function isRunningTask(Task $task): bool
    {
        $statusName = Str::of($task->status?->name ?? '')
            ->ascii()
            ->lower()
            ->toString();

        return Str::contains($statusName, ['execucao', 'executando', 'running']);
    }

    public function create(): Response
    {
        return Inertia::render('Tasks/Create', [
            'statuses' => TaskStatus::query()->orderBy('order')->get(),
            'contents' => Content::query()->whereIn('status', ['queued', 'in_production'])->orderBy('title')->get(['id', 'title']),
            'plans' => Plan::query()->whereIn('status', ['queued', 'running'])->with('phases')->orderBy('title')->get(),
            'events' => Event::query()->orderBy('event_date')->orderBy('event_time')->get(['id', 'title']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Task $task): JsonResponse
    {
        return response()->json([
            'task' => $task->load(['status', 'content', 'subtasks', 'assignedUser', 'plan', 'planPhase', 'event']),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $payload = $request->safe()->all();
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

        return redirect()->route('tasks.index')->with('success', 'Tarefa criada com sucesso.');
    }

    public function edit(Task $task): Response
    {
        return Inertia::render('Tasks/Edit', [
            'task' => $task->load('subtasks'),
            'statuses' => TaskStatus::query()->orderBy('order')->get(),
            'contents' => Content::query()->whereIn('status', ['queued', 'in_production'])->orderBy('title')->get(['id', 'title']),
            'plans' => Plan::query()->whereIn('status', ['queued', 'running'])->with('phases')->orderBy('title')->get(),
            'events' => Event::query()->orderBy('event_date')->orderBy('event_time')->get(['id', 'title']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $payload = $request->safe()->all();
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

        return redirect()->route('tasks.index')->with('success', 'Tarefa atualizada com sucesso.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Tarefa removida com sucesso.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        abort_unless(TaskStatus::where('id', $request->task_status_id)->exists(), 422, 'Status inválido.');

        $task->update(['task_status_id' => $request->task_status_id]);

        return back()->with('success', 'Status da tarefa atualizado.');
    }
}
