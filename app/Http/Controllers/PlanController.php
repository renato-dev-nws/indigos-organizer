<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
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
                ...$request->safe()->except('phases'),
                'user_id' => (string) Auth::id(),
            ]);

            foreach ($request->input('phases', []) as $index => $phase) {
                $plan->phases()->create([
                    'user_id' => (string) Auth::id(),
                    'title' => $phase['title'],
                    'description' => $phase['description'] ?? null,
                    'order' => $phase['order'] ?? ($index + 1),
                ]);
            }
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
            $plan->update($request->safe()->except('phases'));

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
                ];

                if (! empty($phase['id'])) {
                    $plan->phases()->where('id', $phase['id'])->update($payload);
                } else {
                    $plan->phases()->create($payload);
                }
            }
        });

        return redirect()->route('plans.index')->with('success', 'Plano atualizado com sucesso.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('plans.index')->with('success', 'Plano removido com sucesso.');
    }
}
