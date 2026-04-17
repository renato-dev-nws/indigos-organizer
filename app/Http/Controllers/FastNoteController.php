<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFastNoteRequest;
use App\Http\Requests\UpdateFastNoteRequest;
use App\Models\FastNote;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FastNoteController extends Controller
{
    public function index(Request $request): Response
    {
        $archivedVisible = $request->boolean('show_archived');
        $archivedLimit = max(16, (int) $request->integer('archived_limit', 16));

        $activeNotes = FastNote::query()
            ->where('user_id', Auth::id())
            ->whereNull('archived_at')
            ->latest('created_at')
            ->get();

        $archivedNotes = collect();
        $hasMoreArchived = false;

        if ($archivedVisible) {
            $archivedQuery = FastNote::query()
                ->where('user_id', Auth::id())
                ->whereNotNull('archived_at')
                ->orderByDesc('archived_at')
                ->orderByDesc('created_at');

            $archivedNotes = $archivedQuery->limit($archivedLimit)->get();
            $hasMoreArchived = $archivedQuery->count() > $archivedNotes->count();
        }

        return Inertia::render('FastNotes/Index', [
            'activeNotes' => $activeNotes,
            'archivedNotes' => $archivedNotes,
            'showArchived' => $archivedVisible,
            'archivedLimit' => $archivedLimit,
            'hasMoreArchived' => $hasMoreArchived,
            'openCreate' => $request->boolean('open_create'),
        ]);
    }

    public function store(StoreFastNoteRequest $request): RedirectResponse
    {
        $userId = (string) Auth::id();
        $today = Carbon::today()->toDateString();

        $countToday = FastNote::query()
            ->where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->count();

        $title = 'Nota '.$today;
        if ($countToday > 0) {
            $title .= ' '.($countToday + 1);
        }

        FastNote::create([
            'user_id' => $userId,
            'title' => $title,
            ...$this->validatedPayload($request->validated()),
        ]);

        return back()->with('success', 'Nota rápida criada com sucesso.');
    }

    public function update(UpdateFastNoteRequest $request, FastNote $fastNote): RedirectResponse
    {
        abort_unless((string) $fastNote->user_id === (string) Auth::id(), 403);

        $fastNote->update($this->validatedPayload($request->validated()));

        return back()->with('success', 'Nota rápida atualizada com sucesso.');
    }

    public function archive(FastNote $fastNote): RedirectResponse
    {
        abort_unless((string) $fastNote->user_id === (string) Auth::id(), 403);

        $fastNote->update([
            'archived_at' => Carbon::now(),
        ]);

        return back()->with('success', 'Nota rápida arquivada com sucesso.');
    }

    public function destroy(FastNote $fastNote): RedirectResponse
    {
        abort_unless((string) $fastNote->user_id === (string) Auth::id(), 403);

        $fastNote->delete();

        return back()->with('success', 'Nota rápida removida com sucesso.');
    }

    private function validatedPayload(array $validated): array
    {
        $listItems = collect($validated['list_items'] ?? [])
            ->map(fn (array $row) => ['item' => trim((string) ($row['item'] ?? ''))])
            ->filter(fn (array $row) => filled($row['item']))
            ->values()
            ->all();

        return [
            'related_type' => $validated['related_type'],
            'list_items' => $listItems,
            'note' => $validated['note'] ?? null,
            'is_priority' => (bool) ($validated['is_priority'] ?? false),
        ];
    }
}
