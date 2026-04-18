<?php

namespace Tests\Feature\Modules;

use App\Models\Idea;
use App\Models\Plan;
use App\Models\PlanPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanPhaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_update_unlinks_idea_from_removed_phase_without_tasks(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $plan = Plan::create([
            'user_id' => $user->id,
            'title' => 'Plano para teste',
            'status' => 'queued',
            'progress' => 0,
        ]);

        $phaseToKeep = PlanPhase::create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'title' => 'Fase mantida',
            'order' => 1,
            'completed' => false,
        ]);

        $phaseToDelete = PlanPhase::create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'title' => 'Fase removida',
            'order' => 2,
            'completed' => false,
        ]);

        $idea = Idea::create([
            'user_id' => $user->id,
            'title' => 'Ideia vinculada a fase',
            'description' => 'teste',
            'status' => 'on_table',
            'related_type' => 'existing_plan',
            'plan_id' => $plan->id,
            'plan_phase_id' => $phaseToDelete->id,
            'is_private' => false,
        ]);

        $this->actingAs($user)
            ->put(route('plans.update', $plan), [
                'title' => $plan->title,
                'description' => $plan->description,
                'start_date' => null,
                'end_date' => null,
                'status' => $plan->status,
                'phases' => [
                    [
                        'id' => $phaseToKeep->id,
                        'title' => $phaseToKeep->title,
                        'description' => $phaseToKeep->description,
                        'completed' => false,
                        'estimated_start_date' => null,
                        'estimated_end_date' => null,
                    ],
                ],
            ])
            ->assertRedirect(route('plans.index'));

        $this->assertSoftDeleted('plan_phases', ['id' => $phaseToDelete->id]);

        $idea->refresh();
        $this->assertNull($idea->plan_phase_id);
        $this->assertSame($plan->id, $idea->plan_id);
    }
}
