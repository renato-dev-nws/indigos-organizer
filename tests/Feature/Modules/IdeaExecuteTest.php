<?php

namespace Tests\Feature\Modules;

use App\Models\Idea;
use App\Models\IdeaVote;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaExecuteTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_user_can_vote_on_board_idea(): void
    {
        /** @var Authenticatable $owner */
        $owner = User::factory()->createOne();
        /** @var Authenticatable $voter */
        $voter = User::factory()->createOne();

        $idea = Idea::create([
            'user_id' => $owner->id,
            'title' => 'Ideia em votação',
            'description' => 'descrição',
            'status' => 'on_board',
            'related_type' => 'none',
        ]);

        $idea->voterUsers()->attach($voter->id);

        $response = $this->actingAs($voter)->post(route('ideas.vote', $idea), [
            'vote' => 'on_table',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('idea_votes', [
            'idea_id' => $idea->id,
            'user_id' => $voter->id,
            'vote' => 'on_table',
        ]);
    }

    public function test_vote_updates_existing_vote_record(): void
    {
        /** @var Authenticatable $owner */
        $owner = User::factory()->createOne();
        /** @var Authenticatable $voter */
        $voter = User::factory()->createOne();

        $idea = Idea::create([
            'user_id' => $owner->id,
            'title' => 'Ideia em votação',
            'description' => 'descrição',
            'status' => 'on_board',
            'related_type' => 'none',
        ]);

        IdeaVote::create([
            'idea_id' => $idea->id,
            'user_id' => $voter->id,
            'vote' => 'on_table',
        ]);

        $response = $this->actingAs($voter)->post(route('ideas.vote', $idea), [
            'vote' => 'trash',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('idea_votes', [
            'idea_id' => $idea->id,
            'user_id' => $voter->id,
            'vote' => 'trash',
        ]);
    }
}
