<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\Idea;
use App\Models\IdeaType;
use App\Models\Task;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();
        $users = User::query()->orderBy('name')->get();
        $assignableUsers = $users->isNotEmpty() ? $users : collect([$user]);

        $ideaTypes = IdeaType::where('user_id', $user->id)->get();
        $ideaCategories = \App\Models\IdeaCategory::where('user_id', $user->id)->get();
        $contentStyles = \App\Models\VenueStyle::where('user_id', $user->id)->where('domain', \App\Models\VenueStyle::DOMAIN_CONTENT)->get();
        $venueStyles = \App\Models\VenueStyle::where('user_id', $user->id)->where('domain', \App\Models\VenueStyle::DOMAIN_VENUES)->get();
        $venueTypes = \App\Models\VenueType::where('user_id', $user->id)->get();
        $venueCategories = \App\Models\VenueCategory::where('user_id', $user->id)->get();
        $platforms = \App\Models\ContentPlatform::where('user_id', $user->id)->get();
        $taskStatuses = \App\Models\TaskStatus::where('user_id', $user->id)->orderBy('order')->get();

        $ideas = collect([
            ['title' => 'Lancar single no inverno', 'status' => 'in_drawer'],
            ['title' => 'Serie de bastidores da tour', 'status' => 'on_table'],
            ['title' => 'Parceria com marca local', 'status' => 'trash'],
            ['title' => 'Clipe acustico na rua', 'status' => 'executing'],
            ['title' => 'Campanha pre-save', 'status' => 'executed'],
        ])->map(function ($row, $index) use ($user, $ideaTypes, $ideaCategories) {
            return Idea::create([
                'user_id' => $user->id,
                'title' => $row['title'],
                'description' => 'Descricao de exemplo da ideia '.$row['title'],
                'idea_type_id' => $ideaTypes[$index % $ideaTypes->count()]?->id,
                'idea_category_id' => $ideaCategories[$index % $ideaCategories->count()]?->id,
                'status' => $row['status'],
                'related_type' => 'none',
            ]);
        });

        $ideas->take(3)->each(function (Idea $idea, int $index) use ($contentStyles): void {
            $idea->references()->createMany([
                [
                    'title' => 'Referencia '.($index + 1),
                    'description' => 'Inspiracao externa',
                    'url' => 'https://example.com/ref-'.($index + 1),
                ],
            ]);

            $styleId = $contentStyles[$index % max($contentStyles->count(), 1)]?->id;
            if ($styleId) {
                $idea->styles()->sync([$styleId]);
            }
        });

        $contents = collect([
            ['title' => 'Teaser single', 'status' => 'published', 'offset' => -3],
            ['title' => 'Video making of', 'status' => 'published', 'offset' => -1],
            ['title' => 'Reel agenda de shows', 'status' => 'in_production', 'offset' => 2],
            ['title' => 'Story chamada para live', 'status' => 'queued', 'offset' => 4],
        ])->map(function ($row, $index) use ($user, $ideas, $ideaTypes, $ideaCategories, $platforms, $contentStyles) {
            $planned = Carbon::now()->addDays($row['offset']);
            $publishedAt = $row['status'] === 'published' ? (clone $planned)->addHours(2) : null;

            $content = Content::create([
                'user_id' => $user->id,
                'idea_id' => $ideas[$index % $ideas->count()]?->id,
                'title' => $row['title'],
                'script' => '<p>Roteiro inicial de exemplo.</p>',
                'idea_type_id' => $ideaTypes[$index % $ideaTypes->count()]?->id,
                'idea_category_id' => $ideaCategories[$index % $ideaCategories->count()]?->id,
                'status' => $row['status'],
                'planned_publish_at' => $planned,
                'published_at' => $publishedAt,
            ]);

            $platformId = $platforms[$index % max($platforms->count(), 1)]?->id;
            if ($platformId) {
                $content->platforms()->sync([$platformId]);
            }

            $styleId = $contentStyles[$index % max($contentStyles->count(), 1)]?->id;
            if ($styleId) {
                $content->styles()->sync([$styleId]);
            }

            $content->links()->create([
                'title' => 'Link externo',
                'url' => 'https://example.com/content-'.$index,
            ]);

            return $content;
        });

        foreach (range(1, 8) as $index) {
            $task = Task::create([
                'user_id' => $user->id,
                'assigned_user_id' => $assignableUsers[($index - 1) % $assignableUsers->count()]?->id,
                'related_type' => $index % 3 === 0 ? 'administrative' : 'content',
                'content_id' => $index <= 4 ? $contents[$index % $contents->count()]?->id : null,
                'plan_id' => null,
                'plan_phase_id' => null,
                'title' => 'Tarefa '.$index,
                'description' => 'Descricao da tarefa '.$index,
                'task_status_id' => $taskStatuses[$index % $taskStatuses->count()]?->id,
                'priority' => ['low', 'medium', 'high', 'urgent'][$index % 4],
                'scheduled_for' => Carbon::now()->addDays($index)->setTime(10 + ($index % 6), 0),
                'due_date' => Carbon::now()->addDays($index),
            ]);

            $task->subtasks()->createMany([
                ['title' => 'Subtarefa A '.$index, 'completed' => false, 'order' => 1],
                ['title' => 'Subtarefa B '.$index, 'completed' => $index % 2 === 0, 'order' => 2],
            ]);
        }

        $sizes = \App\Models\VenueSize::orderBy('name')->get();

        foreach (range(1, 3) as $index) {
            Venue::create([
                'user_id' => $user->id,
                'name' => 'Casa '.$index,
                'email' => 'contato'.$index.'@casa.com',
                'phone' => '(11) 99999-000'.$index,
                'contact_name' => 'Produtor '.$index,
                'venue_size_id' => $sizes[$index % $sizes->count()]?->id,
                'venue_type_id' => $venueTypes[$index % max($venueTypes->count(), 1)]?->id,
                'venue_category_id' => $venueCategories[$index % max($venueCategories->count(), 1)]?->id,
                'venue_style_id' => $venueStyles[$index % max($venueStyles->count(), 1)]?->id,
                'address_line' => 'Rua Exemplo '.$index,
                'address_number' => (string) (100 + $index),
                'neighborhood' => 'Centro',
                'city' => 'São Paulo',
                'state' => 'SP',
                'postal_code' => '01000-000',
                'country' => 'Brasil',
                'latitude' => -23.5505 + ($index / 1000),
                'longitude' => -46.6333 - ($index / 1000),
                'status' => 'contacted',
                'performances_count' => $index,
                'equipment_tags' => ['PA', 'Monitor', 'Luz básica'],
                'rating' => min(5, 2 + $index),
                'instagram_url' => 'https://instagram.com/casa'.$index,
                'facebook_url' => 'https://facebook.com/casa'.$index,
                'youtube_url' => 'https://youtube.com/@casa'.$index,
                'whatsapp' => '(11) 98888-100'.$index,
                'website_url' => 'https://casa'.$index.'.com',
                'notes' => 'Possui boa estrutura tecnica',
                'description' => 'Local de show de exemplo',
            ]);
        }

    }
}
