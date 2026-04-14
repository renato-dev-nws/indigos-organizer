<?php

namespace Tests\Feature\Modules;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Content;
use App\Models\Event;
use App\Models\EventType;
use App\Models\SharedInfo;
use App\Models\SharedInfoCategory;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventAndCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_event_with_repeaters(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $type = EventType::create(['user_id' => $user->id, 'name' => 'Show', 'color' => '#ff0000']);
        $venue = Venue::create(['user_id' => $user->id, 'name' => 'Audio Club', 'status' => 'undefined']);

        $this->actingAs($user)
            ->post(route('events.store'), [
                'title' => 'Show teste',
                'event_type_id' => $type->id,
                'venue_id' => $venue->id,
                'attendance_mode' => 'participant',
                'description' => 'descricao',
                'event_date' => '2026-05-10',
                'event_time' => '20:30',
                'ticket_link' => 'https://example.com/tickets',
                'ticket_price_first_batch' => 20,
                'extra_infos' => [
                    ['title' => 'Abertura', 'information' => '19h'],
                ],
                'links' => [
                    ['title' => 'Instagram', 'url' => 'https://instagram.com/showteste'],
                ],
            ])
            ->assertRedirect(route('events.index'));

        $event = Event::query()->firstOrFail();
        $this->assertSame('Show teste', $event->title);
        $this->assertDatabaseHas('event_extra_infos', ['event_id' => $event->id, 'title' => 'Abertura']);
        $this->assertDatabaseHas('event_links', ['event_id' => $event->id, 'title' => 'Instagram']);
    }

    public function test_general_calendar_merges_contents_and_events(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $type = EventType::create(['user_id' => $user->id, 'name' => 'Festival', 'color' => '#00aa00']);
        $venue = Venue::create(['user_id' => $user->id, 'name' => 'Casa', 'status' => 'undefined']);

        Content::create([
            'user_id' => $user->id,
            'title' => 'Conteúdo agendado',
            'status' => 'queued',
            'planned_publish_at' => now()->addDays(2),
        ]);

        Event::create([
            'user_id' => $user->id,
            'event_type_id' => $type->id,
            'venue_id' => $venue->id,
            'title' => 'Evento agendado',
            'attendance_mode' => 'audience',
            'event_date' => now()->addDays(3)->toDateString(),
            'event_time' => '22:00',
        ]);

        $inertiaVersion = app(HandleInertiaRequests::class)->version(Request::create(route('calendar.index'), 'GET'));

        $this->actingAs($user)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $inertiaVersion,
            ])
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertJsonPath('component', 'Calendar/Index')
            ->assertJsonCount(2, 'props.calendarItems');
    }

    public function test_user_can_quick_create_venue_for_event_form(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $this->actingAs($user)
            ->postJson(route('venues.quick-store'), [
                'name' => 'Novo local rápido',
                'status' => 'undefined',
                'address_line' => 'Rua das Flores',
                'city' => 'São Paulo',
                'state' => 'SP',
                'latitude' => -23.5,
                'longitude' => -46.6,
            ])
            ->assertCreated()
            ->assertJsonPath('venue.name', 'Novo local rápido');

        $this->assertDatabaseHas('venues', ['user_id' => $user->id, 'name' => 'Novo local rápido']);
    }

    public function test_shared_info_store_syncs_categories(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $category = SharedInfoCategory::create(['user_id' => $user->id, 'name' => 'Documentação']);

        $this->actingAs($user)
            ->post(route('shared-infos.store'), [
                'title' => 'Checklist de palco',
                'shared_info_category_ids' => [$category->id],
                'description' => 'Levar contratos e rider.',
            ])
            ->assertRedirect(route('shared-infos.index'));

        $info = SharedInfo::query()->firstOrFail();
        $this->assertTrue($info->categories()->whereKey($category->id)->exists());
    }

    public function test_task_can_be_linked_to_event(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();
        $type = EventType::create(['user_id' => $user->id, 'name' => 'Show', 'color' => '#111111']);
        $venue = Venue::create(['user_id' => $user->id, 'name' => 'Palco', 'status' => 'undefined']);
        $status = TaskStatus::create(['user_id' => $user->id, 'name' => 'Pendente', 'color' => '#123456', 'order' => 1]);
        $event = Event::create([
            'user_id' => $user->id,
            'event_type_id' => $type->id,
            'venue_id' => $venue->id,
            'title' => 'Show principal',
            'attendance_mode' => 'participant',
            'event_date' => now()->addWeek()->toDateString(),
        ]);

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'related_type' => 'event',
                'event_id' => $event->id,
                'title' => 'Confirmar horário de passagem',
                'task_status_id' => $status->id,
                'priority' => 'high',
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'related_type' => 'event',
            'event_id' => $event->id,
            'title' => 'Confirmar horário de passagem',
        ]);
    }
}