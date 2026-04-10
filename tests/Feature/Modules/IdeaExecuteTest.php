<?php

namespace Tests\Feature\Modules;

use App\Models\Idea;
use App\Models\IdeaCategory;
use App\Models\IdeaReference;
use App\Models\IdeaType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaExecuteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_execute_idea_and_generate_content_with_links(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $type = IdeaType::create(['user_id' => $user->id, 'name' => 'Clipe', 'color' => '#111111']);
        $category = IdeaCategory::create(['user_id' => $user->id, 'name' => 'Letras']);

        $idea = Idea::create([
            'user_id'          => $user->id,
            'idea_type_id'     => $type->id,
            'idea_category_id' => $category->id,
            'title'            => 'Nova ideia de teste',
            'description'      => 'descricao',
            'status'           => 'pending',
        ]);

        IdeaReference::create([
            'idea_id'     => $idea->id,
            'title'       => 'Referencia 1',
            'description' => 'ref',
            'url'         => 'https://example.com/ref-1',
        ]);

        $response = $this->actingAs($user)->post(route('ideas.execute', $idea));

        $response->assertRedirect(route('contents.index'));

        $this->assertDatabaseHas('contents', [
            'user_id'          => $user->id,
            'idea_id'          => $idea->id,
            'title'            => 'Nova ideia de teste',
            'status'           => 'queued',
            'idea_type_id'     => $type->id,
            'idea_category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('ideas', [
            'id'     => $idea->id,
            'status' => 'in_production',
        ]);

        $content = \App\Models\Content::where('idea_id', $idea->id)->firstOrFail();

        $this->assertDatabaseHas('content_links', [
            'content_id' => $content->id,
            'title'      => 'Referencia 1',
            'url'        => 'https://example.com/ref-1',
        ]);
    }

    public function test_execute_idea_without_type_and_category_still_creates_content(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $idea = Idea::create([
            'user_id'     => $user->id,
            'title'       => 'Ideia sem tipo',
            'description' => 'sem tipo nem categoria',
            'status'      => 'pending',
        ]);

        $this->actingAs($user)->post(route('ideas.execute', $idea))->assertRedirect(route('contents.index'));

        $this->assertDatabaseHas('contents', [
            'user_id'          => $user->id,
            'idea_id'          => $idea->id,
            'title'            => 'Ideia sem tipo',
            'status'           => 'queued',
            'idea_type_id'     => null,
            'idea_category_id' => null,
        ]);
    }
}
