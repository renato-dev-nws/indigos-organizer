<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\VenueCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VenueCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        VenueCategory::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Categoria de local criada com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = VenueCategory::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $item->update($data);

        return back()->with('success', 'Categoria de local atualizada com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = VenueCategory::withCount('venues')->findOrFail($id);

        if ($item->venues_count > 0) {
            return back()->with('error', 'Não é permitido remover categoria com locais vinculados.');
        }

        $item->delete();

        return back()->with('success', 'Categoria de local removida com sucesso.');
    }
}
