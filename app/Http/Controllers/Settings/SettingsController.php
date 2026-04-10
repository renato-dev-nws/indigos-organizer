<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContentCategory;
use App\Models\ContentPlatform;
use App\Models\ContentType;
use App\Models\IdeaCategory;
use App\Models\IdeaType;
use App\Models\TaskStatus;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $userId = (string) Auth::id();

        return Inertia::render('Settings/Index', [
            'ideaTypes' => IdeaType::where('user_id', $userId)->withCount('ideas')->orderBy('name')->get(),
            'ideaCategories' => IdeaCategory::where('user_id', $userId)->orderBy('name')->get(),
            'contentPlatforms' => ContentPlatform::where('user_id', $userId)->orderBy('name')->get(),
            'contentTypes' => ContentType::where('user_id', $userId)->orderBy('name')->get(),
            'contentCategories' => ContentCategory::where('user_id', $userId)->orderBy('name')->get(),
            'taskStatuses' => TaskStatus::where('user_id', $userId)->withCount('tasks')->orderBy('order')->get(),
        ]);
    }
}
