<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\ContentFileController;
use App\Http\Controllers\CloudConnectionController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FastNoteController;
use App\Http\Controllers\GeneralCalendarController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SharedInfoController;
use App\Http\Controllers\SharedInfoDocumentController;
use App\Http\Controllers\Settings\ContentPlatformController;
use App\Http\Controllers\Settings\EventTypeController;
use App\Http\Controllers\Settings\IdeaCategoryController;
use App\Http\Controllers\Settings\IdeaTypeController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SharedInfoCategoryController;
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

    Route::get('/calendar', GeneralCalendarController::class)->name('calendar.index');

    Route::resource('ideas', IdeaController::class);
    Route::post('/ideas/{idea}/vote', [IdeaController::class, 'vote'])->name('ideas.vote');
    Route::patch('/ideas/{idea}/status', [IdeaController::class, 'updateStatus'])->name('ideas.status');

    Route::resource('contents', ContentController::class);
    Route::post('/contents/{content}/files', [ContentFileController::class, 'store'])->name('contents.files.store');
    Route::post('/contents/{content}/files/attach', [ContentFileController::class, 'attach'])->name('contents.files.attach');
    Route::get('/contents/{content}/files/{file}/open', [ContentFileController::class, 'open'])->name('contents.files.open');
    Route::delete('/contents/{content}/files/{file}', [ContentFileController::class, 'destroy'])->name('contents.files.destroy');

    Route::get('/cloud/{provider}/redirect', [CloudConnectionController::class, 'redirect'])->name('cloud.redirect');
    Route::get('/cloud/{provider}/callback', [CloudConnectionController::class, 'callback'])->name('cloud.callback');
    Route::delete('/cloud/{provider}', [CloudConnectionController::class, 'disconnect'])->name('cloud.disconnect');
    Route::patch('/cloud/{provider}/folder', [CloudConnectionController::class, 'updateFolder'])->name('cloud.folder');
    Route::post('/cloud/{provider}/test', [CloudConnectionController::class, 'test'])->name('cloud.test');
    Route::get('/cloud/{provider}/browser', [CloudConnectionController::class, 'browser'])->name('cloud.browser');

    Route::resource('tasks', TaskController::class);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::patch('/tasks/{task}/quick-action', [TaskController::class, 'quickAction'])->name('tasks.quick-action');
    Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store'])->name('tasks.subtasks.store');
    Route::patch('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'update'])->name('tasks.subtasks.update');
    Route::delete('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'destroy'])->name('tasks.subtasks.destroy');

    Route::resource('venues', VenueController::class);
    Route::post('/venues/quick-store', [VenueController::class, 'quickStore'])->name('venues.quick-store');
    Route::resource('contacts', ContactController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('plans', PlanController::class);
    Route::patch('/plans/{plan}/phases/{phase}/completion', [PlanController::class, 'updatePhaseCompletion'])->name('plans.phases.completion');

    Route::get('/fast-notes', [FastNoteController::class, 'index'])->name('fast-notes.index');
    Route::post('/fast-notes', [FastNoteController::class, 'store'])->name('fast-notes.store');
    Route::put('/fast-notes/{fastNote}', [FastNoteController::class, 'update'])->name('fast-notes.update');
    Route::patch('/fast-notes/{fastNote}/archive', [FastNoteController::class, 'archive'])->name('fast-notes.archive');
    Route::delete('/fast-notes/{fastNote}', [FastNoteController::class, 'destroy'])->name('fast-notes.destroy');

    Route::resource('events', EventController::class);
    Route::resource('shared-infos', SharedInfoController::class);
    Route::delete('/shared-infos/{sharedInfo}/documents/{document}', [SharedInfoDocumentController::class, 'destroy'])
        ->name('shared-infos.documents.destroy');
    Route::resource('users', UserController::class)->except(['show']);
    Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/types', [SettingsController::class, 'types'])->name('settings.pages.types');
    Route::get('/settings/types/venues', [SettingsController::class, 'venueTypes'])->name('settings.pages.types.venues');
    Route::get('/settings/types/events', [SettingsController::class, 'eventTypes'])->name('settings.pages.types.events');
    Route::get('/settings/categories', [SettingsController::class, 'categories'])->name('settings.pages.categories');
    Route::get('/settings/categories/venues', [SettingsController::class, 'venueCategories'])->name('settings.pages.categories.venues');
    Route::get('/settings/categories/shared-infos', [SettingsController::class, 'sharedInfoCategories'])->name('settings.pages.categories.shared-infos');
    Route::get('/settings/styles', [SettingsController::class, 'styles'])->name('settings.pages.styles');
    Route::get('/settings/content-platforms', [SettingsController::class, 'contentPlatforms'])->name('settings.pages.content-platforms');
    Route::get('/settings/task-statuses', [SettingsController::class, 'taskStatuses'])->name('settings.pages.task-statuses');
    Route::get('/settings/system', [SystemSettingController::class, 'index'])->name('settings.pages.system');
    Route::put('/settings/theme', [ThemeController::class, 'update'])->name('settings.theme');

    Route::resource('/settings/idea-types', IdeaTypeController::class)->only(['store', 'update', 'destroy'])->names('settings.idea-types');
    Route::resource('/settings/idea-categories', IdeaCategoryController::class)->only(['store', 'update', 'destroy'])->names('settings.idea-categories');
    Route::resource('/settings/venue-types', VenueTypeController::class)->only(['store', 'update', 'destroy'])->names('settings.venue-types');
    Route::resource('/settings/venue-categories', VenueCategoryController::class)->only(['store', 'update', 'destroy'])->names('settings.venue-categories');
    Route::resource('/settings/event-types', EventTypeController::class)->only(['store', 'update', 'destroy'])->names('settings.event-types');
    Route::resource('/settings/shared-info-categories', SharedInfoCategoryController::class)->only(['store', 'update', 'destroy'])->names('settings.shared-info-categories');
    Route::resource('/settings/content-styles', VenueStyleController::class)->only(['store', 'update', 'destroy'])->names('settings.content-styles');
    Route::resource('/settings/venue-styles', VenueStyleController::class)->only(['store', 'update', 'destroy'])->names('settings.venue-styles');
    Route::resource('/settings/content-platforms', ContentPlatformController::class)->only(['store', 'update', 'destroy'])->names('settings.content-platforms');
    Route::patch('/settings/task-statuses/reorder', [TaskStatusController::class, 'reorder'])->name('settings.task-statuses.reorder');
    Route::resource('/settings/task-statuses', TaskStatusController::class)->only(['store', 'update', 'destroy'])->names('settings.task-statuses');
    Route::post('/settings/system', [SystemSettingController::class, 'update'])->middleware('admin')->name('settings.system.update');
    Route::get('/settings/system/whatsapp/status', [SystemSettingController::class, 'whatsappStatus'])->middleware('admin')->name('settings.system.whatsapp.status');
    Route::get('/settings/system/whatsapp/qr', [SystemSettingController::class, 'whatsappQr'])->middleware('admin')->name('settings.system.whatsapp.qr');
    Route::post('/settings/system/whatsapp/disconnect', [SystemSettingController::class, 'whatsappDisconnect'])->middleware('admin')->name('settings.system.whatsapp.disconnect');
    Route::post('/settings/system/whatsapp/reconnect', [SystemSettingController::class, 'whatsappReconnect'])->middleware('admin')->name('settings.system.whatsapp.reconnect');
    Route::post('/settings/system/whatsapp/send-test', [SystemSettingController::class, 'whatsappSendTest'])->middleware('admin')->name('settings.system.whatsapp.send-test');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Docs / Tutorial
    Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
    Route::get('/docs/{slug}', [DocsController::class, 'show'])->name('docs.show');
    Route::get('/api/help-summary/{slug}', [DocsController::class, 'summary'])->name('docs.summary');

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
