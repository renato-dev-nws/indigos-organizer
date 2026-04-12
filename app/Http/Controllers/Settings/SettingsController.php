<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContentPlatform;
use App\Models\IdeaCategory;
use App\Models\IdeaType;
use App\Models\TaskStatus;
use App\Models\VenueCategory;
use App\Models\VenueStyle;
use App\Models\VenueType;
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
        $items = IdeaType::query()
            ->withCount(['ideas', 'contents'])
            ->orderBy('name')
            ->get()
            ->map(function (IdeaType $type) {
                $type->setAttribute('links_count', (int) $type->ideas_count + (int) $type->contents_count);

                return $type;
            });

        return Inertia::render('Settings/CrudPage', [
            'title' => 'Tipos',
            'description' => 'Tipos utilizados em ideias e conteúdos.',
            'items' => $items,
            'routeBase' => 'settings.idea-types',
            'withColor' => true,
            'withIcon' => true,
            'disableDeleteWhen' => 'links_count',
            'disableDeleteMessage' => 'Não é permitido excluir tipo com ideias ou conteúdos vinculados.',
            'tabs' => [
                ['label' => 'Ideias e Conteúdos', 'value' => 'ideas_contents'],
                ['label' => 'Locais', 'value' => 'venues'],
            ],
            'tabRoutes' => [
                'ideas_contents' => route('settings.pages.types'),
                'venues' => route('settings.pages.types.venues'),
            ],
            'activeTab' => 'ideas_contents',
        ]);
    }

    public function venueTypes(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Tipos',
            'description' => 'Tipos de locais.',
            'items' => VenueType::query()->withCount('venues')->orderBy('name')->get(),
            'routeBase' => 'settings.venue-types',
            'withColor' => true,
            'withIcon' => true,
            'disableDeleteWhen' => 'venues_count',
            'disableDeleteMessage' => 'Não é permitido excluir tipo com locais vinculados.',
            'tabs' => [
                ['label' => 'Ideias e Conteúdos', 'value' => 'ideas_contents'],
                ['label' => 'Locais', 'value' => 'venues'],
            ],
            'tabRoutes' => [
                'ideas_contents' => route('settings.pages.types'),
                'venues' => route('settings.pages.types.venues'),
            ],
            'activeTab' => 'venues',
        ]);
    }

    public function categories(): Response
    {
        $items = IdeaCategory::query()
            ->withCount(['ideas', 'contents'])
            ->orderBy('name')
            ->get()
            ->map(function (IdeaCategory $category) {
                $category->setAttribute('links_count', (int) $category->ideas_count + (int) $category->contents_count);

                return $category;
            });

        return Inertia::render('Settings/CrudPage', [
            'title' => 'Categorias',
            'description' => 'Categorias utilizadas em ideias e conteúdos.',
            'items' => $items,
            'routeBase' => 'settings.idea-categories',
            'withIcon' => true,
            'disableDeleteWhen' => 'links_count',
            'disableDeleteMessage' => 'Não é permitido excluir categoria com ideias ou conteúdos vinculados.',
            'tabs' => [
                ['label' => 'Ideias e Conteúdos', 'value' => 'ideas_contents'],
                ['label' => 'Locais', 'value' => 'venues'],
            ],
            'tabRoutes' => [
                'ideas_contents' => route('settings.pages.categories'),
                'venues' => route('settings.pages.categories.venues'),
            ],
            'activeTab' => 'ideas_contents',
        ]);
    }

    public function venueCategories(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Categorias',
            'description' => 'Categorias de locais.',
            'items' => VenueCategory::query()->withCount('venues')->orderBy('name')->get(),
            'routeBase' => 'settings.venue-categories',
            'withColor' => true,
            'withIcon' => true,
            'disableDeleteWhen' => 'venues_count',
            'disableDeleteMessage' => 'Não é permitido excluir categoria com locais vinculados.',
            'tabs' => [
                ['label' => 'Ideias e Conteúdos', 'value' => 'ideas_contents'],
                ['label' => 'Locais', 'value' => 'venues'],
            ],
            'tabRoutes' => [
                'ideas_contents' => route('settings.pages.categories'),
                'venues' => route('settings.pages.categories.venues'),
            ],
            'activeTab' => 'venues',
        ]);
    }

    public function styles(): Response
    {
        $activeTab = request('tab') === 'venues' ? 'venues' : 'content';

        if ($activeTab === 'venues') {
            $items = VenueStyle::query()
                ->where('domain', VenueStyle::DOMAIN_VENUES)
                ->withCount('venues')
                ->orderBy('name')
                ->get();

            return Inertia::render('Settings/CrudPage', [
                'title' => 'Estilos',
                'description' => 'Estilos de locais.',
                'items' => $items,
                'routeBase' => 'settings.venue-styles',
                'withColor' => true,
                'withIcon' => true,
                'disableDeleteWhen' => 'venues_count',
                'disableDeleteMessage' => 'Não é permitido excluir estilo com locais vinculados.',
                'tabs' => [
                    ['label' => 'Conteúdo', 'value' => 'content'],
                    ['label' => 'Locais', 'value' => 'venues'],
                ],
                'tabRoutes' => [
                    'content' => '/settings/styles?tab=content',
                    'venues' => '/settings/styles?tab=venues',
                ],
                'activeTab' => 'venues',
                'extraPayload' => ['domain' => VenueStyle::DOMAIN_VENUES],
            ]);
        }

        $items = VenueStyle::query()
            ->where('domain', VenueStyle::DOMAIN_CONTENT)
            ->withCount(['ideas', 'contents'])
            ->orderBy('name')
            ->get()
            ->map(function (VenueStyle $style) {
                $style->setAttribute('links_count', (int) $style->ideas_count + (int) $style->contents_count);

                return $style;
            });

        return Inertia::render('Settings/CrudPage', [
            'title' => 'Estilos',
            'description' => 'Estilos de conteúdo.',
            'items' => $items,
            'routeBase' => 'settings.content-styles',
            'withColor' => true,
            'withIcon' => true,
            'disableDeleteWhen' => 'links_count',
            'disableDeleteMessage' => 'Não é permitido excluir estilo com ideias ou conteúdos vinculados.',
            'tabs' => [
                ['label' => 'Conteúdo', 'value' => 'content'],
                ['label' => 'Locais', 'value' => 'venues'],
            ],
            'tabRoutes' => [
                'content' => '/settings/styles?tab=content',
                'venues' => '/settings/styles?tab=venues',
            ],
            'activeTab' => 'content',
            'extraPayload' => ['domain' => VenueStyle::DOMAIN_CONTENT],
        ]);
    }

    public function contentPlatforms(): Response
    {
        return Inertia::render('Settings/CrudPage', [
            'title' => 'Plataformas de conteúdo',
            'description' => 'Ex.: YouTube, Instagram, TikTok.',
            'items' => ContentPlatform::query()->withCount('contents')->orderBy('name')->get(),
            'routeBase' => 'settings.content-platforms',
            'withIcon' => true,
            'disableDeleteWhen' => 'contents_count',
            'disableDeleteMessage' => 'Não é permitido excluir plataforma com conteúdos vinculados.',
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
