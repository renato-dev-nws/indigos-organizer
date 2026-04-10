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
        ]);

        $item->update($data);

        return back()->with('success', 'Categoria de ideia atualizada com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = IdeaCategory::findOrFail($id);
        $item->delete();

        return back()->with('success', 'Categoria de ideia removida com sucesso.');
    }
}
