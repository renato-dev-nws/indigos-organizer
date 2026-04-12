<?php

namespace App\Providers;

use App\Models\Content;
use App\Models\Idea;
use App\Models\Plan;
use App\Models\Task;
use App\Observers\ContentObserver;
use App\Observers\IdeaObserver;
use App\Observers\PlanObserver;
use App\Observers\TaskObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Content::observe(ContentObserver::class);
        Plan::observe(PlanObserver::class);
        Task::observe(TaskObserver::class);
        Idea::observe(IdeaObserver::class);

        Vite::prefetch(concurrency: 3);
    }
}
