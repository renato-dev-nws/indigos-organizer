<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentRequest;
use App\Http\Requests\UpdateContentRequest;
use App\Models\Content;
use App\Models\ContentPlatform;
use App\Models\Idea;
use App\Models\IdeaCategory;
use App\Models\IdeaType;
use App\Models\Plan;
use App\Models\VenueStyle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function index(): Response
    {
        [$chartStart, $chartEnd, $chartMeta] = $this->resolveChartPeriod();

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
            'filters' => request()->only(['status', 'content_platform_id', 'idea_type_id', 'idea_category_id', 'venue_style_id', 'planned_week', 'search', 'chart_period', 'chart_start', 'chart_end']),
            'contentChartPeriod' => $chartMeta,
            'contentCharts' => $this->buildContentCharts($chartStart, $chartEnd),
            'platforms' => ContentPlatform::query()->orderBy('name')->get(),
            'types' => IdeaType::query()->orderBy('name')->get(),
            'categories' => IdeaCategory::query()->orderBy('name')->get(),
            'styles' => VenueStyle::query()->where('domain', VenueStyle::DOMAIN_CONTENT)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
        ]);
    }

    private function resolveChartPeriod(): array
    {
        $period = request('chart_period', '7d');
        $now = Carbon::now()->endOfDay();

        if ($period === 'custom') {
            $startInput = request('chart_start');
            $endInput = request('chart_end');

            $start = $startInput ? Carbon::parse($startInput)->startOfDay() : Carbon::now()->subDays(29)->startOfDay();
            $end = $endInput ? Carbon::parse($endInput)->endOfDay() : $now;

            if ($end->lessThan($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            return [$start, $end, [
                'period' => 'custom',
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ]];
        }

        $days = match ($period) {
            '7d' => 7,
            '15d' => 15,
            default => 30,
        };

        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        return [$start, $now, [
            'period' => in_array($period, ['7d', '15d', '30d'], true) ? $period : '7d',
            'start' => $start->toDateString(),
            'end' => $now->toDateString(),
        ]];
    }

    private function buildContentCharts(Carbon $start, Carbon $end): array
    {
        $base = Content::query()
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('content_platform_id'), fn ($q, $platformId) => $q->whereHas('platforms', fn ($q2) => $q2->where('content_platforms.id', $platformId)))
            ->when(request('idea_type_id'), fn ($q, $typeId) => $q->where('idea_type_id', $typeId))
            ->when(request('idea_category_id'), fn ($q, $categoryId) => $q->where('idea_category_id', $categoryId))
            ->when(request('venue_style_id'), fn ($q, $styleId) => $q->whereHas('styles', fn ($q2) => $q2->where('venue_styles.id', $styleId)))
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"));

        $periodBase = (clone $base)->whereBetween('created_at', [$start, $end]);

        $labels = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $labels[] = $cursor->format('d/m');
            $cursor->addDay();
        }

        $createdByDate = (clone $base)
            ->selectRaw("to_char(created_at::date, 'YYYY-MM-DD') as day, count(*) as total")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        $publishedByDate = (clone $base)
            ->selectRaw("to_char(published_at::date, 'YYYY-MM-DD') as day, count(*) as total")
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        $dateKeys = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $dateKeys[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        $typeCounts = IdeaType::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (IdeaType $type) => [
                'name' => $type->name,
                'total' => (int) (clone $periodBase)->where('idea_type_id', $type->id)->count(),
            ]);

        $categoryCounts = IdeaCategory::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (IdeaCategory $category) => [
                'name' => $category->name,
                'total' => (int) (clone $periodBase)->where('idea_category_id', $category->id)->count(),
            ]);

        $styleCounts = VenueStyle::query()
            ->where('domain', VenueStyle::DOMAIN_CONTENT)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (VenueStyle $style) => [
                'name' => $style->name,
                'total' => (int) (clone $base)
                    ->whereBetween('contents.created_at', [$start, $end])
                    ->whereHas('styles', fn ($query) => $query->where('venue_styles.id', $style->id))
                    ->count(),
            ]);

        $statusCounts = (clone $periodBase)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $platforms = ContentPlatform::query()->orderBy('name')->get(['id', 'name']);
        $platformCounts = $platforms->map(function (ContentPlatform $platform) use ($base, $start, $end) {
            $count = (clone $base)
                ->whereBetween('contents.created_at', [$start, $end])
                ->whereHas('platforms', fn ($query) => $query->where('content_platforms.id', $platform->id))
                ->count();

            return [
                'name' => $platform->name,
                'total' => (int) $count,
            ];
        });

        $statusOrder = ['queued', 'in_production', 'finalized', 'published', 'cancelled', 'paused'];
        $statusLabels = [
            'queued' => 'Na fila',
            'in_production' => 'Em produção',
            'finalized' => 'Finalizado',
            'published' => 'Publicado',
            'cancelled' => 'Cancelado',
            'paused' => 'Pausado',
        ];

        return [
            'lineCreatedPublished' => [
                'labels' => $labels,
                'created' => collect($dateKeys)->map(fn (string $day) => (int) ($createdByDate[$day] ?? 0))->values(),
                'published' => collect($dateKeys)->map(fn (string $day) => (int) ($publishedByDate[$day] ?? 0))->values(),
            ],
            'types' => [
                'labels' => $typeCounts->pluck('name')->values(),
                'data' => $typeCounts->pluck('total')->values(),
            ],
            'categories' => [
                'labels' => $categoryCounts->pluck('name')->values(),
                'data' => $categoryCounts->pluck('total')->values(),
            ],
            'styles' => [
                'labels' => $styleCounts->pluck('name')->values(),
                'data' => $styleCounts->pluck('total')->values(),
            ],
            'statuses' => [
                'labels' => collect($statusOrder)->map(fn (string $status) => $statusLabels[$status])->values(),
                'data' => collect($statusOrder)->map(fn (string $status) => (int) ($statusCounts[$status] ?? 0))->values(),
            ],
            'platforms' => [
                'labels' => $platformCounts->pluck('name')->values(),
                'data' => $platformCounts->pluck('total')->values(),
            ],
        ];
    }

    public function create(): Response
    {
        return Inertia::render('Contents/Create', [
            'platforms' => ContentPlatform::query()->orderBy('name')->get(),
            'types' => IdeaType::query()->orderBy('name')->get(),
            'categories' => IdeaCategory::query()->orderBy('name')->get(),
            'styles' => VenueStyle::query()->where('domain', VenueStyle::DOMAIN_CONTENT)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'ideas' => Idea::query()->orderBy('title')->get(['id', 'title']),
            'plans' => Plan::query()->whereIn('status', ['queued', 'running'])->orderBy('title')->get(['id', 'title']),
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
            'content' => $content->load(['platforms', 'type', 'category', 'styles', 'files', 'links', 'idea', 'plan', 'user']),
        ]);
    }

    public function edit(Content $content): Response
    {
        return Inertia::render('Contents/Edit', [
            'content' => $content->load(['platforms', 'type', 'category', 'styles', 'links', 'files', 'plan']),
            'platforms' => ContentPlatform::query()->orderBy('name')->get(),
            'types' => IdeaType::query()->orderBy('name')->get(),
            'categories' => IdeaCategory::query()->orderBy('name')->get(),
            'styles' => VenueStyle::query()->where('domain', VenueStyle::DOMAIN_CONTENT)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'ideas' => Idea::query()->orderBy('title')->get(['id', 'title']),
            'plans' => Plan::query()->whereIn('status', ['queued', 'running'])->orderBy('title')->get(['id', 'title']),
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
