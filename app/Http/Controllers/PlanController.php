<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
use App\Models\PlanPhase;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::query()
            ->with(['user', 'phases'])
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
            $plan = Plan::create([
                ...$request->safe()->except(['phases', 'progress']),
                'user_id' => (string) Auth::id(),
                'progress' => 0,
            ]);

            foreach ($request->input('phases', []) as $index => $phase) {
                $plan->phases()->create([
                    'user_id' => (string) Auth::id(),
                    'title' => $phase['title'],
                    'description' => $phase['description'] ?? null,
                    'order' => $phase['order'] ?? ($index + 1),
                    'completed' => (bool) ($phase['completed'] ?? false),
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
                'phases.tasks.assignedUser',
                'phases.tasks.subtasks',
                'tasks.status',
                'tasks.assignedUser',
                'tasks.subtasks',
                'contents:id,plan_id,title,status,planned_publish_at,published_at',
                'user',
            ]),
        ]);
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('Plans/Edit', [
            'plan' => $plan->load('phases'),
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        DB::transaction(function () use ($request, $plan): void {
            $plan->update($request->safe()->except(['phases', 'progress']));

            $incoming = collect($request->input('phases', []));
            $incomingIds = $incoming->pluck('id')->filter()->values();

            $plan->phases()->when($incomingIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $incomingIds))->delete();
            if ($incomingIds->isEmpty()) {
                $plan->phases()->delete();
            }

            foreach ($incoming as $index => $phase) {
                $payload = [
                    'user_id' => (string) Auth::id(),
                    'title' => $phase['title'],
                    'description' => $phase['description'] ?? null,
                    'order' => $phase['order'] ?? ($index + 1),
                    'completed' => (bool) ($phase['completed'] ?? false),
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
}
