<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use App\Models\Venue;
use App\Models\VenueCategory;
use App\Models\VenueSize;
use App\Models\VenueStyle;
use App\Models\VenueType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function index(): Response
    {
        $venues = Venue::query()
            ->with(['size', 'user', 'type', 'category', 'style'])
            ->when(request('venue_type_id'), fn ($q, $typeId) => $q->where('venue_type_id', $typeId))
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
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $mapPoints = Venue::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('type:id,name,color')
            ->get(['id', 'name', 'latitude', 'longitude', 'venue_type_id'])
            ->map(fn ($venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'lat' => (float) $venue->latitude,
                'lng' => (float) $venue->longitude,
                'type' => $venue->type,
            ]);

        return Inertia::render('Venues/Index', [
            'venues' => $venues,
            'sizes' => VenueSize::orderBy('name')->get(),
            'types' => VenueType::orderBy('name')->get(['id', 'name', 'color']),
            'categories' => VenueCategory::orderBy('name')->get(['id', 'name', 'color']),
            'styles' => VenueStyle::orderBy('name')->get(['id', 'name', 'color']),
            'mapPoints' => $mapPoints,
            'filters' => request()->only(['venue_type_id', 'status', 'city', 'has_performed', 'rating', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Venues/Create', [
            'sizes' => VenueSize::orderBy('name')->get(),
            'types' => VenueType::orderBy('name')->get(['id', 'name']),
            'categories' => VenueCategory::orderBy('name')->get(['id', 'name']),
            'styles' => VenueStyle::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        Venue::create([
            ...$request->validated(),
            'user_id' => (string) Auth::id(),
        ]);

        return redirect()->route('venues.index')->with('success', 'Local criado com sucesso.');
    }

    public function show(Venue $venue): Response
    {
        return Inertia::render('Venues/Show', [
            'venue' => $venue->load(['size', 'user', 'type', 'category', 'style']),
        ]);
    }

    public function edit(Venue $venue): Response
    {
        return Inertia::render('Venues/Edit', [
            'venue' => $venue,
            'sizes' => VenueSize::orderBy('name')->get(),
            'types' => VenueType::orderBy('name')->get(['id', 'name']),
            'categories' => VenueCategory::orderBy('name')->get(['id', 'name']),
            'styles' => VenueStyle::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $venue->update($request->validated());

        return redirect()->route('venues.index')->with('success', 'Local atualizado com sucesso.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $venue->delete();

        return redirect()->route('venues.index')->with('success', 'Local removido com sucesso.');
    }
}
