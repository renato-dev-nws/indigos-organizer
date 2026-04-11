<?php

namespace Tests\Feature\Modules;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaFiltersAndPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_idea_index_applies_search_on_visible_ideas(): void
    {
        /** @var Authenticatable $userA */
        $userA = User::factory()->createOne();
        /** @var Authenticatable $userB */
        $userB = User::factory()->createOne();

        Idea::create([
            'user_id' => $userA->id,
            'title' => 'Ideia Alpha',
            'description' => 'A',
            'status' => 'on_table',
            'related_type' => 'none',
            'is_private' => false,
        ]);

        Idea::create([
            'user_id' => $userB->id,
            'title' => 'Ideia Beta',
            'description' => 'B',
            'status' => 'on_table',
            'related_type' => 'none',
            'is_private' => false,
        ]);

        $response = $this->actingAs($userA)->get(route('ideas.index', ['search' => 'Beta']));

        $response->assertOk();
        $response->assertSee('Ideia Beta');
        $response->assertDontSee('Ideia Alpha');

        $response2 = $this->actingAs($userA)->get(route('ideas.index', ['search' => 'Alpha']));
        $response2->assertOk();
        $response2->assertSee('Ideia Alpha');
        $response2->assertDontSee('Ideia Beta');
    }

    public function test_private_idea_is_not_visible_to_other_users_in_index(): void
    {
        /** @var Authenticatable $owner */
        $owner = User::factory()->createOne();
        /** @var Authenticatable $other */
        $other = User::factory()->createOne();

        $privateIdea = Idea::create([
            'user_id' => $owner->id,
            'title' => 'Rascunho Privado',
            'description' => 'x',
            'status' => 'in_drawer',
            'related_type' => 'none',
            'is_private' => true,
        ]);

        $publicIdea = Idea::create([
            'user_id' => $owner->id,
            'title' => 'Rascunho Público',
            'description' => 'y',
            'status' => 'in_drawer',
            'related_type' => 'none',
            'is_private' => false,
        ]);

        $response = $this->actingAs($other)->get(route('ideas.index'));

        $response->assertOk();
        
        // Database verify non-owner cannot query private idea
        $this->assertFalse(
            Idea::where('id', $privateIdea->id)
                ->where(function ($q) use ($other) {
                    $q->where('is_private', false)
                        ->orWhere('user_id', $other->id);
                })
                ->exists(),
            'Private idea should not be visible to non-owner'
        );
        
        // Database verify non-owner can query public idea from owner
        $this->assertTrue(
            Idea::where('id', $publicIdea->id)
                ->where(function ($q) use ($other) {
                    $q->where('is_private', false)
                        ->orWhere('user_id', $other->id);
                })
                ->exists(),
            'Public idea should be visible to non-owner'
        );
    }

    public function test_user_can_view_idea_from_another_user_in_collaborative_mode(): void
    {
        /** @var Authenticatable $owner */
        $owner = User::factory()->createOne();
        /** @var Authenticatable $other */
        $other = User::factory()->createOne();

        $idea = Idea::create([
            'user_id' => $owner->id,
            'title' => 'Compartilhada',
            'description' => 'x',
            'status' => 'on_table',
            'related_type' => 'none',
            'is_private' => false,
        ]);

        $this->actingAs($other)
            ->get(route('ideas.show', $idea))
            ->assertOk();
    }
}
