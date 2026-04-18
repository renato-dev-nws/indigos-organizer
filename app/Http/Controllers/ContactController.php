<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        $contacts = Contact::query()
            ->with('venue:id,name')
            ->when(request('name'), fn ($query, $name) => $query->where('name', 'ilike', "%{$name}%"))
            ->when(request('search'), fn ($query, $search) => $query
                ->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%")
                        ->orWhere('whatsapp', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%")
                        ->orWhereHas('venue', fn ($venueQuery) => $venueQuery->where('name', 'ilike', "%{$search}%"));
                }))
            ->when(request('venue_id'), fn ($query, $venueId) => $query->where('venue_id', $venueId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'venues' => Venue::query()->orderBy('name')->get(['id', 'name']),
            'filters' => request()->only(['name', 'search', 'venue_id']),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        if (! empty($payload['venue_id'])) {
            $existingForVenue = Contact::query()->where('venue_id', $payload['venue_id'])->first();

            if ($existingForVenue) {
                $existingForVenue->update($payload);

                return redirect()->route('contacts.index')->with('success', 'Contato atualizado com sucesso.');
            }
        }

        Contact::create([
            ...$payload,
            'user_id' => (string) Auth::id(),
        ]);

        return redirect()->route('contacts.index')->with('success', 'Contato criado com sucesso.');
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->validated());

        return redirect()->route('contacts.index')->with('success', 'Contato atualizado com sucesso.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Contato removido com sucesso.');
    }
}
