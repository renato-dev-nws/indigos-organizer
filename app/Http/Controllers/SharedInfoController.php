<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSharedInfoRequest;
use App\Http\Requests\UpdateSharedInfoRequest;
use App\Models\SharedInfoCategory;
use App\Models\SharedInfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SharedInfoController extends Controller
{
    public function index(): Response
    {
        $infos = SharedInfo::query()
            ->with(['user', 'documents', 'links', 'categories'])
            ->when(request('title'), fn ($query, $title) => $query->where('title', 'ilike', "%{$title}%"))
            ->when(request('shared_info_category_id'), fn ($query, $categoryId) => $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->where('shared_info_categories.id', $categoryId)))
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('title', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%")
                        ->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery->where('name', 'ilike', "%{$search}%"))
                        ->orWhereHas('links', fn ($linkQuery) => $linkQuery
                            ->where('title', 'ilike', "%{$search}%")
                            ->orWhere('description', 'ilike', "%{$search}%")
                        )
                        ->orWhereHas('documents', fn ($documentQuery) => $documentQuery->where('original_name', 'ilike', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SharedInfos/Index', [
            'sharedInfos' => $infos,
            'categories' => SharedInfoCategory::query()->orderBy('name')->get(['id', 'name', 'icon']),
            'filters' => request()->only(['title', 'shared_info_category_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SharedInfos/Create', [
            'categories' => SharedInfoCategory::query()->orderBy('name')->get(['id', 'name', 'icon']),
        ]);
    }

    public function store(StoreSharedInfoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $info = SharedInfo::create([
                ...$request->safe()->except(['links', 'documents', 'shared_info_category_ids']),
                'user_id' => (string) Auth::id(),
            ]);

            $info->categories()->sync($request->input('shared_info_category_ids', []));

            foreach ($request->input('links', []) as $link) {
                $info->links()->create($link);
            }

            foreach ($request->file('documents', []) as $document) {
                $path = $document->store('shared-docs', 'public');
                $info->documents()->create([
                    'file_path' => $path,
                    'original_name' => $document->getClientOriginalName(),
                    'mime_type' => $document->getClientMimeType(),
                    'size' => $document->getSize(),
                ]);
            }
        });

        return redirect()->route('shared-infos.index')->with('success', 'Informação criada com sucesso.');
    }

    public function show(SharedInfo $sharedInfo): Response
    {
        return Inertia::render('SharedInfos/Show', [
            'sharedInfo' => $sharedInfo->load(['user', 'links', 'documents', 'categories']),
        ]);
    }

    public function edit(SharedInfo $sharedInfo): Response
    {
        return Inertia::render('SharedInfos/Edit', [
            'sharedInfo' => $sharedInfo->load(['links', 'documents', 'categories']),
            'categories' => SharedInfoCategory::query()->orderBy('name')->get(['id', 'name', 'icon']),
        ]);
    }

    public function update(UpdateSharedInfoRequest $request, SharedInfo $sharedInfo): RedirectResponse
    {
        DB::transaction(function () use ($request, $sharedInfo): void {
            $sharedInfo->update($request->safe()->except(['links', 'documents', 'shared_info_category_ids']));
            $sharedInfo->categories()->sync($request->input('shared_info_category_ids', []));

            $incomingLinks = collect($request->input('links', []));
            $incomingIds = $incomingLinks->pluck('id')->filter()->values();

            $sharedInfo->links()->when($incomingIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $incomingIds))->delete();
            if ($incomingIds->isEmpty()) {
                $sharedInfo->links()->delete();
            }

            foreach ($incomingLinks as $link) {
                if (! empty($link['id'])) {
                    $sharedInfo->links()->where('id', $link['id'])->update([
                        'title' => $link['title'],
                        'url' => $link['url'],
                        'description' => $link['description'] ?? null,
                    ]);
                } else {
                    $sharedInfo->links()->create([
                        'title' => $link['title'],
                        'url' => $link['url'],
                        'description' => $link['description'] ?? null,
                    ]);
                }
            }

            foreach ($request->file('documents', []) as $document) {
                $path = $document->store('shared-docs', 'public');
                $sharedInfo->documents()->create([
                    'file_path' => $path,
                    'original_name' => $document->getClientOriginalName(),
                    'mime_type' => $document->getClientMimeType(),
                    'size' => $document->getSize(),
                ]);
            }
        });

        return redirect()->route('shared-infos.index')->with('success', 'Informação atualizada com sucesso.');
    }

    public function destroy(SharedInfo $sharedInfo): RedirectResponse
    {
        $sharedInfo->delete();

        return redirect()->route('shared-infos.index')->with('success', 'Informação removida com sucesso.');
    }
}
