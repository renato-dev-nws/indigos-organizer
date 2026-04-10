<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use App\Models\Venue;
use App\Models\VenueSize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function index(): Response
    {
        $venues = Venue::query()
            ->with(['size', 'user'])
            ->when(request('venue_size_id'), fn ($q, $sizeId) => $q->where('venue_size_id', $sizeId))
            ->when(request('search'), fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Venues/Index', [
            'venues' => $venues,
            'sizes' => VenueSize::orderBy('name')->get(),
            'filters' => request()->only(['venue_size_id', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Venues/Create', [
            'sizes' => VenueSize::orderBy('name')->get(),
        ]);
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        Venue::create([
            ...$request->validated(),
            'user_id' => (string) Auth::id(),
        ]);

        return redirect()->route('venues.index')->with('success', 'Casa de show criada com sucesso.');
    }

    public function show(Venue $venue): Response
    {
        return Inertia::render('Venues/Show', [
            'venue' => $venue->load(['size', 'user']),
        ]);
    }

    public function edit(Venue $venue): Response
    {
        return Inertia::render('Venues/Edit', [
            'venue' => $venue,
            'sizes' => VenueSize::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $venue->update($request->validated());

        return redirect()->route('venues.index')->with('success', 'Casa de show atualizada com sucesso.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $venue->delete();

        return redirect()->route('venues.index')->with('success', 'Casa de show removida com sucesso.');
    }
}
