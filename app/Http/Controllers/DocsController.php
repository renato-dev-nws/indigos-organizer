<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use League\CommonMark\CommonMarkConverter;

class DocsController extends Controller
{
    /** Map slug → module metadata (icon, label, markdown file) */
    private array $modules = [
        'tarefas'            => ['label' => 'Tarefas',             'icon' => 'mdi:checkbox-multiple-outline',   'slug' => 'tarefas'],
        'calendario'         => ['label' => 'Calendário',          'icon' => 'mdi:calendar-month-outline',      'slug' => 'calendario'],
        'ideias'             => ['label' => 'Ideias',              'icon' => 'mdi:lightbulb-multiple-outline',  'slug' => 'ideias'],
        'conteudos'          => ['label' => 'Conteúdos',           'icon' => 'mdi:film-reel',                   'slug' => 'conteudos'],
        'planejamentos'      => ['label' => 'Planejamentos',       'icon' => 'mdi:routes-clock',                'slug' => 'planejamentos'],
        'notas-rapidas'      => ['label' => 'Notas Rápidas',       'icon' => 'mdi:notebook-edit-outline',       'slug' => 'notas-rapidas'],
        'eventos'            => ['label' => 'Eventos',             'icon' => 'ph:ticket-bold',                  'slug' => 'eventos'],
        'locais'             => ['label' => 'Locais',              'icon' => 'mdi:map-marker-multiple-outline', 'slug' => 'locais'],
        'contatos'           => ['label' => 'Contatos',            'icon' => 'ph:address-book-bold',            'slug' => 'contatos'],
        'informacoes-uteis'  => ['label' => 'Informações Úteis',   'icon' => 'ph:info-bold',                    'slug' => 'informacoes-uteis'],
        'configuracoes'      => ['label' => 'Configurações',       'icon' => 'mdi:settings-outline',            'slug' => 'configuracoes'],
        'usuarios'           => ['label' => 'Usuários',            'icon' => 'ph:users-three-bold',             'slug' => 'usuarios'],
    ];

    public function index(): Response
    {
        $summaries = $this->loadSummaries();

        $cards = array_map(function (array $meta) use ($summaries) {
            $slug = $meta['slug'];
            return array_merge($meta, [
                'summary' => $summaries[$slug]['summary'] ?? '',
                'link'    => "/docs/{$slug}",
            ]);
        }, array_values($this->modules));

        return Inertia::render('Tutorial/Index', [
            'cards' => $cards,
        ]);
    }

    public function show(string $slug): Response
    {
        abort_unless(array_key_exists($slug, $this->modules), 404);

        $meta = $this->modules[$slug];
        $filePath = resource_path("markdown/docs/{$slug}.md");

        $html = '';
        if (File::exists($filePath)) {
            $converter = new CommonMarkConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
            $html = $converter->convert(File::get($filePath));
        }

        return Inertia::render('Tutorial/Show', [
            'module' => $meta,
            'content' => (string) $html,
            'modules' => array_values($this->modules),
        ]);
    }

    public function summary(string $slug): \Illuminate\Http\JsonResponse
    {
        $summaries = $this->loadSummaries();

        if (! isset($summaries[$slug])) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($summaries[$slug]);
    }

    private function loadSummaries(): array
    {
        $path = resource_path('lang/pt-BR/help_summaries.json');
        if (! File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?? [];
    }
}
