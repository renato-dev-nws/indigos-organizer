<?php

namespace App\Observers;

use App\Models\Idea;
use App\Models\Plan;

class PlanObserver
{
    public function updated(Plan $plan): void
    {
        if (! $plan->wasChanged('status')) {
            return;
        }

        if ($plan->status === 'running') {
            Idea::query()
                ->where('plan_id', $plan->id)
                ->whereNotIn('status', ['executed', 'trash'])
                ->update(['status' => 'executing']);
        }

        if ($plan->status === 'completed') {
            Idea::query()
                ->where('plan_id', $plan->id)
                ->update(['status' => 'executed']);
        }
    }
}
