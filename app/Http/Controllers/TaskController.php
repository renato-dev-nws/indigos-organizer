<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Content;
use App\Models\Plan;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        $tasks = Task::query()
            ->with(['status', 'content', 'subtasks', 'assignedUser', 'plan', 'planPhase'])
            ->when(request('assigned_user_id'), fn ($q, $assignedUserId) => $q->where('assigned_user_id', $assignedUserId))
            ->when(request('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->when(request('related_type'), fn ($q, $relatedType) => $q->where('related_type', $relatedType))
            ->when(request('content_id'), fn ($q, $contentId) => $q->where('content_id', $contentId))
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'statuses' => TaskStatus::query()->orderBy('order')->get(),
            'contents' => Content::query()->orderBy('title')->get(['id', 'title']),
            'plans' => Plan::query()
                ->whereIn('status', ['queued', 'running'])
                ->with('phases')
                ->orderBy('title')
                ->get(['id', 'title', 'status']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'filters' => request()->only(['assigned_user_id', 'priority', 'related_type', 'content_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tasks/Create', [
            'statuses' => TaskStatus::query()->orderBy('order')->get(),
            'contents' => Content::query()->whereIn('status', ['queued', 'in_production'])->orderBy('title')->get(['id', 'title']),
            'plans' => Plan::query()->whereIn('status', ['queued', 'running'])->with('phases')->orderBy('title')->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
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

        $task = Task::create([
            ...$payload,
            'user_id' => (string) Auth::id(),
        ]);

        foreach ($request->input('subtasks', []) as $subtask) {
            $task->subtasks()->create($subtask);
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

        $task->update($payload);

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
