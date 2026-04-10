<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskStatusController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'order' => ['nullable', 'integer', 'min:1'],
        ]);

        $data['order'] ??= (TaskStatus::where('user_id', (string) Auth::id())->max('order') ?? 0) + 1;

        TaskStatus::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Status de tarefa criado com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = TaskStatus::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'order' => ['required', 'integer', 'min:1'],
        ]);

        $item->update($data);

        return back()->with('success', 'Status de tarefa atualizado com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = TaskStatus::withCount('tasks')->findOrFail($id);

        if ($item->tasks_count > 0) {
            return back()->with('error', 'Nao e permitido remover status com tarefas vinculadas.');
        }

        $item->delete();

        return back()->with('success', 'Status de tarefa removido com sucesso.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'uuid', 'exists:task_statuses,id'],
        ]);

        $ids = $validated['ordered_ids'];
        $existingCount = TaskStatus::whereIn('id', $ids)->count();
        abort_unless($existingCount === count($ids), 403);

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $index => $id) {
                TaskStatus::where('id', $id)->update(['order' => $index + 1]);
            }
        });

        return back()->with('success', 'Ordem de status atualizada com sucesso.');
    }
}
