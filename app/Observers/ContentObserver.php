<?php

namespace App\Observers;

use App\Models\Content;
use App\Models\Idea;

class ContentObserver
{
    public function updated(Content $content): void
    {
        if (! $content->wasChanged('status')) {
            return;
        }

        if ($content->status === 'in_production') {
            Idea::query()
                ->where('content_id', $content->id)
                ->whereNotIn('status', ['executed', 'trash'])
                ->update(['status' => 'executing']);
        }

        if ($content->status === 'published') {
            Idea::query()
                ->where('content_id', $content->id)
                ->update(['status' => 'executed']);
        }
    }
}
