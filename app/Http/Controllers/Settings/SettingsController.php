<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContentCategory;
use App\Models\ContentPlatform;
use App\Models\ContentType;
use App\Models\IdeaCategory;
use App\Models\IdeaType;
use App\Models\TaskStatus;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return $this->ideaTypes();
    }

    public function ideaTypes(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Tipos de ideia',
            'description' => 'Gerencie os tipos de ideia com nome e cor.',
            'items' => IdeaType::query()->withCount('ideas')->orderBy('name')->get(),
            'routeBase' => 'settings.idea-types',
            'withColor' => true,
            'disableDeleteWhen' => 'ideas_count',
            'disableDeleteMessage' => 'Não é permitido excluir tipo com ideias vinculadas.',
        ]);
    }

    public function ideaCategories(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Categorias de ideia',
            'description' => 'Categorias utilizadas no cadastro de ideias.',
            'items' => IdeaCategory::query()->orderBy('name')->get(),
            'routeBase' => 'settings.idea-categories',
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

    public function contentTypes(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Tipos de conteúdo',
            'description' => 'Ex.: Reel, Story, Clipe.',
            'items' => ContentType::query()->orderBy('name')->get(),
            'routeBase' => 'settings.content-types',
        ]);
    }

    public function contentCategories(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Categorias de conteúdo',
            'description' => 'Classificação de conteúdo com cores.',
            'items' => ContentCategory::query()->orderBy('name')->get(),
            'routeBase' => 'settings.content-categories',
            'withColor' => true,
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
            'reorderRoute' => 'settings.task-statuses.reorder',
            'disableDeleteWhen' => 'tasks_count',
            'disableDeleteMessage' => 'Não é permitido remover status com tarefas vinculadas.',
        ]);
    }
}
