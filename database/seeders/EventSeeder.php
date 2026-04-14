<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();
        $eventType = EventType::query()->where('user_id', $user->id)->orderBy('name')->first();
        $venue = Venue::query()->orderBy('name')->first();
        $taskStatus = TaskStatus::query()->where('user_id', $user->id)->orderBy('order')->first();

        if (! $eventType || ! $venue || ! $taskStatus) {
            return;
        }

        $event = Event::updateOrCreate(
            ['user_id' => $user->id, 'title' => 'Show de lançamento do single'],
            [
                'event_type_id' => $eventType->id,
                'venue_id' => $venue->id,
                'attendance_mode' => 'participant',
                'description' => 'Evento de demonstração para agenda da banda.',
                'event_date' => Carbon::now()->addDays(10)->toDateString(),
                'event_time' => '21:00',
                'ticket_link' => 'https://example.com/evento-lancamento',
                'ticket_price_first_batch' => 25,
                'ticket_price_second_batch' => 35,
                'ticket_price_third_batch' => 45,
                'ticket_price_door' => 50,
            ]
        );

        $event->extraInfos()->delete();
        $event->extraInfos()->createMany([
            ['title' => 'Abertura da casa', 'information' => 'Portões abrem às 20h.', 'order' => 1],
            ['title' => 'Line-up', 'information' => 'Banda principal + artista convidado.', 'order' => 2],
        ]);

        $event->links()->delete();
        $event->links()->createMany([
            ['title' => 'Evento no Instagram', 'url' => 'https://instagram.com/p/evento-lancamento'],
            ['title' => 'Mapa do local', 'url' => 'https://maps.google.com/?q=-23.5505,-46.6333'],
        ]);

        Task::updateOrCreate(
            ['title' => 'Fechar rider técnico do evento', 'event_id' => $event->id],
            [
                'user_id' => $user->id,
                'assigned_user_id' => $user->id,
                'related_type' => 'event',
                'content_id' => null,
                'plan_id' => null,
                'plan_phase_id' => null,
                'description' => 'Confirmar rider e passagem de som com a produção do local.',
                'task_status_id' => $taskStatus->id,
                'priority' => 'high',
                'due_date' => Carbon::now()->addDays(8)->toDateString(),
            ]
        );
    }
}