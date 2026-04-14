<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class GeneralCalendarController extends Controller
{
    public function __invoke(): Response
    {
        $contentItems = Content::query()
            ->whereNotNull('planned_publish_at')
            ->get(['id', 'title', 'planned_publish_at'])
            ->map(fn (Content $content) => [
                'id' => 'content-'.$content->id,
                'title' => $content->title,
                'start' => optional($content->planned_publish_at)->toIso8601String(),
                'type' => 'content',
                'color' => '#4f46e5',
                'url' => route('contents.show', $content),
            ]);

        $eventItems = Event::query()
            ->with('type:id,name,color')
            ->get(['id', 'title', 'event_date', 'event_time', 'event_type_id'])
            ->map(fn (Event $event) => [
                'id' => 'event-'.$event->id,
                'title' => $event->title,
                'start' => $event->starts_at,
                'type' => 'event',
                'color' => $event->type?->color ?: '#059669',
                'url' => route('events.show', $event),
            ]);

        return Inertia::render('Calendar/Index', [
            'calendarItems' => $contentItems->concat($eventItems)->values(),
            'legend' => [
                ['label' => 'Conteúdos', 'color' => '#4f46e5'],
                ['label' => 'Eventos', 'color' => '#059669'],
            ],
        ]);
    }
}