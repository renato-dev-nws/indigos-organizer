<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\IdeaCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IdeaCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/i'],
        ]);

        IdeaCategory::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Categoria de ideia criada com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = IdeaCategory::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/i'],
        ]);

        $item->update($data);

        return back()->with('success', 'Categoria de ideia atualizada com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = IdeaCategory::withCount(['ideas', 'contents'])->findOrFail($id);

        if ($item->ideas_count > 0 || $item->contents_count > 0) {
            return back()->with('error', 'Nao e permitido remover categoria vinculada a ideias ou conteudos.');
        }

        $item->delete();

        return back()->with('success', 'Categoria de ideia removida com sucesso.');
    }
}
