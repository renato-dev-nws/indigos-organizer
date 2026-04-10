<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Idea;
use App\Models\Task;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $userId = (string) Auth::id();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        return Inertia::render('Dashboard/Index', [
            'summary' => [
                'pendingIdeas' => Idea::where('user_id', $userId)->where('status', 'pending')->count(),
                'contentsThisWeek' => Content::where('user_id', $userId)
                    ->whereBetween('planned_publish_at', [$startOfWeek, $endOfWeek])
                    ->count(),
                'urgentOpenTasks' => Task::where('user_id', $userId)
                    ->where('priority', 'urgent')
                    ->whereNull('deleted_at')
                    ->count(),
                'venuesCount' => Venue::where('user_id', $userId)->count(),
            ],
            'nextContents' => Content::where('user_id', $userId)
                ->whereNotNull('planned_publish_at')
                ->orderBy('planned_publish_at')
                ->limit(5)
                ->get(),
            'urgentTasks' => Task::where('user_id', $userId)
                ->where('priority', 'urgent')
                ->orderBy('due_date')
                ->limit(5)
                ->get(),
        ]);
    }
}
