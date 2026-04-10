<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContentCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        ContentCategory::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Categoria de conteudo criada com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = ContentCategory::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $item->update($data);

        return back()->with('success', 'Categoria de conteudo atualizada com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = ContentCategory::findOrFail($id);
        $item->delete();

        return back()->with('success', 'Categoria de conteudo removida com sucesso.');
    }
}
