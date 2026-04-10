<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentRequest;
use App\Http\Requests\UpdateContentRequest;
use App\Models\Content;
use App\Models\ContentCategory;
use App\Models\ContentPlatform;
use App\Models\ContentType;
use App\Models\Idea;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function index(): Response
    {
        $userId = (string) Auth::id();

        $query = Content::query()
            ->where('user_id', $userId)
            ->with(['platform', 'type', 'categories', 'idea'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('content_platform_id'), fn ($q, $platformId) => $q->where('content_platform_id', $platformId))
            ->when(request('content_type_id'), fn ($q, $typeId) => $q->where('content_type_id', $typeId))
            ->when(request('content_category_id'), fn ($q, $categoryId) => $q->whereHas('categories', fn ($qq) => $qq->where('content_categories.id', $categoryId)))
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"));

        if (request('planned_week')) {
            [$year, $week] = explode('-W', request('planned_week'));
            $start = Carbon::now()->setISODate((int) $year, (int) $week)->startOfWeek();
            $end = (clone $start)->endOfWeek();
            $query->whereBetween('planned_publish_at', [$start, $end]);
        }

        $contents = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Contents/Index', [
            'contents' => $contents,
            'filters' => request()->only(['status', 'content_platform_id', 'content_type_id', 'content_category_id', 'planned_week', 'search']),
            'platforms' => ContentPlatform::where('user_id', $userId)->orderBy('name')->get(),
            'types' => ContentType::where('user_id', $userId)->orderBy('name')->get(),
            'categories' => ContentCategory::where('user_id', $userId)->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        $userId = (string) Auth::id();

        return Inertia::render('Contents/Create', [
            'platforms' => ContentPlatform::where('user_id', $userId)->orderBy('name')->get(),
            'types' => ContentType::where('user_id', $userId)->orderBy('name')->get(),
            'categories' => ContentCategory::where('user_id', $userId)->orderBy('name')->get(),
            'ideas' => Idea::where('user_id', $userId)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(StoreContentRequest $request): RedirectResponse
    {
        $content = Content::create([
            ...$request->safe()->except(['content_category_ids', 'links']),
            'user_id' => (string) Auth::id(),
        ]);

        $content->categories()->sync($request->input('content_category_ids', []));

        foreach ($request->input('links', []) as $link) {
            $content->links()->create($link);
        }

        return redirect()->route('contents.index')->with('success', 'Conteudo criado com sucesso.');
    }

    public function show(Content $content): Response
    {
        $this->authorize('view', $content);

        return Inertia::render('Contents/Show', [
            'content' => $content->load(['platform', 'type', 'categories', 'files', 'links', 'idea']),
        ]);
    }

    public function edit(Content $content): Response
    {
        $this->authorize('update', $content);

        $userId = (string) Auth::id();

        return Inertia::render('Contents/Edit', [
            'content' => $content->load(['categories', 'links', 'files']),
            'platforms' => ContentPlatform::where('user_id', $userId)->orderBy('name')->get(),
            'types' => ContentType::where('user_id', $userId)->orderBy('name')->get(),
            'categories' => ContentCategory::where('user_id', $userId)->orderBy('name')->get(),
            'ideas' => Idea::where('user_id', $userId)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(UpdateContentRequest $request, Content $content): RedirectResponse
    {
        $this->authorize('update', $content);

        $content->update($request->safe()->except(['content_category_ids', 'links']));
        $content->categories()->sync($request->input('content_category_ids', []));

        $content->links()->delete();
        foreach ($request->input('links', []) as $link) {
            $content->links()->create($link);
        }

        return redirect()->route('contents.index')->with('success', 'Conteudo atualizado com sucesso.');
    }

    public function destroy(Content $content): RedirectResponse
    {
        $this->authorize('delete', $content);
        $content->delete();

        return redirect()->route('contents.index')->with('success', 'Conteudo removido com sucesso.');
    }
}
