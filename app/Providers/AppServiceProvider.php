<?php

namespace App\Providers;

use App\Models\Content;
use App\Models\Plan;
use App\Observers\ContentObserver;
use App\Observers\PlanObserver;
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

        Vite::prefetch(concurrency: 3);
    }
}
