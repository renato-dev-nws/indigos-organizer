<?php

namespace App\Actions;

use App\Models\Content;
use App\Models\Idea;
use Illuminate\Support\Facades\DB;

class ExecuteIdeaAction
{
    public function handle(Idea $idea): Content
    {
        return DB::transaction(function () use ($idea): Content {
            $content = Content::create([
                'title' => $idea->title,
                'idea_id' => $idea->id,
                'user_id' => $idea->user_id,
                'idea_type_id' => $idea->idea_type_id,
                'idea_category_id' => $idea->idea_category_id,
                'status' => 'queued',
            ]);

            $links = $idea->references->map(fn ($reference) => [
                'title' => $reference->title,
                'url' => $reference->url,
            ])->all();

            if ($links !== []) {
                $content->links()->createMany($links);
            }

            $idea->update(['status' => 'in_production']);

            return $content;
        });
    }
}
