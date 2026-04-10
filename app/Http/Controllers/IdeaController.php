<?php

namespace App\Http\Controllers;

use App\Actions\ExecuteIdeaAction;
use App\Http\Requests\StoreIdeaRequest;
use App\Http\Requests\UpdateIdeaRequest;
use App\Models\Idea;
use App\Models\IdeaCategory;
use App\Models\IdeaType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class IdeaController extends Controller
{
    public function index(): Response
    {
        $ideas = Idea::query()
            ->with(['type', 'category', 'user'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('idea_type_id'), fn ($q, $typeId) => $q->where('idea_type_id', $typeId))
            ->when(request('idea_category_id'), fn ($q, $categoryId) => $q->where('idea_category_id', $categoryId))
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Ideas/Index', [
            'ideas' => $ideas,
            'filters' => request()->only(['status', 'idea_type_id', 'idea_category_id', 'search']),
            'ideaTypes' => IdeaType::query()->orderBy('name')->get(),
            'ideaCategories' => IdeaCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Ideas/Create', [
            'ideaTypes' => IdeaType::query()->orderBy('name')->get(),
            'ideaCategories' => IdeaCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreIdeaRequest $request): RedirectResponse
    {
        $idea = Idea::create([
            ...$request->safe()->except('references'),
            'user_id' => (string) Auth::id(),
        ]);

        foreach ($request->input('references', []) as $reference) {
            $idea->references()->create($reference);
        }

        return redirect()->route('ideas.index')->with('success', 'Ideia criada com sucesso.');
    }

    public function show(Idea $idea): Response
    {
        return Inertia::render('Ideas/Show', [
            'idea' => $idea->load(['type', 'category', 'references', 'user']),
        ]);
    }

    public function edit(Idea $idea): Response
    {
        return Inertia::render('Ideas/Edit', [
            'idea' => $idea->load('references'),
            'ideaTypes' => IdeaType::query()->orderBy('name')->get(),
            'ideaCategories' => IdeaCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateIdeaRequest $request, Idea $idea): RedirectResponse
    {
        $idea->update($request->safe()->except('references'));
        $idea->references()->delete();

        foreach ($request->input('references', []) as $reference) {
            $idea->references()->create($reference);
        }

        return redirect()->route('ideas.index')->with('success', 'Ideia atualizada com sucesso.');
    }

    public function destroy(Idea $idea): RedirectResponse
    {
        $idea->delete();

        return redirect()->route('ideas.index')->with('success', 'Ideia removida com sucesso.');
    }

    public function execute(Idea $idea, ExecuteIdeaAction $executeIdeaAction): RedirectResponse
    {
        abort_if(in_array($idea->status, ['cancelled', 'executed'], true), 422, 'Esta ideia nao pode ser executada.');

        $executeIdeaAction->handle($idea);

        return redirect()->route('contents.index')->with('success', 'Ideia convertida em conteudo com sucesso.');
    }
}
