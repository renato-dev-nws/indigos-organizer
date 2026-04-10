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
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $userId = (string) Auth::id();

        // Tasks: urgent first, then by due_date, limited to 10, assigned to the user
        $tasks = Task::query()
            ->with(['user', 'status'])
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->limit(10)
            ->get();

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
            'tasks' => $tasks,
        ]);
    }
}
