<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\Idea;
use App\Models\Plan;
use App\Models\PlanPhase;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::query()
            ->with([
                'user',
                'phases' => fn ($query) => $query
                    ->select(['id', 'plan_id'])
                    ->withCount('tasks'),
            ])
            ->withCount([
                'tasks as direct_tasks_count',
                'contents as related_contents_count',
            ])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('search'), fn ($q, $search) => $q->where('title', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Plans/Index', [
            'plans' => $plans,
            'filters' => request()->only(['status', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Plans/Create');
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $phases = collect($request->input('phases', []));
            $planPayload = $this->buildPlanPayload(
                $request->safe()->except(['phases', 'progress']),
                $phases,
            );

            $plan = Plan::create([
                ...$planPayload,
                'user_id' => (string) Auth::id(),
                'progress' => 0,
            ]);

            foreach ($phases as $index => $phase) {
                $plan->phases()->create([
                    'user_id' => (string) Auth::id(),
                    'title' => $phase['title'],
                    'description' => $phase['description'] ?? null,
                    'order' => $index + 1,
                    'completed' => (bool) ($phase['completed'] ?? false),
                    'estimated_start_date' => $this->normalizeDate($phase['estimated_start_date'] ?? null),
                    'estimated_end_date' => $this->normalizeDate($phase['estimated_end_date'] ?? null),
                ]);
            }

            $plan->refreshProgress();
        });

        return redirect()->route('plans.index')->with('success', 'Plano criado com sucesso.');
    }

    public function show(Plan $plan): Response
    {
        return Inertia::render('Plans/Show', [
            'plan' => $plan->load([
                'phases.tasks.status',
                'phases.tasks.assignedUsers',
                'phases.tasks.subtasks',
                'tasks.status',
                'tasks.assignedUsers',
                'tasks.subtasks',
                'contents:id,plan_id,title,status,planned_publish_at,published_at,idea_type_id',
                'contents.type:id,name',
                'contents.categories:id,name,icon',
                'user',
            ]),
        ]);
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('Plans/Edit', [
            'plan' => $plan->load([
                'phases' => fn ($query) => $query
                    ->withCount('tasks')
                    ->orderBy('order'),
            ]),
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        DB::transaction(function () use ($request, $plan): void {
            $incoming = collect($request->input('phases', []));
            $incomingIds = $incoming->pluck('id')->filter()->values();
            $phasesToDelete = $plan->phases()
                ->when($incomingIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $incomingIds))
                ->when($incomingIds->isEmpty(), fn ($query) => $query)
                ->get();

            $blockedDeletion = $phasesToDelete->first(fn (PlanPhase $phase) => $phase->tasks()->exists());
            if ($blockedDeletion) {
                throw ValidationException::withMessages([
                    'phases' => 'Nao e possivel excluir fase com tarefas relacionadas! Remova o relacionamento de tarefas para excluir uma fase do planejamento.',
                ]);
            }

            $planPayload = $this->buildPlanPayload(
                $request->safe()->except(['phases', 'progress']),
                $incoming,
            );
            $plan->update($planPayload);

            if ($phasesToDelete->isNotEmpty()) {
                Idea::query()
                    ->whereIn('plan_phase_id', $phasesToDelete->pluck('id'))
                    ->update(['plan_phase_id' => null]);

                $plan->phases()->whereIn('id', $phasesToDelete->pluck('id'))->delete();
            }

            foreach ($incoming as $index => $phase) {
                $payload = [
                    'user_id' => (string) Auth::id(),
                    'title' => $phase['title'],
                    'description' => $phase['description'] ?? null,
                    'order' => $index + 1,
                    'completed' => (bool) ($phase['completed'] ?? false),
                    'estimated_start_date' => $this->normalizeDate($phase['estimated_start_date'] ?? null),
                    'estimated_end_date' => $this->normalizeDate($phase['estimated_end_date'] ?? null),
                ];

                if (! empty($phase['id'])) {
                    $plan->phases()->where('id', $phase['id'])->update($payload);
                } else {
                    $plan->phases()->create($payload);
                }
            }

            $plan->refreshProgress();
        });

        return redirect()->route('plans.index')->with('success', 'Plano atualizado com sucesso.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('plans.index')->with('success', 'Plano removido com sucesso.');
    }

    public function updatePhaseCompletion(Request $request, Plan $plan, PlanPhase $phase): RedirectResponse
    {
        abort_unless((string) $phase->plan_id === (string) $plan->id, 404);

        $payload = $request->validate([
            'completed' => ['required', 'boolean'],
        ]);

        $phase->update([
            'completed' => (bool) $payload['completed'],
        ]);

        $plan->refreshProgress();

        return back()->with('success', 'Fase atualizada com sucesso.');
    }

    private function buildPlanPayload(array $payload, Collection $phases): array
    {
        $payload['start_date'] = $this->normalizeDate($payload['start_date'] ?? null);
        $payload['end_date'] = $this->normalizeDate($payload['end_date'] ?? null);

        $firstPhaseStart = $phases
            ->pluck('estimated_start_date')
            ->filter(fn ($value) => filled($value))
            ->first();

        $lastPhaseEnd = $phases
            ->pluck('estimated_end_date')
            ->filter(fn ($value) => filled($value))
            ->last();

        if (filled($firstPhaseStart)) {
            $payload['start_date'] = $firstPhaseStart;
        }

        if (filled($lastPhaseEnd)) {
            $payload['end_date'] = $lastPhaseEnd;
        }

        return $payload;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return date('Y-m-d', strtotime((string) $value));
    }
}
