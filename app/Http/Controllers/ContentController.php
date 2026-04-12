<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentRequest;
use App\Http\Requests\UpdateContentRequest;
use App\Models\Content;
use App\Models\ContentPlatform;
use App\Models\Idea;
use App\Models\IdeaCategory;
use App\Models\IdeaType;
use App\Models\VenueStyle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function index(): Response
    {
        $query = Content::query()
            ->with(['platforms', 'type', 'category', 'styles', 'idea', 'user'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('content_platform_id'), fn ($q, $platformId) => $q->whereHas('platforms', fn ($q2) => $q2->where('content_platforms.id', $platformId)))
            ->when(request('idea_type_id'), fn ($q, $typeId) => $q->where('idea_type_id', $typeId))
            ->when(request('idea_category_id'), fn ($q, $categoryId) => $q->where('idea_category_id', $categoryId))
            ->when(request('venue_style_id'), fn ($q, $styleId) => $q->whereHas('styles', fn ($q2) => $q2->where('venue_styles.id', $styleId)))
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
            'filters' => request()->only(['status', 'content_platform_id', 'idea_type_id', 'idea_category_id', 'venue_style_id', 'planned_week', 'search']),
            'platforms' => ContentPlatform::query()->orderBy('name')->get(),
            'types' => IdeaType::query()->orderBy('name')->get(),
            'categories' => IdeaCategory::query()->orderBy('name')->get(),
            'styles' => VenueStyle::query()->where('domain', VenueStyle::DOMAIN_CONTENT)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Contents/Create', [
            'platforms' => ContentPlatform::query()->orderBy('name')->get(),
            'types' => IdeaType::query()->orderBy('name')->get(),
            'categories' => IdeaCategory::query()->orderBy('name')->get(),
            'styles' => VenueStyle::query()->where('domain', VenueStyle::DOMAIN_CONTENT)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'ideas' => Idea::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(StoreContentRequest $request): RedirectResponse
    {
        $content = Content::create([
            ...$request->safe()->except(['links', 'content_platform_ids', 'venue_style_ids']),
            'user_id' => (string) Auth::id(),
        ]);

        $content->platforms()->sync($request->input('content_platform_ids', []));
        $content->styles()->sync($request->input('venue_style_ids', []));

        foreach ($request->input('links', []) as $link) {
            $content->links()->create($link);
        }

        return redirect()->route('contents.index')->with('success', 'Conteudo criado com sucesso.');
    }

    public function show(Content $content): Response
    {
        return Inertia::render('Contents/Show', [
            'content' => $content->load(['platforms', 'type', 'category', 'styles', 'files', 'links', 'idea', 'user']),
        ]);
    }

    public function edit(Content $content): Response
    {
        return Inertia::render('Contents/Edit', [
            'content' => $content->load(['platforms', 'type', 'category', 'styles', 'links', 'files']),
            'platforms' => ContentPlatform::query()->orderBy('name')->get(),
            'types' => IdeaType::query()->orderBy('name')->get(),
            'categories' => IdeaCategory::query()->orderBy('name')->get(),
            'styles' => VenueStyle::query()->where('domain', VenueStyle::DOMAIN_CONTENT)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'ideas' => Idea::query()->orderBy('title')->get(['id', 'title']),
            'venueStyleIds' => $content->styles()->pluck('venue_styles.id')->all(),
        ]);
    }

    public function update(UpdateContentRequest $request, Content $content): RedirectResponse
    {
        $content->update($request->safe()->except(['links', 'content_platform_ids', 'venue_style_ids']));
        $content->platforms()->sync($request->input('content_platform_ids', []));
        $content->styles()->sync($request->input('venue_style_ids', []));

        $content->links()->delete();
        foreach ($request->input('links', []) as $link) {
            $content->links()->create($link);
        }

        return redirect()->route('contents.index')->with('success', 'Conteudo atualizado com sucesso.');
    }

    public function destroy(Content $content): RedirectResponse
    {
        $content->delete();

        return redirect()->route('contents.index')->with('success', 'Conteudo removido com sucesso.');
    }
}
