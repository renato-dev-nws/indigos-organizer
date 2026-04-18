<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use App\Models\Contact;
use App\Models\Venue;
use App\Models\VenueCategory;
use App\Models\VenueStyle;
use App\Models\VenueType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function index(): Response
    {
        $venues = $this->applyVenueFilters(Venue::query())
            ->with(['user', 'type', 'category', 'styles'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $mapPoints = $this->applyVenueFilters(Venue::query())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('type:id,name,color,icon')
            ->get([
                'id',
                'name',
                'latitude',
                'longitude',
                'venue_type_id',
                'address_line',
                'address_number',
                'neighborhood',
                'city',
                'state',
                'postal_code',
                'country',
                'rating',
            ])
            ->map(fn ($venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'lat' => (float) $venue->latitude,
                'lng' => (float) $venue->longitude,
                'type' => $venue->type,
                'rating' => $venue->rating,
                'address' => collect([
                    $venue->address_line,
                    $venue->address_number,
                    $venue->neighborhood,
                    trim(implode(' - ', array_filter([$venue->city, $venue->state]))),
                    $venue->postal_code,
                    $venue->country,
                ])->filter()->implode(', '),
            ]);

        return Inertia::render('Venues/Index', [
            'venues' => $venues,
            'venueCharts' => $this->buildVenueCharts(),
            'sizes' => collect(),
            'types' => VenueType::orderBy('name')->get(['id', 'name', 'color']),
            'categories' => VenueCategory::orderBy('name')->get(['id', 'name', 'color']),
            'styles' => VenueStyle::where('domain', VenueStyle::DOMAIN_VENUES)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'mapPoints' => $mapPoints,
            'filters' => request()->only(['venue_type_id', 'venue_category_id', 'venue_style_id', 'status', 'city', 'has_performed', 'rating', 'search']),
        ]);
    }

    private function buildVenueCharts(): array
    {
        $scope = $this->applyVenueFilters(Venue::query());

        $types = VenueType::query()->orderBy('name')->get(['id', 'name']);
        $categories = VenueCategory::query()->orderBy('name')->get(['id', 'name']);
        $styles = VenueStyle::query()->where('domain', VenueStyle::DOMAIN_VENUES)->orderBy('name')->get(['id', 'name']);

        $stateCounts = (clone $scope)
            ->selectRaw('upper(state) as state, count(*) as total')
            ->whereNotNull('state')
            ->where('state', '<>', '')
            ->groupBy('state')
            ->orderByRaw('count(*) desc')
            ->get();

        $cityCounts = (clone $scope)
            ->selectRaw("concat(city, ' - ', upper(state)) as city_label, count(*) as total")
            ->whereNotNull('city')
            ->where('city', '<>', '')
            ->whereNotNull('state')
            ->where('state', '<>', '')
            ->groupBy('city_label')
            ->orderByRaw('count(*) desc')
            ->limit(20)
            ->get();

        return [
            'types' => [
                'labels' => $types->pluck('name')->values(),
                'data' => $types->map(fn (VenueType $type) => (int) (clone $scope)->where('venue_type_id', $type->id)->count())->values(),
            ],
            'categories' => [
                'labels' => $categories->pluck('name')->values(),
                'data' => $categories->map(fn (VenueCategory $category) => (int) (clone $scope)->where('venue_category_id', $category->id)->count())->values(),
            ],
            'styles' => [
                'labels' => $styles->pluck('name')->values(),
                'data' => $styles->map(fn (VenueStyle $style) => (int) (clone $scope)->whereHas('styles', fn ($styleQuery) => $styleQuery->where('venue_styles.id', $style->id))->count())->values(),
            ],
            'states' => [
                'labels' => $stateCounts->pluck('state')->values(),
                'data' => $stateCounts->pluck('total')->map(fn ($count) => (int) $count)->values(),
            ],
            'cities' => [
                'labels' => $cityCounts->pluck('city_label')->values(),
                'data' => $cityCounts->pluck('total')->map(fn ($count) => (int) $count)->values(),
            ],
        ];
    }

    private function applyVenueFilters(Builder $query): Builder
    {
        return $query
            ->when(request('venue_type_id'), fn ($q, $typeId) => $q->where('venue_type_id', $typeId))
            ->when(request('venue_category_id'), fn ($q, $categoryId) => $q->where('venue_category_id', $categoryId))
            ->when(request('venue_style_id'), fn ($q, $styleId) => $q->whereHas('styles', fn ($styleQuery) => $styleQuery->where('venue_styles.id', $styleId)))
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('city'), fn ($q, $city) => $q->where('city', 'ilike', "%{$city}%"))
            ->when(request()->has('has_performed') && request('has_performed') !== null, function ($q) {
                $hasPerformed = filter_var(request('has_performed'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if ($hasPerformed === true) {
                    $q->where('performances_count', '>', 0);
                }
                if ($hasPerformed === false) {
                    $q->where('performances_count', 0);
                }
            })
            ->when(request('rating'), fn ($q, $rating) => $q->where('rating', (int) $rating))
            ->when(request('search'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('address_line', 'ilike', "%{$search}%")
                        ->orWhere('address_number', 'ilike', "%{$search}%")
                        ->orWhere('neighborhood', 'ilike', "%{$search}%")
                        ->orWhere('city', 'ilike', "%{$search}%")
                        ->orWhere('state', 'ilike', "%{$search}%")
                        ->orWhere('contact_name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('whatsapp', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%");
                });
            });
    }

    public function create(): Response
    {
        return Inertia::render('Venues/Create', [
            'sizes' => collect(),
            'types' => VenueType::orderBy('name')->get(['id', 'name', 'icon']),
            'categories' => VenueCategory::orderBy('name')->get(['id', 'name', 'icon']),
            'styles' => VenueStyle::where('domain', VenueStyle::DOMAIN_VENUES)->orderBy('name')->get(['id', 'name', 'icon']),
        ]);
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $styleIds = $this->extractStyleIds($payload);

        unset($payload['venue_style_id'], $payload['venue_style_ids']);
        $payload['equipment_tags'] = array_values(array_filter($payload['equipment_tags'] ?? [], fn ($tag) => filled($tag)));

        $venue = Venue::create([
            ...$payload,
            'user_id' => (string) Auth::id(),
        ]);

        $venue->styles()->sync($styleIds);

        $this->syncContactFromVenue($venue);

        return redirect()->route('venues.index')->with('success', 'Local criado com sucesso.');
    }

    public function quickStore(StoreVenueRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $styleIds = $this->extractStyleIds($payload);

        unset($payload['venue_style_id'], $payload['venue_style_ids']);
        $payload['equipment_tags'] = array_values(array_filter($payload['equipment_tags'] ?? [], fn ($tag) => filled($tag)));

        $venue = Venue::create([
            ...$payload,
            'user_id' => (string) Auth::id(),
        ]);

        $venue->styles()->sync($styleIds);
        $venue->load(['type:id,name,icon,color', 'category:id,name', 'styles:id,name']);

        $this->syncContactFromVenue($venue);

        return response()->json([
            'venue' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'type' => $venue->type,
                'category' => $venue->category,
                'style' => $venue->styles->first(),
                'styles' => $venue->styles,
                'latitude' => $venue->latitude,
                'longitude' => $venue->longitude,
                'address' => collect([
                    trim(implode(', ', array_filter([$venue->address_line, $venue->address_number]))),
                    $venue->neighborhood,
                    trim(implode(' - ', array_filter([$venue->city, $venue->state]))),
                    $venue->postal_code,
                    $venue->country,
                ])->filter()->implode(', '),
            ],
        ], 201);
    }

    public function show(Venue $venue): Response
    {
        return Inertia::render('Venues/Show', [
            'venue' => $venue->load(['user', 'type', 'category', 'styles']),
        ]);
    }

    public function edit(Venue $venue): Response
    {
        $venue->load('styles:id');
        $venue->setAttribute('venue_style_ids', $venue->styles->pluck('id')->values());

        return Inertia::render('Venues/Edit', [
            'venue' => $venue,
            'sizes' => collect(),
            'types' => VenueType::orderBy('name')->get(['id', 'name', 'icon']),
            'categories' => VenueCategory::orderBy('name')->get(['id', 'name', 'icon']),
            'styles' => VenueStyle::where('domain', VenueStyle::DOMAIN_VENUES)->orderBy('name')->get(['id', 'name', 'icon']),
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $payload = $request->validated();
        $styleIds = $this->extractStyleIds($payload);

        unset($payload['venue_style_id'], $payload['venue_style_ids']);
        $payload['equipment_tags'] = array_values(array_filter($payload['equipment_tags'] ?? [], fn ($tag) => filled($tag)));

        $venue->update($payload);
        $venue->styles()->sync($styleIds);
        $venue->refresh();

        $this->syncContactFromVenue($venue);

        return redirect()->route('venues.index')->with('success', 'Local atualizado com sucesso.');
    }

    private function syncContactFromVenue(Venue $venue): void
    {
        $existingContact = Contact::query()->where('venue_id', $venue->id)->first();

        $hasReachabilityData = filled($venue->phone) || filled($venue->email);

        if (! $existingContact && ! $hasReachabilityData) {
            return;
        }

        $contactName = filled($venue->contact_name)
            ? sprintf('%s (%s)', trim((string) $venue->contact_name), $venue->name)
            : $venue->name;

        if ($existingContact) {
            $existingContact->update([
                'name' => $contactName,
                'email' => $venue->email,
                'phone' => $venue->phone,
            ]);

            return;
        }

        Contact::create([
            'user_id' => $venue->user_id,
            'venue_id' => $venue->id,
            'name' => $contactName,
            'email' => $venue->email,
            'phone' => $venue->phone,
        ]);
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $venue->delete();

        return redirect()->route('venues.index')->with('success', 'Local removido com sucesso.');
    }

    private function extractStyleIds(array $payload): array
    {
        $legacyStyleId = $payload['venue_style_id'] ?? null;
        $styleIds = $payload['venue_style_ids'] ?? [];

        if (filled($legacyStyleId)) {
            $styleIds[] = $legacyStyleId;
        }

        return collect($styleIds)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }
}
