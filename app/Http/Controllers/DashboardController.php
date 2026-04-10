<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Idea;
use App\Models\Task;
use App\Models\Venue;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        return Inertia::render('Dashboard/Index', [
            'summary' => [
                'pendingIdeas' => Idea::where('status', 'pending')->count(),
                'contentsThisWeek' => Content::query()
                    ->whereBetween('planned_publish_at', [$startOfWeek, $endOfWeek])
                    ->count(),
                'urgentOpenTasks' => Task::query()
                    ->where('priority', 'urgent')
                    ->whereNull('deleted_at')
                    ->count(),
                'venuesCount' => Venue::count(),
            ],
            'nextContents' => Content::query()
                ->with('user')
                ->whereNotNull('planned_publish_at')
                ->orderBy('planned_publish_at')
                ->limit(5)
                ->get(),
            'urgentTasks' => Task::query()
                ->with('user')
                ->where('priority', 'urgent')
                ->orderBy('due_date')
                ->limit(5)
                ->get(),
        ]);
    }
}
