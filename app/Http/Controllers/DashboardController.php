<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'boardIdeas' => Idea::query()
                ->where('status', 'on_board')
                ->where(function ($q) {
                    $q->whereDoesntHave('voterUsers')
                        ->orWhereHas('voterUsers', fn ($q2) => $q2->where('user_id', Auth::id()));
                })
                ->whereDoesntHave('votes', fn ($q) => $q->where('user_id', Auth::id()))
                ->with(['user', 'votes'])
                ->get(),
            'myTasks' => Task::query()
                ->where(function ($q) {
                    $q->whereNull('assigned_user_id')
                        ->orWhere('assigned_user_id', Auth::id());
                })
                ->with('status')
                ->whereHas('status')
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
