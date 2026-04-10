<?php

namespace Tests\Feature\Modules;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaFiltersAndPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_idea_index_applies_search_and_scopes_by_user(): void
    {
        /** @var User $userA */
        $userA = User::factory()->createOne();
        /** @var User $userB */
        $userB = User::factory()->createOne();

        Idea::create([
            'user_id' => $userA->id,
            'title' => 'Ideia Alpha',
            'description' => 'A',
            'status' => 'pending',
        ]);

        Idea::create([
            'user_id' => $userB->id,
            'title' => 'Ideia Beta',
            'description' => 'B',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($userA)->get(route('ideas.index', ['search' => 'Beta']));

        $response->assertOk();
        $response->assertDontSee('Ideia Beta');
        $response->assertDontSee('Ideia Alpha');

        $response2 = $this->actingAs($userA)->get(route('ideas.index', ['search' => 'Alpha']));
        $response2->assertOk();
        $response2->assertSee('Ideia Alpha');
        $response2->assertDontSee('Ideia Beta');
    }

    public function test_user_cannot_view_idea_from_another_user(): void
    {
        /** @var User $owner */
        $owner = User::factory()->createOne();
        /** @var User $other */
        $other = User::factory()->createOne();

        $idea = Idea::create([
            'user_id' => $owner->id,
            'title' => 'Privada',
            'description' => 'x',
            'status' => 'pending',
        ]);

        $this->actingAs($other)
            ->get(route('ideas.show', $idea))
            ->assertForbidden();
    }
}
