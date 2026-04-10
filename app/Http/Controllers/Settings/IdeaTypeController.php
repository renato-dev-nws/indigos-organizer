<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\IdeaType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdeaTypeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        IdeaType::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Tipo de ideia criado com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $ideaType = IdeaType::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $ideaType->update($data);

        return back()->with('success', 'Tipo de ideia atualizado com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $ideaType = IdeaType::withCount('ideas')->findOrFail($id);

        if ($ideaType->ideas_count > 0) {
            return back()->with('error', 'Nao e permitido remover um tipo de ideia vinculado a ideias.');
        }

        $ideaType->delete();

        return back()->with('success', 'Tipo de ideia removido com sucesso.');
    }
}
