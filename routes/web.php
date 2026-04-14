<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\ContentFileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SharedInfoController;
use App\Http\Controllers\SharedInfoDocumentController;
use App\Http\Controllers\Settings\ContentPlatformController;
use App\Http\Controllers\Settings\IdeaCategoryController;
use App\Http\Controllers\Settings\IdeaTypeController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SystemSettingController;
use App\Http\Controllers\Settings\TaskStatusController;
use App\Http\Controllers\Settings\ThemeController;
use App\Http\Controllers\Settings\VenueCategoryController;
use App\Http\Controllers\Settings\VenueStyleController;
use App\Http\Controllers\Settings\VenueTypeController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('ideas', IdeaController::class);
    Route::post('/ideas/{idea}/vote', [IdeaController::class, 'vote'])->name('ideas.vote');

    Route::resource('contents', ContentController::class);
    Route::post('/contents/{content}/files', [ContentFileController::class, 'store'])->name('contents.files.store');
    Route::delete('/contents/{content}/files/{file}', [ContentFileController::class, 'destroy'])->name('contents.files.destroy');

    Route::resource('tasks', TaskController::class);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store'])->name('tasks.subtasks.store');
    Route::patch('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'update'])->name('tasks.subtasks.update');
    Route::delete('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'destroy'])->name('tasks.subtasks.destroy');

    Route::resource('venues', VenueController::class);
    Route::resource('plans', PlanController::class);
    Route::resource('shared-infos', SharedInfoController::class);
    Route::delete('/shared-infos/{sharedInfo}/documents/{document}', [SharedInfoDocumentController::class, 'destroy'])
        ->name('shared-infos.documents.destroy');
    Route::resource('users', UserController::class)->except(['show']);

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/types', [SettingsController::class, 'types'])->name('settings.pages.types');
    Route::get('/settings/types/venues', [SettingsController::class, 'venueTypes'])->name('settings.pages.types.venues');
    Route::get('/settings/categories', [SettingsController::class, 'categories'])->name('settings.pages.categories');
    Route::get('/settings/categories/venues', [SettingsController::class, 'venueCategories'])->name('settings.pages.categories.venues');
    Route::get('/settings/styles', [SettingsController::class, 'styles'])->name('settings.pages.styles');
    Route::get('/settings/content-platforms', [SettingsController::class, 'contentPlatforms'])->name('settings.pages.content-platforms');
    Route::get('/settings/task-statuses', [SettingsController::class, 'taskStatuses'])->name('settings.pages.task-statuses');
    Route::get('/settings/system', [SystemSettingController::class, 'index'])->name('settings.pages.system');
    Route::post('/settings/system', [SystemSettingController::class, 'update'])->name('settings.system.update');
    Route::put('/settings/theme', [ThemeController::class, 'update'])->name('settings.theme');
    Route::resource('/settings/idea-types', IdeaTypeController::class)->only(['store', 'update', 'destroy'])->names('settings.idea-types');
    Route::resource('/settings/idea-categories', IdeaCategoryController::class)->only(['store', 'update', 'destroy'])->names('settings.idea-categories');
    Route::resource('/settings/venue-types', VenueTypeController::class)->only(['store', 'update', 'destroy'])->names('settings.venue-types');
    Route::resource('/settings/venue-categories', VenueCategoryController::class)->only(['store', 'update', 'destroy'])->names('settings.venue-categories');
    Route::resource('/settings/content-styles', VenueStyleController::class)->only(['store', 'update', 'destroy'])->names('settings.content-styles');
    Route::resource('/settings/venue-styles', VenueStyleController::class)->only(['store', 'update', 'destroy'])->names('settings.venue-styles');
    Route::resource('/settings/content-platforms', ContentPlatformController::class)->only(['store', 'update', 'destroy'])->names('settings.content-platforms');
    Route::patch('/settings/task-statuses/reorder', [TaskStatusController::class, 'reorder'])->name('settings.task-statuses.reorder');
    Route::resource('/settings/task-statuses', TaskStatusController::class)->only(['store', 'update', 'destroy'])->names('settings.task-statuses');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Push subscriptions
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
});

// Serve PWA service worker from root scope with correct headers
Route::get('/sw.js', function () {
    $path = public_path('build/sw.js');
    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache',
    ]);
});

// Workbox precaches manifest.webmanifest from root scope.
Route::get('/manifest.webmanifest', function () {
    $path = public_path('build/manifest.webmanifest');
    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/manifest+json',
        'Cache-Control' => 'no-cache',
    ]);
});

// Workbox runtime file is generated under /public/build, but sw.js imports it from root.
Route::get('/{workbox}', function (string $workbox) {
    $path = public_path('build/'.$workbox);
    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/javascript',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('workbox', 'workbox-[A-Za-z0-9_-]+\.js');

require __DIR__.'/auth.php';
