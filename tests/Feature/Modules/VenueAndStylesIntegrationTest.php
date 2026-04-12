<?php

namespace Tests\Feature\Modules;

use App\Models\ContentPlatform;
use App\Models\IdeaCategory;
use App\Models\IdeaType;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\VenueStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueAndStylesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_can_store_icon_for_idea_type(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->post(route('settings.idea-types.store'), [
                'name' => 'Documentário',
                'color' => '#123abc',
                'icon' => 'mdi:video',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('idea_types', [
            'user_id' => $user->id,
            'name' => 'Documentário',
            'icon' => 'mdi:video',
        ]);
    }

    public function test_idea_store_syncs_multiple_styles(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        TaskStatus::create(['user_id' => $user->id, 'name' => 'Backlog', 'color' => '#111111', 'order' => 1]);

        $type = IdeaType::create(['user_id' => $user->id, 'name' => 'Vídeo', 'color' => '#ff0000']);
        $category = IdeaCategory::create(['user_id' => $user->id, 'name' => 'Marketing']);
        $styleA = VenueStyle::create(['user_id' => $user->id, 'name' => 'Acústico', 'color' => '#00aa00']);
        $styleB = VenueStyle::create(['user_id' => $user->id, 'name' => 'Vintage', 'color' => '#aa00aa']);

        $this->actingAs($user)
            ->post(route('ideas.store'), [
                'title' => 'Nova ideia com estilos',
                'description' => 'teste',
                'idea_type_id' => $type->id,
                'idea_category_id' => $category->id,
                'status' => 'in_drawer',
                'related_type' => 'none',
                'is_private' => false,
                'venue_style_ids' => [$styleA->id, $styleB->id],
            ])
            ->assertRedirect(route('ideas.index'));

        $idea = $user->ideas()->firstOrFail();
        $this->assertCount(2, $idea->styles);
    }

    public function test_content_store_syncs_styles_and_venue_stores_equipment_array(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        TaskStatus::create(['user_id' => $user->id, 'name' => 'Backlog', 'color' => '#111111', 'order' => 1]);

        $type = IdeaType::create(['user_id' => $user->id, 'name' => 'Vídeo', 'color' => '#ff0000']);
        $category = IdeaCategory::create(['user_id' => $user->id, 'name' => 'Marketing']);
        $styleA = VenueStyle::create(['user_id' => $user->id, 'name' => 'Acústico', 'color' => '#00aa00']);
        $platform = ContentPlatform::create(['user_id' => $user->id, 'name' => 'Instagram']);

        $this->actingAs($user)
            ->post(route('contents.store'), [
                'title' => 'Conteúdo com estilo',
                'script' => 'roteiro',
                'status' => 'queued',
                'idea_type_id' => $type->id,
                'idea_category_id' => $category->id,
                'content_platform_ids' => [$platform->id],
                'venue_style_ids' => [$styleA->id],
            ])
            ->assertRedirect(route('contents.index'));

        $content = $user->contents()->firstOrFail();
        $this->assertCount(1, $content->styles);

        $this->actingAs($user)
            ->post(route('venues.store'), [
                'name' => 'Casa Teste',
                'status' => 'undefined',
                'equipment_tags' => ['PA', 'Luz', 'Monitor'],
            ])
            ->assertRedirect(route('venues.index'));

        $venue = $user->venues()->latest()->firstOrFail();
        $this->assertSame(['PA', 'Luz', 'Monitor'], $venue->equipment_tags);
    }
}
