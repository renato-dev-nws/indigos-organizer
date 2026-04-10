<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubtaskRequest;
use App\Http\Requests\UpdateSubtaskRequest;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class SubtaskController extends Controller
{
    public function store(StoreSubtaskRequest $request, Task $task): RedirectResponse
    {
        $task->subtasks()->create($request->validated());

        return back()->with('success', 'Subtarefa criada com sucesso.');
    }

    public function update(UpdateSubtaskRequest $request, Task $task, Subtask $subtask): RedirectResponse
    {
        abort_unless($subtask->task_id === $task->id, 404);

        $subtask->update($request->validated());

        return back()->with('success', 'Subtarefa atualizada com sucesso.');
    }

    public function destroy(Task $task, Subtask $subtask): RedirectResponse
    {
        abort_unless($subtask->task_id === $task->id, 404);

        $subtask->delete();

        return back()->with('success', 'Subtarefa removida com sucesso.');
    }
}
