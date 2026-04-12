<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContentPlatform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentPlatformController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        ContentPlatform::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Plataforma criada com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = ContentPlatform::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        $item->update($data);

        return back()->with('success', 'Plataforma atualizada com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = ContentPlatform::withCount('contents')->findOrFail($id);

        if ($item->contents_count > 0) {
            return back()->with('error', 'Nao e permitido remover plataforma com conteudos vinculados.');
        }

        $item->delete();

        return back()->with('success', 'Plataforma removida com sucesso.');
    }
}
