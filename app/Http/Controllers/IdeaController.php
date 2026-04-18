<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIdeaRequest;
use App\Http\Requests\UpdateIdeaRequest;
use App\Jobs\DispatchIdeaVotedNotificationJob;
use App\Models\Content;
use App\Models\Idea;
use App\Models\IdeaCategory;
use App\Models\IdeaVote;
use App\Models\IdeaType;
use App\Models\Plan;
use App\Models\User;
use App\Models\VenueStyle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class IdeaController extends Controller
{
    public function index(): Response
    {
        $baseQuery = $this->applyIdeaFilters(Idea::query())
            ->with(['type', 'category', 'styles', 'user', 'content', 'plan', 'planPhase'])
            ->orderByDesc('updated_at');

        $ideas = (clone $baseQuery)
            ->paginate(15)
            ->withQueryString();

        $ideaBoardItems = (clone $baseQuery)
            ->get();

        return Inertia::render('Ideas/Index', [
            'ideas' => $ideas,
            'ideaBoardItems' => $ideaBoardItems,
            'filters' => request()->only(['status', 'idea_type_id', 'idea_category_id', 'venue_style_id', 'search', 'mine']),
            'ideaTypes' => IdeaType::query()->orderBy('name')->get(),
            'ideaCategories' => IdeaCategory::query()->orderBy('name')->get(),
            'venueStyles' => VenueStyle::query()->where('domain', VenueStyle::DOMAIN_CONTENT)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
        ]);
    }

    private function applyIdeaFilters($query)
    {
        return $query
            ->where(function ($q) {
                $q->where('is_private', false)
                    ->orWhere('user_id', Auth::id());
            })
            ->when(request()->boolean('mine'), fn ($q) => $q->where('user_id', Auth::id()))
            ->when(
                request('status'),
                fn ($q, $status) => $q->where('status', $status),
                fn ($q) => $q->where('status', '!=', 'trash')
            )
            ->when(request('idea_type_id'), fn ($q, $typeId) => $q->where('idea_type_id', $typeId))
            ->when(request('idea_category_id'), fn ($q, $categoryId) => $q->where('idea_category_id', $categoryId))
            ->when(request('venue_style_id'), fn ($q, $styleId) => $q->whereHas('styles', fn ($q2) => $q2->where('venue_styles.id', $styleId)))
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"));
    }

    public function create(): Response
    {
        return Inertia::render('Ideas/Create', [
            'ideaTypes' => IdeaType::query()->orderBy('name')->get(),
            'ideaCategories' => IdeaCategory::query()->orderBy('name')->get(),
            'venueStyles' => VenueStyle::query()->where('domain', VenueStyle::DOMAIN_CONTENT)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'plans' => Plan::query()->whereIn('status', ['queued', 'running'])->with('phases')->orderBy('title')->get(),
            'contents' => Content::query()->whereIn('status', ['queued', 'in_production', 'finalized'])->orderBy('title')->get(['id', 'title']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreIdeaRequest $request): RedirectResponse
    {
        $payload = $request->safe()->except(['references', 'voter_users', 'collaborator_ids', 'venue_style_ids']);
        if (($payload['related_type'] ?? null) !== 'existing_content') {
            $payload['content_id'] = null;
        }
        if (($payload['related_type'] ?? null) !== 'existing_plan') {
            $payload['plan_id'] = null;
            $payload['plan_phase_id'] = null;
        }

        $idea = Idea::create([
            ...$payload,
            'user_id' => (string) Auth::id(),
        ]);

        foreach ($request->input('references', []) as $reference) {
            $idea->references()->create($reference);
        }

        $idea->voterUsers()->sync($request->input('voter_users', []));
        $idea->styles()->sync($request->input('venue_style_ids', []));
        $idea->collaborators()->sync($idea->is_private ? [] : $request->input('collaborator_ids', []));

        return redirect()->route('ideas.index')->with('success', 'Ideia criada com sucesso.');
    }

    public function show(Idea $idea): Response
    {
        $this->authorize('view', $idea);

        $idea->load(['type', 'category', 'styles', 'references', 'user', 'content', 'plan', 'planPhase', 'votes.user', 'voterUsers']);
        $userVote = $idea->votes()->where('user_id', Auth::id())->first();

        return Inertia::render('Ideas/Show', [
            'idea' => $idea,
            'userVote' => $userVote,
        ]);
    }

    public function edit(Idea $idea): Response
    {
        $this->authorize('update', $idea);

        return Inertia::render('Ideas/Edit', [
            'idea' => $idea->load(['references', 'voterUsers', 'styles', 'collaborators']),
            'ideaTypes' => IdeaType::query()->orderBy('name')->get(),
            'ideaCategories' => IdeaCategory::query()->orderBy('name')->get(),
            'venueStyles' => VenueStyle::query()->where('domain', VenueStyle::DOMAIN_CONTENT)->orderBy('name')->get(['id', 'name', 'color', 'icon']),
            'plans' => Plan::query()->whereIn('status', ['queued', 'running'])->with('phases')->orderBy('title')->get(),
            'contents' => Content::query()->whereIn('status', ['queued', 'in_production', 'finalized'])->orderBy('title')->get(['id', 'title']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'voterUsers' => $idea->voterUsers()->pluck('users.id')->all(),
            'collaboratorUsers' => $idea->collaborators()->pluck('users.id')->all(),
            'venueStyleIds' => $idea->styles()->pluck('venue_styles.id')->all(),
        ]);
    }

    public function update(UpdateIdeaRequest $request, Idea $idea): RedirectResponse
    {
        $this->authorize('update', $idea);

        $payload = $request->safe()->except(['references', 'voter_users', 'collaborator_ids', 'venue_style_ids']);
        if (($payload['related_type'] ?? null) !== 'existing_content') {
            $payload['content_id'] = null;
        }
        if (($payload['related_type'] ?? null) !== 'existing_plan') {
            $payload['plan_id'] = null;
            $payload['plan_phase_id'] = null;
        }
        if (! in_array(($payload['status'] ?? null), ['in_drawer', 'trash'], true)) {
            $payload['is_private'] = false;
        }

        $idea->update($payload);
        $idea->references()->delete();
        $idea->voterUsers()->sync($request->input('voter_users', []));
        $idea->styles()->sync($request->input('venue_style_ids', []));
        $idea->collaborators()->sync($idea->is_private ? [] : $request->input('collaborator_ids', []));

        foreach ($request->input('references', []) as $reference) {
            $idea->references()->create($reference);
        }

        return redirect()->route('ideas.index')->with('success', 'Ideia atualizada com sucesso.');
    }

    public function destroy(Idea $idea): RedirectResponse
    {
        $this->authorize('delete', $idea);

        $idea->delete();

        return redirect()->route('ideas.index')->with('success', 'Ideia removida com sucesso.');
    }

    public function vote(Request $request, Idea $idea): RedirectResponse
    {
        $request->validate([
            'vote' => ['required', 'in:on_table,in_drawer,trash'],
        ]);

        abort_if($idea->status !== 'on_board', 422, 'Somente ideias no quadro podem receber voto.');

        $eligible = ! $idea->voterUsers()->exists()
            || $idea->voterUsers()->where('user_id', Auth::id())->exists();
        abort_if(! $eligible, 403, 'Você não pode votar nesta ideia.');

        IdeaVote::updateOrCreate(
            ['idea_id' => $idea->id, 'user_id' => (string) Auth::id()],
            ['vote' => $request->string('vote')->toString()],
        );

        DispatchIdeaVotedNotificationJob::dispatchSync($idea->id, (string) Auth::id());

        return back()->with('success', 'Voto registrado.');
    }

    public function updateStatus(Request $request, Idea $idea): RedirectResponse
    {
        $this->authorize('update', $idea);

        $payload = $request->validate([
            'status' => ['required', 'in:in_drawer,on_table,on_board,executing,executed'],
        ]);

        $idea->update(['status' => $payload['status']]);

        return back()->with('success', 'Status da ideia atualizado.');
    }
}
