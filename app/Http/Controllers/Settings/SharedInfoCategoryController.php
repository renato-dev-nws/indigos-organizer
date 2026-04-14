<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SharedInfoCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SharedInfoCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/i'],
        ]);

        SharedInfoCategory::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Categoria de informação criada com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = SharedInfoCategory::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/i'],
        ]);

        $item->update($data);

        return back()->with('success', 'Categoria de informação atualizada com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = SharedInfoCategory::withCount('sharedInfos')->findOrFail($id);

        if ($item->shared_infos_count > 0) {
            return back()->with('error', 'Não é permitido excluir categoria com informações vinculadas.');
        }

        $item->delete();

        return back()->with('success', 'Categoria de informação removida com sucesso.');
    }
}