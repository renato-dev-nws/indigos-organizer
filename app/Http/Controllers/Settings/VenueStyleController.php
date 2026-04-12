<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\VenueStyle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VenueStyleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        VenueStyle::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Estilo de local criado com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = VenueStyle::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        $item->update($data);

        return back()->with('success', 'Estilo de local atualizado com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = VenueStyle::withCount(['venues', 'ideas', 'contents'])->findOrFail($id);

        if ($item->venues_count > 0 || $item->ideas_count > 0 || $item->contents_count > 0) {
            return back()->with('error', 'Não é permitido remover estilo com locais, ideias ou conteúdos vinculados.');
        }

        $item->delete();

        return back()->with('success', 'Estilo de local removido com sucesso.');
    }
}
