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
        $user = User::where('email', 'demo@band.com')->firstOrFail();

        $ideaTypes = IdeaType::where('user_id', $user->id)->get();
        $ideaCategories = \App\Models\IdeaCategory::where('user_id', $user->id)->get();
        $platforms = \App\Models\ContentPlatform::where('user_id', $user->id)->get();
        $taskStatuses = \App\Models\TaskStatus::where('user_id', $user->id)->orderBy('order')->get();

        $ideas = collect([
            ['title' => 'Lancar single no inverno', 'status' => 'pending'],
            ['title' => 'Serie de bastidores da tour', 'status' => 'maturing'],
            ['title' => 'Parceria com marca local', 'status' => 'cancelled'],
            ['title' => 'Clipe acustico na rua', 'status' => 'in_production'],
            ['title' => 'Campanha pre-save', 'status' => 'executed'],
        ])->map(function ($row, $index) use ($user, $ideaTypes, $ideaCategories) {
            return Idea::create([
                'user_id' => $user->id,
                'title' => $row['title'],
                'description' => 'Descricao de exemplo da ideia '.$row['title'],
                'idea_type_id' => $ideaTypes[$index % $ideaTypes->count()]?->id,
                'idea_category_id' => $ideaCategories[$index % $ideaCategories->count()]?->id,
                'status' => $row['status'],
            ]);
        });

        $ideas->take(3)->each(function (Idea $idea, int $index): void {
            $idea->references()->createMany([
                [
                    'title' => 'Referencia '.($index + 1),
                    'description' => 'Inspiracao externa',
                    'url' => 'https://example.com/ref-'.($index + 1),
                ],
            ]);
        });

        $contents = collect([
            ['title' => 'Teaser single', 'status' => 'published', 'offset' => -3],
            ['title' => 'Video making of', 'status' => 'published', 'offset' => -1],
            ['title' => 'Reel agenda de shows', 'status' => 'in_production', 'offset' => 2],
            ['title' => 'Story chamada para live', 'status' => 'queued', 'offset' => 4],
        ])->map(function ($row, $index) use ($user, $ideas, $ideaTypes, $ideaCategories, $platforms) {
            $planned = Carbon::now()->addDays($row['offset']);
            $publishedAt = $row['status'] === 'published' ? (clone $planned)->addHours(2) : null;

            $content = Content::create([
                'user_id' => $user->id,
                'idea_id' => $ideas[$index % $ideas->count()]?->id,
                'title' => $row['title'],
                'script' => '<p>Roteiro inicial de exemplo.</p>',
                'content_platform_id' => $platforms[$index % $platforms->count()]?->id,
                'idea_type_id' => $ideaTypes[$index % $ideaTypes->count()]?->id,
                'idea_category_id' => $ideaCategories[$index % $ideaCategories->count()]?->id,
                'status' => $row['status'],
                'planned_publish_at' => $planned,
                'published_at' => $publishedAt,
            ]);

            $content->links()->create([
                'title' => 'Link externo',
                'url' => 'https://example.com/content-'.$index,
            ]);

            return $content;
        });

        foreach (range(1, 8) as $index) {
            $task = Task::create([
                'user_id' => $user->id,
                'content_id' => $index <= 4 ? $contents[$index % $contents->count()]?->id : null,
                'title' => 'Tarefa '.$index,
                'description' => 'Descricao da tarefa '.$index,
                'type' => $index % 3 === 0 ? 'administrative' : 'content',
                'task_status_id' => $taskStatuses[$index % $taskStatuses->count()]?->id,
                'assignee' => $index % 2 === 0 ? 'Renato' : 'Equipe',
                'priority' => ['low', 'medium', 'high', 'urgent'][$index % 4],
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
                'instagram_url' => 'https://instagram.com/casa'.$index,
                'facebook_url' => 'https://facebook.com/casa'.$index,
                'youtube_url' => 'https://youtube.com/@casa'.$index,
                'website_url' => 'https://casa'.$index.'.com',
                'notes' => 'Possui boa estrutura tecnica',
                'description' => 'Local de show de exemplo',
            ]);
        }
    }
}
