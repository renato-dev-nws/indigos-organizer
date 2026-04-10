<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Content;
use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        $tasks = Task::query()
            ->with(['status', 'content', 'subtasks', 'user'])
            ->when(request('assignee'), fn ($q, $assignee) => $q->where('assignee', 'ilike', "%{$assignee}%"))
            ->when(request('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->when(request('type'), fn ($q, $type) => $q->where('type', $type))
            ->when(request('content_id'), fn ($q, $contentId) => $q->where('content_id', $contentId))
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'statuses' => TaskStatus::query()->orderBy('order')->get(),
            'contents' => Content::query()->orderBy('title')->get(['id', 'title']),
            'filters' => request()->only(['assignee', 'priority', 'type', 'content_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tasks/Create', [
            'statuses' => TaskStatus::query()->orderBy('order')->get(),
            'contents' => Content::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = Task::create([
            ...$request->safe()->all(),
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
            'contents' => Content::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->safe()->all());

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
