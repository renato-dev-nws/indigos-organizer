<?php

use App\Jobs\DispatchDueSoonTasksNotificationsJob;
use App\Jobs\DispatchTaskReminderNotificationsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new DispatchDueSoonTasksNotificationsJob())->everyFifteenMinutes();
Schedule::job(new DispatchTaskReminderNotificationsJob())->everyFiveMinutes();
