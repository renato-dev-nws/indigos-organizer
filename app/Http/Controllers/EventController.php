<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::query()
            ->with(['type', 'venue.type', 'user'])
            ->when(request('event_type_id'), fn ($q, $typeId) => $q->where('event_type_id', $typeId))
            ->when(request('attendance_mode'), fn ($q, $mode) => $q->where('attendance_mode', $mode))
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"))
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Events/Index', [
            'events' => $events,
            'types' => EventType::query()->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'filters' => request()->only(['event_type_id', 'attendance_mode', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Events/Create', [
            'types' => EventType::query()->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'venues' => $this->venueOptions(),
            'venueTypes' => \App\Models\VenueType::query()->orderBy('name')->get(['id', 'name', 'icon']),
            'venueCategories' => \App\Models\VenueCategory::query()->orderBy('name')->get(['id', 'name', 'icon']),
            'venueStyles' => \App\Models\VenueStyle::query()->where('domain', \App\Models\VenueStyle::DOMAIN_VENUES)->orderBy('name')->get(['id', 'name', 'icon']),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $event = Event::create([
                ...$request->safe()->except(['extra_infos', 'links']),
                'user_id' => (string) Auth::id(),
            ]);

            foreach ($request->input('extra_infos', []) as $index => $extraInfo) {
                $event->extraInfos()->create([
                    'title' => $extraInfo['title'],
                    'information' => $extraInfo['information'],
                    'order' => $index + 1,
                ]);
            }

            foreach ($request->input('links', []) as $link) {
                $event->links()->create($link);
            }
        });

        return redirect()->route('events.index')->with('success', 'Evento criado com sucesso.');
    }

    public function show(Event $event): Response
    {
        return Inertia::render('Events/Show', [
            'event' => $event->load([
                'type',
                'venue.type',
                'venue.category',
                'venue.style',
                'extraInfos',
                'links',
                'tasks.status',
                'tasks.assignedUser',
                'tasks.subtasks',
                'user',
            ]),
        ]);
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Events/Edit', [
            'event' => $event->load(['extraInfos', 'links', 'venue.type', 'venue.category', 'venue.style']),
            'types' => EventType::query()->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'venues' => $this->venueOptions(),
            'venueTypes' => \App\Models\VenueType::query()->orderBy('name')->get(['id', 'name', 'icon']),
            'venueCategories' => \App\Models\VenueCategory::query()->orderBy('name')->get(['id', 'name', 'icon']),
            'venueStyles' => \App\Models\VenueStyle::query()->where('domain', \App\Models\VenueStyle::DOMAIN_VENUES)->orderBy('name')->get(['id', 'name', 'icon']),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        DB::transaction(function () use ($request, $event): void {
            $event->update($request->safe()->except(['extra_infos', 'links']));

            $event->extraInfos()->delete();
            foreach ($request->input('extra_infos', []) as $index => $extraInfo) {
                $event->extraInfos()->create([
                    'title' => $extraInfo['title'],
                    'information' => $extraInfo['information'],
                    'order' => $index + 1,
                ]);
            }

            $event->links()->delete();
            foreach ($request->input('links', []) as $link) {
                $event->links()->create($link);
            }
        });

        return redirect()->route('events.index')->with('success', 'Evento atualizado com sucesso.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('events.index')->with('success', 'Evento removido com sucesso.');
    }

    private function venueOptions()
    {
        return Venue::query()
            ->with(['type:id,name,icon,color', 'category:id,name', 'style:id,name'])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'venue_type_id',
                'venue_category_id',
                'venue_style_id',
                'address_line',
                'address_number',
                'neighborhood',
                'city',
                'state',
                'postal_code',
                'country',
                'latitude',
                'longitude',
            ])
            ->map(fn (Venue $venue) => [
                'id' => $venue->id,
                'name' => $venue->name,
                'type' => $venue->type,
                'category' => $venue->category,
                'style' => $venue->style,
                'latitude' => $venue->latitude,
                'longitude' => $venue->longitude,
                'address' => collect([
                    trim(implode(', ', array_filter([$venue->address_line, $venue->address_number]))),
                    $venue->neighborhood,
                    trim(implode(' - ', array_filter([$venue->city, $venue->state]))),
                    $venue->postal_code,
                    $venue->country,
                ])->filter()->implode(', '),
            ])
            ->values();
    }
}