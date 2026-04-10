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
        $userId = (string) Auth::id();

        $tasks = Task::query()
            ->where('user_id', $userId)
            ->with(['status', 'content', 'subtasks'])
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
            'statuses' => TaskStatus::where('user_id', $userId)->orderBy('order')->get(),
            'contents' => Content::where('user_id', $userId)->orderBy('title')->get(['id', 'title']),
            'filters' => request()->only(['assignee', 'priority', 'type', 'content_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        $userId = (string) Auth::id();

        return Inertia::render('Tasks/Create', [
            'statuses' => TaskStatus::where('user_id', $userId)->orderBy('order')->get(),
            'contents' => Content::where('user_id', $userId)->orderBy('title')->get(['id', 'title']),
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
        $this->authorize('update', $task);

        $userId = (string) Auth::id();

        return Inertia::render('Tasks/Edit', [
            'task' => $task->load('subtasks'),
            'statuses' => TaskStatus::where('user_id', $userId)->orderBy('order')->get(),
            'contents' => Content::where('user_id', $userId)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update($request->safe()->all());

        return redirect()->route('tasks.index')->with('success', 'Tarefa atualizada com sucesso.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Tarefa removida com sucesso.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        abort_unless(
            TaskStatus::where('id', $request->task_status_id)->where('user_id', (string) Auth::id())->exists(),
            422,
            'Status invalido para este usuario.'
        );

        $task->update(['task_status_id' => $request->task_status_id]);

        return back()->with('success', 'Status da tarefa atualizado.');
    }
}
