<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContentPlatform;
use App\Models\IdeaCategory;
use App\Models\IdeaType;
use App\Models\TaskStatus;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return $this->types();
    }

    public function types(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Tipos',
            'description' => 'Tipos utilizados em ideias e conteúdos.',
            'items' => IdeaType::query()->withCount(['ideas', 'contents'])->orderBy('name')->get(),
            'routeBase' => 'settings.idea-types',
            'withColor' => true,
            'disableDeleteWhen' => 'ideas_count',
            'disableDeleteMessage' => 'Não é permitido excluir tipo com ideias vinculadas.',
        ]);
    }

    public function categories(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Categorias',
            'description' => 'Categorias utilizadas em ideias e conteúdos.',
            'items' => IdeaCategory::query()->withCount(['ideas', 'contents'])->orderBy('name')->get(),
            'routeBase' => 'settings.idea-categories',
            'disableDeleteWhen' => 'ideas_count',
            'disableDeleteMessage' => 'Não é permitido excluir categoria com ideias vinculadas.',
        ]);
    }

    public function contentPlatforms(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Plataformas de conteúdo',
            'description' => 'Ex.: YouTube, Instagram, TikTok.',
            'items' => ContentPlatform::query()->orderBy('name')->get(),
            'routeBase' => 'settings.content-platforms',
        ]);
    }

    public function taskStatuses(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Status de tarefas',
            'description' => 'Cadastre e reordene os status do Kanban.',
            'items' => TaskStatus::query()->withCount('tasks')->orderBy('order')->get(),
            'routeBase' => 'settings.task-statuses',
            'withColor' => true,
            'withOrder' => true,
            'reorderOnly' => true,
            'reorderRoute' => 'settings.task-statuses.reorder',
            'disableDeleteWhen' => 'tasks_count',
            'disableDeleteMessage' => 'Não é permitido remover status com tarefas vinculadas.',
        ]);
    }
}
