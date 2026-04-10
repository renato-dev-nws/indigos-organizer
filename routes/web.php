<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\ContentFileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\ThemeController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard', DashboardController::class);

    Route::resource('ideas', IdeaController::class);
    Route::post('/ideas/{idea}/execute', [IdeaController::class, 'execute'])->name('ideas.execute');

    Route::resource('contents', ContentController::class);
    Route::post('/contents/{content}/files', [ContentFileController::class, 'store'])->name('contents.files.store');
    Route::delete('/contents/{content}/files/{file}', [ContentFileController::class, 'destroy'])->name('contents.files.destroy');

    Route::resource('tasks', TaskController::class)->except(['show']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store'])->name('tasks.subtasks.store');
    Route::patch('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'update'])->name('tasks.subtasks.update');
    Route::delete('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'destroy'])->name('tasks.subtasks.destroy');

    Route::resource('venues', VenueController::class);

    Route::get('/settings', fn () => Inertia::render('Settings/Index'))->name('settings.index');
    Route::put('/settings/theme', [ThemeController::class, 'update'])->name('settings.theme');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
