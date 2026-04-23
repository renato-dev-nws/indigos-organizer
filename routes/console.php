<?php

use App\Jobs\DispatchDueSoonTasksNotificationsJob;
use App\Jobs\DispatchTaskReminderNotificationsJob;
use Database\Seeders\InitialSettingsSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('settings:seed', function () {
    $this->call('db:seed', [
        '--class' => InitialSettingsSeeder::class,
        '--force' => true,
    ]);

    $this->info('Configurações iniciais semeadas com sucesso.');
})->purpose('Seed only initial panel settings');

Artisan::command('panel:reset', function () {
    $this->call('migrate:fresh', ['--force' => true]);
    $this->call('db:seed', [
        '--class' => InitialSettingsSeeder::class,
        '--force' => true,
    ]);

    $this->info('Painel resetado com configurações iniciais.');
})->purpose('Reset panel database and seed only settings');

Schedule::call(fn () => DispatchDueSoonTasksNotificationsJob::dispatchSync())->everyFifteenMinutes();
Schedule::call(fn () => DispatchTaskReminderNotificationsJob::dispatchSync())->everyFiveMinutes();
