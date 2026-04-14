<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\EventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventTypeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/i'],
        ]);

        EventType::create([
            ...$data,
            'user_id' => (string) Auth::id(),
        ]);

        return back()->with('success', 'Tipo de evento criado com sucesso.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = EventType::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+:[a-z0-9-]+$/i'],
        ]);

        $item->update($data);

        return back()->with('success', 'Tipo de evento atualizado com sucesso.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = EventType::withCount('events')->findOrFail($id);

        if ($item->events_count > 0) {
            return back()->with('error', 'Não é permitido excluir tipo com eventos vinculados.');
        }

        $item->delete();

        return back()->with('success', 'Tipo de evento removido com sucesso.');
    }
}