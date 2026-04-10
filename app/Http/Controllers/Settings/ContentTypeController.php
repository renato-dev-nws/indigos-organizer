<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentTypeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        ContentType::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Tipo de conteudo criado com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = ContentType::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $item->update($data);

        return back()->with('success', 'Tipo de conteudo atualizado com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = ContentType::findOrFail($id);
        $item->delete();

        return back()->with('success', 'Tipo de conteudo removido com sucesso.');
    }
}
