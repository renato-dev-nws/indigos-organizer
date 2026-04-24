<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\Idea;
use App\Models\IdeaType;
use App\Models\Task;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $joao   = User::where('email', 'joao@demo.com')->firstOrFail();
        $paulo  = User::where('email', 'paulo@demo.com')->firstOrFail();
        $jorge  = User::where('email', 'jorge@demo.com')->firstOrFail();
        $bingo  = User::where('email', 'bingo@demo.com')->firstOrFail();

        $allUsers = collect([$joao, $paulo, $jorge, $bingo]);

        // Reference date: demo "today" is 2026-04-20
        $now = Carbon::create(2026, 4, 20);

        // ─────────────────────────────────────────────
        // Taxonomy helpers
        // ─────────────────────────────────────────────
        $ideaTypes      = \App\Models\IdeaType::whereNull('user_id')->get()->keyBy('name');
        $ideaCategories = \App\Models\IdeaCategory::whereNull('user_id')->get()->keyBy('name');
        $contentStyles  = \App\Models\VenueStyle::whereNull('user_id')->where('domain', \App\Models\VenueStyle::DOMAIN_CONTENT)->get()->keyBy('name');
        $platforms      = \App\Models\ContentPlatform::whereNull('user_id')->get()->keyBy('name');
        $taskStatuses   = \App\Models\TaskStatus::whereNull('user_id')->orderBy('order')->get()->keyBy('name');

        $statusPendente  = $taskStatuses->get('Pendente');
        $statusExecucao  = $taskStatuses->get('Em Execucao');
        $statusRevisao   = $taskStatuses->get('Aguardando Revisao');
        $statusConcluido = $taskStatuses->get('Concluido');

        // Fallback to first status if named ones not found
        $st = fn (string $name) => $taskStatuses->get($name)?->id ?? $taskStatuses->first()?->id;

        // ─────────────────────────────────────────────
        // IDEAS – Os Bisouros
        // ─────────────────────────────────────────────
        $ideasData = [
            // Paulo Macarti
            [
                'user' => $paulo,
                'title' => 'Refrão pra música da Jude',
                'description' => 'Acordei às 3h com um refrão na cabeça: "Nã-nã-nã-nã-nã-nã-nã". Simples. Poderoso. Talvez seja a coisa mais importante que já criei. Ou não. Guardando para avaliar com calma.',
                'status' => 'in_drawer',
                'type' => 'Produção musical',
                'categories' => ['Divulgação', 'Humor'],
            ],
            [
                'user' => $paulo,
                'title' => 'Álbum completamente solo',
                'description' => 'E se eu gravasse um álbum inteiro sozinho, tocando todos os instrumentos? A ideia existe. O João não sabe. Por enquanto é melhor assim.',
                'status' => 'in_drawer',
                'type' => 'Produção musical',
                'categories' => ['Marketing'],
            ],
            [
                'user' => $paulo,
                'title' => 'Show beneficente no Bangladesh',
                'description' => 'Organizar um mega-show para arrecadar fundos para refugiados. Convidar todo mundo. Inclusive o Jorge. Talvez não o Jorge.',
                'status' => 'on_board',
                'type' => 'Video',
                'categories' => ['Informativo', 'Divulgação'],
                'references' => [
                    ['title' => 'Concert for Bangladesh (1971)', 'url' => 'https://en.wikipedia.org/wiki/The_Concert_for_Bangladesh', 'description' => 'A inspiração original, meio que.'],
                ],
            ],
            [
                'user' => $paulo,
                'title' => 'Nome novo para a banda',
                'description' => 'E se a gente mudasse o nome para "Os Bisouros Voadores"? Só uma ideia. Está na mesa. O Bingo gostou. Isso é preocupante.',
                'status' => 'on_table',
                'type' => 'Identidade visual',
                'categories' => ['Marketing'],
            ],
            [
                'user' => $paulo,
                'title' => 'Música para o lado B que vira lado A',
                'description' => 'Compus em 20 minutos, era pra ser descartável. Suspeito que é melhor que tudo no álbum. Desenvolvendo.',
                'status' => 'executing',
                'type' => 'Produção musical',
                'categories' => ['Divulgação'],
            ],
            // João Leno
            [
                'user' => $joao,
                'title' => 'Levar minha esposa pra cantar no estúdio',
                'description' => 'Ela tem uma voz única. Experimental. Divisiva. A banda vai adorar. (A banda não vai adorar.) Mas é minha esposa e o estúdio é nosso.',
                'status' => 'on_board',
                'type' => 'Produção musical',
                'categories' => ['Humor'],
            ],
            [
                'user' => $joao,
                'title' => 'Paz e Amor — campanha global',
                'description' => 'Campanha mundial pedindo paz, amor e harmonia entre os povos. Fazer da nossa cama um quartel general. A esposa topou.',
                'status' => 'executing',
                'type' => 'Video',
                'categories' => ['Divulgação', 'Informativo'],
                'references' => [
                    ['title' => 'Bed-In for Peace', 'url' => 'https://en.wikipedia.org/wiki/Bed-in', 'description' => 'Referência histórica de protesto pacifista. Clássica.'],
                ],
            ],
            [
                'user' => $joao,
                'title' => 'Álbum duplo porque sim',
                'description' => 'Por que lançar um álbum quando dá pra lançar dois? São 30 faixas. O Paulo está calculando os custos. O Bingo está feliz. O Jorge acha que é ideia dele.',
                'status' => 'on_board',
                'type' => 'Produção musical',
                'categories' => ['Marketing'],
            ],
            [
                'user' => $joao,
                'title' => 'Documentário sobre minha infância em Liverpool',
                'description' => 'Contar minha história desde o começo. Já tenho 300 páginas escritas. Vai ser livro, filme e série. Simultaneamente.',
                'status' => 'on_table',
                'type' => 'Video',
                'categories' => ['História'],
            ],
            [
                'user' => $joao,
                'title' => 'Música de protesto sobre qualquer coisa',
                'description' => 'Acordo indignado toda manhã. A ideia é canalizar isso em música. Atualmente o protesto é contra o preço do chá.',
                'status' => 'executed',
                'type' => 'Produção musical',
                'categories' => ['Divulgação', 'Humor'],
            ],
            // Bingo Estrella
            [
                'user' => $bingo,
                'title' => 'Bateria com colheres de pau',
                'description' => 'E se eu trocasse as baquetas por colheres de pau numa apresentação ao vivo? Já testei. Funcionou. Ninguém notou diferença.',
                'status' => 'on_table',
                'type' => 'Video',
                'categories' => ['Humor'],
            ],
            [
                'user' => $bingo,
                'title' => 'Gravar álbum solo de bateria',
                'description' => 'Um álbum inteiro só de percussão. Sem voz, sem guitarra. 40 minutos de bumbo. Vai vender bem. Tenho certeza.',
                'status' => 'in_drawer',
                'type' => 'Produção musical',
                'categories' => ['Marketing'],
            ],
            [
                'user' => $bingo,
                'title' => 'Aprender a tocar bateria de verdade',
                'description' => 'Uma ideia radical que surgiu durante uma crise existencial. Estou avaliando se realmente vale a pena mudar algo que funciona.',
                'status' => 'executed',
                'type' => 'Produção musical',
                'categories' => ['Humor'],
            ],
            [
                'user' => $bingo,
                'title' => 'Mostrar que sou compositor também',
                'description' => 'Tenho uma melodia de 4 notas que é revolucionária. Estou desenvolvendo com muito cuidado e segredo.',
                'status' => 'executing',
                'type' => 'Produção musical',
                'categories' => ['Divulgação'],
            ],
            // Jorge Cleberson (trash & drawer)
            [
                'user' => $jorge,
                'title' => 'Álbum só de solos de guitarra',
                'description' => '12 faixas, cada uma um solo de 6 minutos. Sem vocal. Sem baixo. Só eu e a guitarra. O público está pronto para isso.',
                'status' => 'trash',
                'type' => 'Produção musical',
                'categories' => ['Marketing'],
            ],
            [
                'user' => $jorge,
                'title' => 'Rebatizar a banda como "Os Jorges"',
                'description' => 'Surgiu depois do terceiro chá. Seria uma homenagem à guitarra. Faz todo sentido.',
                'status' => 'trash',
                'type' => 'Identidade visual',
                'categories' => ['Marketing'],
            ],
            [
                'user' => $jorge,
                'title' => 'Fazer tour sem o João e o Paulo',
                'description' => 'A gente consegue tocar sem eles, né? (Spoiler: não consegue.)',
                'status' => 'trash',
                'type' => 'Video',
                'categories' => ['Humor'],
            ],
            [
                'user' => $jorge,
                'title' => 'Música de 18 minutos sobre um polvo',
                'description' => 'Tenho um afeto profundo por polvos. A música existe. São 18 minutos. Há sons de água. É arte.',
                'status' => 'trash',
                'type' => 'Produção musical',
                'categories' => ['Humor', 'História'],
            ],
            [
                'user' => $jorge,
                'title' => 'Convidar Éric Clápeton pra substituir o Paulo',
                'description' => 'O Paulo não é tão necessário assim. O Éric toparia. Tenho certeza.',
                'status' => 'trash',
                'type' => 'Produção musical',
                'categories' => ['Humor'],
            ],
            [
                'user' => $jorge,
                'title' => 'Vestir só branco em todos os shows',
                'description' => 'Visual coerente. Comunicação clara. Declaração estética. A banda vai entender com o tempo.',
                'status' => 'trash',
                'type' => 'Identidade visual',
                'categories' => ['Marketing'],
            ],
            [
                'user' => $jorge,
                'title' => 'Manifesto filosófico sobre música oriental',
                'description' => 'Li metade de um livro e já tenho uma corrente filosófica formada. São 18 páginas. Bem formatadas.',
                'status' => 'trash',
                'type' => 'Post',
                'categories' => ['Informativo', 'História'],
            ],
            [
                'user' => $jorge,
                'title' => 'Workshop "Como ser eu"',
                'description' => 'Posso ensinar as pessoas a alcançarem meu nível. É uma responsabilidade, mas estou disposto.',
                'status' => 'trash',
                'type' => 'Video',
                'categories' => ['Humor'],
            ],
            [
                'user' => $jorge,
                'title' => 'Usar sitar em todas as músicas do próximo álbum',
                'description' => 'Descobri o sitar. Vai entrar em tudo. A banda vai agradecer depois.',
                'status' => 'in_drawer',
                'type' => 'Produção musical',
                'categories' => ['Informativo'],
            ],
            [
                'user' => $jorge,
                'title' => 'Parceria com banda de raga indiana',
                'description' => 'Fusão entre o nosso rock e a música clássica indiana. É o futuro. Estou à frente do meu tempo, como sempre.',
                'status' => 'in_drawer',
                'type' => 'Produção musical',
                'categories' => ['Marketing', 'Série'],
            ],
        ];

        $createdIdeas = collect();
        foreach ($ideasData as $row) {
            $typeId = $ideaTypes->get($row['type'])?->id ?? $ideaTypes->first()?->id;
            $firstCategoryId = isset($row['categories'][0]) ? $ideaCategories->get($row['categories'][0])?->id : null;

            $idea = Idea::create([
                'user_id'          => $row['user']->id,
                'title'            => $row['title'],
                'description'      => $row['description'],
                'idea_type_id'     => $typeId,
                'idea_category_id' => $firstCategoryId,
                'status'           => $row['status'],
                'related_type'     => 'none',
                'is_private'       => false,
            ]);

            $categoryIds = collect($row['categories'] ?? [])
                ->map(fn ($name) => $ideaCategories->get($name)?->id)
                ->filter()
                ->values()
                ->all();
            if ($categoryIds) {
                $idea->categories()->sync($categoryIds);
            }

            if (!empty($row['references'])) {
                foreach ($row['references'] as $ref) {
                    $idea->references()->create($ref);
                }
            }

            if (!empty($row['styles'])) {
                $styleIds = collect($row['styles'])->map(fn ($s) => $contentStyles->get($s)?->id)->filter()->values()->all();
                if ($styleIds) {
                    $idea->styles()->sync($styleIds);
                }
            }

            $createdIdeas->push($idea);
        }

        // Pick a few ideas to connect to contents
        $ideaForContent = $createdIdeas->where('status', '!=', 'trash')->values();

        // ─────────────────────────────────────────────
        // CONTENTS – Os Bisouros
        // ─────────────────────────────────────────────
        $contentsData = [
            [
                'user' => $joao,
                'title' => 'João Leno e a Esposa em Casa',
                'status' => 'published',
                'planned_offset' => -25,
                'published_offset' => -20,
                'script' => '<p>Gravação intimista de João com sua esposa na sala de estar da mansão. Câmera trêmula, vibe documental, ela olhando pra câmera o tempo todo.</p><p>Abrir com plano da janela. Zoom lento para João ao piano. Esposa entra em cena cantarolando. Corte para close de mãos no piano. Plano final: os dois juntos no sofá.</p>',
                'type' => 'Video',
                'categories' => ['Divulgação', 'História'],
                'platforms' => ['YouTube', 'Instagram'],
                'styles' => ['Bastidores'],
            ],
            [
                'user' => $paulo,
                'title' => 'Chamada: Show do Telhado',
                'status' => 'in_production',
                'planned_offset' => 5,
                'published_offset' => null,
                'script' => '<p>Chamada urgente e emocionante para o público assistir o show histórico no telhado. "Vai acontecer. Não sabemos quando. Não sabemos como. Vai."</p><p>Plano: câmera olhando para cima do telhado. Corte rápido entre os quatro integrantes. Música crescente. Fade out com logo da banda.</p>',
                'type' => 'Reel',
                'categories' => ['Divulgação'],
                'platforms' => ['Instagram', 'TikTok'],
                'styles' => ['Lançamento'],
            ],
            [
                'user' => $paulo,
                'title' => 'Paulo no Piano — Estúdio',
                'status' => 'queued',
                'planned_offset' => 12,
                'published_offset' => null,
                'script' => '<p>Registro de Paulo Macarti tocando piano no estúdio durante a madrugada. Iluminação dramática. Nenhuma palavra. Só música e o som do aquecedor.</p>',
                'type' => 'Video',
                'categories' => ['Divulgação', 'Bastidores'],
                'platforms' => ['YouTube'],
                'styles' => ['Bastidores'],
            ],
            [
                'user' => $bingo,
                'title' => 'Como Bindo Nunca Aprendeu a Tocar (E Não Faz Diferença)',
                'status' => 'queued',
                'planned_offset' => 18,
                'published_offset' => null,
                'script' => '<p>Vídeo-documentário revelador sobre o mistério da bateria de Bindo Estrella. Inclui depoimentos de professores que ele nunca teve.</p><p>Entrevistas intercaladas. Cenas de Bindo tocando com enorme entusiasmo e zero técnica aparente. Final épico com música completa.</p>',
                'type' => 'Video',
                'categories' => ['Humor', 'História'],
                'platforms' => ['YouTube', 'TikTok'],
                'styles' => ['Bastidores'],
            ],
            [
                'user' => $jorge,
                'title' => 'As Composições de Jorge — Uma Jornada',
                'status' => 'cancelled',
                'planned_offset' => -10,
                'published_offset' => null,
                'script' => '<p>Mergulho profundo no universo criativo de Jorge Cleberson. Quantas músicas ele fez? Quantas entraram no álbum? Spoiler: veja o número de ideias no lixo.</p>',
                'type' => 'Video',
                'categories' => ['Humor'],
                'platforms' => ['YouTube'],
                'styles' => ['Bastidores'],
            ],
            [
                'user' => $paulo,
                'title' => 'Dia na Vida: Paulo Macarti',
                'status' => 'queued',
                'planned_offset' => 25,
                'published_offset' => null,
                'script' => '<p>Câmera segue Paulo por um dia inteiro. Acorda às 6h, compõe 3 músicas antes do café, resolve briga entre João e Jorge, toca 4 instrumentos. Dia normal.</p>',
                'type' => 'Video',
                'categories' => ['Bastidores', 'Divulgação'],
                'platforms' => ['YouTube', 'Instagram'],
                'styles' => ['Bastidores'],
            ],
            [
                'user' => $bingo,
                'title' => 'Bindo Toca Só com Uma Mão — Desafio',
                'status' => 'queued',
                'planned_offset' => 30,
                'published_offset' => null,
                'script' => '<p>Vídeo-desafio onde Bindo toca uma música inteira usando apenas a mão esquerda. Resultado surpreendente: ficou igual.</p>',
                'type' => 'Reel',
                'categories' => ['Humor'],
                'platforms' => ['TikTok', 'Instagram'],
                'styles' => ['Performance'],
            ],
            [
                'user' => $joao,
                'title' => 'João e Paulo: A Parceria',
                'status' => 'queued',
                'planned_offset' => 35,
                'published_offset' => null,
                'script' => '<p>Mini-documentário sobre a maior parceria criativa da música mundial. Inclui cenas de briga, reconciliação e mais briga. Final em aberto.</p>',
                'type' => 'Video',
                'categories' => ['História', 'Divulgação'],
                'platforms' => ['YouTube'],
                'styles' => ['Bastidores'],
            ],
            [
                'user' => $paulo,
                'title' => 'Dentro do Estúdio: Álbum Pistola',
                'status' => 'queued',
                'planned_offset' => 45,
                'published_offset' => null,
                'script' => '<p>Making-of das gravações do Pistola. 40% música, 60% reuniões tensas e silêncios constrangedores. Avaliação: 5 estrelas.</p>',
                'type' => 'Video',
                'categories' => ['Bastidores', 'História'],
                'platforms' => ['YouTube', 'Instagram'],
                'styles' => ['Bastidores'],
            ],
            [
                'user' => $joao,
                'title' => 'Paz e Amor: Manifesto em Vídeo',
                'status' => 'finalized',
                'planned_offset' => -15,
                'published_offset' => null,
                'script' => '<p>João e sua esposa gravam da cama uma mensagem de paz para o mundo. Dura 11 minutos. Ninguém pediu. Todos assistiram.</p>',
                'type' => 'Video',
                'categories' => ['Informativo', 'Divulgação'],
                'platforms' => ['YouTube'],
                'styles' => ['Lançamento'],
            ],
            [
                'user' => $paulo,
                'title' => 'Teaser Álbum Pistola — 15 segundos',
                'status' => 'in_production',
                'planned_offset' => 7,
                'published_offset' => null,
                'script' => '<p>15 segundos de áudio e imagem para antecipar o lançamento. Silêncio total e depois um riff. Fim.</p>',
                'type' => 'Reel',
                'categories' => ['Divulgação'],
                'platforms' => ['Instagram', 'TikTok'],
                'styles' => ['Lançamento'],
            ],
        ];

        $createdContents = collect();
        foreach ($contentsData as $i => $row) {
            $planned  = (clone $now)->addDays($row['planned_offset']);
            $published = ($row['status'] === 'published' && $row['published_offset'] !== null)
                ? (clone $now)->addDays($row['published_offset'])
                : null;

            $typeId = $ideaTypes->get($row['type'])?->id ?? $ideaTypes->first()?->id;

            $content = Content::create([
                'user_id'            => $row['user']->id,
                'idea_id'            => $ideaForContent[$i % $ideaForContent->count()]?->id,
                'title'              => $row['title'],
                'script'             => $row['script'],
                'idea_type_id'       => $typeId,
                'status'             => $row['status'],
                'planned_publish_at' => $planned,
                'published_at'       => $published,
            ]);

            $contentCategoryIds = collect($row['categories'] ?? [])
                ->map(fn ($name) => $ideaCategories->get($name)?->id)
                ->filter()->values()->all();
            if ($contentCategoryIds) {
                $content->categories()->sync($contentCategoryIds);
            }

            $platformIds = collect($row['platforms'] ?? [])
                ->map(fn ($name) => $platforms->get($name)?->id)
                ->filter()->values()->all();
            if ($platformIds) {
                $content->platforms()->sync($platformIds);
            }

            $styleIds = collect($row['styles'] ?? [])
                ->map(fn ($name) => $contentStyles->get($name)?->id)
                ->filter()->values()->all();
            if ($styleIds) {
                $content->styles()->sync($styleIds);
            }

            $createdContents->push($content);
        }

        // ─────────────────────────────────────────────
        // TASKS – standalone (not in plans)
        // ─────────────────────────────────────────────
        $standaloneTasksData = [
            // OVERDUE tasks
            [
                'title'       => 'Fechar rider técnico do Vibra São Paulo',
                'description' => 'Confirmar lista de equipamentos necessários: PA, monitor, backline. Prazo expirado. A produção já ligou 3 vezes.',
                'priority'    => 'urgent',
                'status'      => 'Pendente',
                'due_date'    => (clone $now)->subDays(10)->toDateString(),
                'scheduled'   => (clone $now)->subDays(12)->setTime(10, 0),
                'user'        => $paulo,
                'assignees'   => [$paulo->id, $joao->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Listar equipamentos necessários', 'completed' => true],
                    ['title' => 'Enviar rider para a produção', 'completed' => false],
                    ['title' => 'Confirmar recebimento', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Enviar presskit para festivais do 2º semestre',
                'description' => 'Bio, foto de alta resolução, release, links de vídeo e áudio. Prazo para submissão era semana passada.',
                'priority'    => 'high',
                'status'      => 'Pendente',
                'due_date'    => (clone $now)->subDays(5)->toDateString(),
                'scheduled'   => (clone $now)->subDays(7)->setTime(9, 0),
                'user'        => $joao,
                'assignees'   => [$joao->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Atualizar bio da banda', 'completed' => true],
                    ['title' => 'Selecionar fotos', 'completed' => true],
                    ['title' => 'Gravar vídeo de apresentação', 'completed' => false],
                    ['title' => 'Submeter formulário dos festivais', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Confirmar passagem de som no Espaço Unimed',
                'description' => 'Horário combinado era às 16h do dia 15. Ninguém foi. Produção irritada. Paulo precisa resolver isso hoje.',
                'priority'    => 'urgent',
                'status'      => 'Em Execucao',
                'due_date'    => (clone $now)->subDays(2)->toDateString(),
                'scheduled'   => (clone $now)->subDays(3)->setTime(14, 0),
                'user'        => $paulo,
                'assignees'   => [$paulo->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Ligar para a produção do Espaço Unimed', 'completed' => true],
                    ['title' => 'Reagendar passagem de som', 'completed' => false],
                ],
            ],
            // NEAR DEADLINE tasks (due in 1-3 days from April 20)
            [
                'title'       => 'Entregar fotos da sessão para o site',
                'description' => 'O fotógrafo Jimmie Pageant enviou as fotos da sessão. Precisam ser selecionadas, editadas e enviadas para o webmaster.',
                'priority'    => 'high',
                'status'      => 'Em Execucao',
                'due_date'    => (clone $now)->addDays(1)->toDateString(),
                'scheduled'   => (clone $now)->addDays(1)->setTime(17, 0),
                'user'        => $joao,
                'assignees'   => [$joao->id, $paulo->id],
                'related'     => 'content',
                'content_idx' => 0,
                'subtasks'    => [
                    ['title' => 'Selecionar 10 melhores fotos', 'completed' => true],
                    ['title' => 'Enviar para edição de cor', 'completed' => false],
                    ['title' => 'Entregar ao webmaster', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Responder propostas de produtores na caixa de entrada',
                'description' => 'Há 7 e-mails sem resposta de produtores interessados. Brian Épistola ligou perguntando se estamos vivos.',
                'priority'    => 'medium',
                'status'      => 'Pendente',
                'due_date'    => (clone $now)->addDays(2)->toDateString(),
                'scheduled'   => (clone $now)->addDays(2)->setTime(10, 0),
                'user'        => $paulo,
                'assignees'   => [$paulo->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Ler todos os e-mails', 'completed' => true],
                    ['title' => 'Priorizar propostas', 'completed' => false],
                    ['title' => 'Redigir respostas', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Postar chamada para show do Blue Note SP',
                'description' => 'Stories, feed e reel de chamada para o show da próxima semana. Precisa ir hoje para gerar antecipação.',
                'priority'    => 'high',
                'status'      => 'Pendente',
                'due_date'    => (clone $now)->addDays(3)->toDateString(),
                'scheduled'   => (clone $now)->addDays(3)->setTime(12, 0),
                'user'        => $joao,
                'assignees'   => [$joao->id, $bingo->id],
                'related'     => 'content',
                'content_idx' => 1,
                'subtasks'    => [
                    ['title' => 'Criar arte de chamada', 'completed' => false],
                    ['title' => 'Escrever legenda', 'completed' => false],
                    ['title' => 'Publicar em todas as plataformas', 'completed' => false],
                ],
            ],
            // Normal upcoming tasks
            [
                'title'       => 'Contratar segurança para o show do Vibra',
                'description' => 'Minimo de 4 seguranças. O produtor Sérgio Menendes indicou uma empresa confiável que trabalhou com Roberto Frippo.',
                'priority'    => 'medium',
                'status'      => 'Pendente',
                'due_date'    => (clone $now)->addDays(15)->toDateString(),
                'scheduled'   => (clone $now)->addDays(10)->setTime(14, 0),
                'user'        => $paulo,
                'assignees'   => [$paulo->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Solicitar orçamento', 'completed' => false],
                    ['title' => 'Assinar contrato', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Reunião com gravadora EMI-Bem sobre contrato do Pistola',
                'description' => 'Discutir royalties, prazo de entrega e distribuição internacional. Paulo precisa levar o advogado Donavan Leite.',
                'priority'    => 'high',
                'status'      => 'Aguardando Revisao',
                'due_date'    => (clone $now)->addDays(15)->toDateString(),
                'scheduled'   => (clone $now)->addDays(15)->setTime(14, 30),
                'user'        => $paulo,
                'assignees'   => [$joao->id, $paulo->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Preparar pauta da reunião', 'completed' => true],
                    ['title' => 'Revisar minutas do contrato com Donavan', 'completed' => false],
                    ['title' => 'Apresentar proposta de royalties', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Gravar videoclipe do single "Oi, Chude"',
                'description' => 'Diretora Björk Oliveira sugeriu locação no metrô às 5h da manhã. A banda ainda está avaliando se é uma boa ideia.',
                'priority'    => 'medium',
                'status'      => 'Pendente',
                'due_date'    => (clone $now)->addDays(25)->toDateString(),
                'scheduled'   => (clone $now)->addDays(20)->setTime(5, 0),
                'user'        => $joao,
                'assignees'   => [$joao->id, $paulo->id, $jorge->id, $bingo->id],
                'related'     => 'content',
                'content_idx' => 0,
                'subtasks'    => [
                    ['title' => 'Confirmar locação com a diretora', 'completed' => false],
                    ['title' => 'Definir figurinos', 'completed' => false],
                    ['title' => 'Reservar câmeras e equipe', 'completed' => false],
                    ['title' => 'Aviso prévio para a esposa do João', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Assinar contrato com Bourbon Street para outubro',
                'description' => 'Show confirmado, contrato pendente de assinatura. 3 apresentações confirmadas, pagamento adiantado.',
                'priority'    => 'high',
                'status'      => 'Aguardando Revisao',
                'due_date'    => (clone $now)->addDays(8)->toDateString(),
                'scheduled'   => (clone $now)->addDays(7)->setTime(11, 0),
                'user'        => $paulo,
                'assignees'   => [$paulo->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Revisar cláusulas com Donavan Leite', 'completed' => true],
                    ['title' => 'Assinar fisicamente', 'completed' => false],
                    ['title' => 'Enviar cópia para a gravadora', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Ensaio geral para o Festival Estoque de Madeira',
                'description' => 'Último ensaio antes do festival. Jorge prometeu não trazer o sitar desta vez.',
                'priority'    => 'high',
                'status'      => 'Pendente',
                'due_date'    => (clone $now)->addDays(12)->toDateString(),
                'scheduled'   => (clone $now)->addDays(12)->setTime(15, 0),
                'user'        => $joao,
                'assignees'   => [$joao->id, $paulo->id, $jorge->id, $bingo->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Reservar sala de ensaio', 'completed' => true],
                    ['title' => 'Definir setlist', 'completed' => false],
                    ['title' => 'Confirmar presença de todos', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Renovar seguro dos equipamentos da banda',
                'description' => 'Apólice vence semana que vem. Os amplificadores do João foram avaliados em R$ 45.000. O ego não tem cobertura.',
                'priority'    => 'medium',
                'status'      => 'Pendente',
                'due_date'    => (clone $now)->addDays(6)->toDateString(),
                'scheduled'   => (clone $now)->addDays(5)->setTime(9, 0),
                'user'        => $paulo,
                'assignees'   => [$paulo->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Contatar seguradora', 'completed' => false],
                    ['title' => 'Atualizar lista de equipamentos', 'completed' => false],
                ],
            ],
            [
                'title'       => 'Criar post sobre aniversário da banda',
                'description' => 'A banda completa 10 anos. O Jorge acha que foi ideia dele a criação. Os outros discordam.',
                'priority'    => 'low',
                'status'      => 'Concluido',
                'due_date'    => (clone $now)->subDays(15)->toDateString(),
                'scheduled'   => (clone $now)->subDays(16)->setTime(18, 0),
                'user'        => $bingo,
                'assignees'   => [$bingo->id],
                'related'     => 'content',
                'content_idx' => 9,
                'subtasks'    => [
                    ['title' => 'Pesquisar fotos antigas da banda', 'completed' => true],
                    ['title' => 'Criar carrossel comemorativo', 'completed' => true],
                    ['title' => 'Publicar no Instagram', 'completed' => true],
                ],
            ],
            [
                'title'       => 'Revisar mixagem da faixa "A Valsa da Esposa"',
                'description' => 'A esposa do João pediu para ouvir antes de lançar. O João pediu para Paulo resolver isso discretamente.',
                'priority'    => 'medium',
                'status'      => 'Em Execucao',
                'due_date'    => (clone $now)->addDays(4)->toDateString(),
                'scheduled'   => (clone $now)->addDays(4)->setTime(16, 0),
                'user'        => $paulo,
                'assignees'   => [$paulo->id, $joao->id],
                'related'     => 'administrative',
                'subtasks'    => [
                    ['title' => 'Exportar mixagem atual', 'completed' => true],
                    ['title' => 'Reunião de escuta com João', 'completed' => false],
                    ['title' => 'Aplicar ajustes finais', 'completed' => false],
                ],
            ],
        ];

        foreach ($standaloneTasksData as $taskRow) {
            $contentForTask = isset($taskRow['content_idx'])
                ? $createdContents->get($taskRow['content_idx'])
                : null;

            $task = Task::create([
                'user_id'        => $taskRow['user']->id,
                'related_type'   => $taskRow['related'],
                'content_id'     => $contentForTask?->id,
                'plan_id'        => null,
                'plan_phase_id'  => null,
                'title'          => $taskRow['title'],
                'description'    => $taskRow['description'],
                'task_status_id' => $st($taskRow['status']),
                'priority'       => $taskRow['priority'],
                'scheduled_for'  => $taskRow['scheduled'],
                'due_date'       => $taskRow['due_date'],
            ]);

            if (!empty($taskRow['assignees'])) {
                $task->assignedUsers()->sync($taskRow['assignees']);
            }

            foreach ($taskRow['subtasks'] ?? [] as $idx => $sub) {
                $task->subtasks()->create([
                    'title'     => $sub['title'],
                    'completed' => $sub['completed'],
                    'order'     => $idx + 1,
                ]);
            }
        }

        // ─────────────────────────────────────────────
        // FAST NOTES
        // ─────────────────────────────────────────────
        $fastNotesData = [
            [
                'user'         => $joao,
                'title'        => 'Ligar para Sérgio Menendes amanhã cedo',
                'related_type' => 'administrative',
                'is_priority'  => true,
                'note'         => 'Sérgio estava querendo discutir a agenda do 2o semestre. Ele falou em exclusividade com um festival. Não confirmar nada sem falar com Paulo primeiro.',
                'list_items'   => null,
            ],
            [
                'user'         => $paulo,
                'title'        => 'Letra inacabada: "Deixa o polvo entrar"',
                'related_type' => 'others',
                'is_priority'  => false,
                'note'         => "Verso 1:\nDeixa o polvo entrar\nEle só quer nadar\nNas águas do teu mar\nDe calamares e sonhar\n\n(Verso 2 ainda em construção — o Jorge não pode saber disso)",
                'list_items'   => null,
            ],
            [
                'user'         => $paulo,
                'title'        => 'Lista de mantimentos para o estúdio',
                'related_type' => 'others',
                'is_priority'  => true,
                'note'         => null,
                'list_items'   => [
                    ['text' => 'Chá de gengibre (para a garganta do João)', 'checked' => false],
                    ['text' => 'Biscoitos de mel (para o humor do Bingo)', 'checked' => true],
                    ['text' => 'Café forte (para as noites de mixagem)', 'checked' => false],
                    ['text' => 'Água com gás (estilo)', 'checked' => false],
                    ['text' => 'Curativos (para os dedos do Paulo)', 'checked' => true],
                    ['text' => 'Reservas de palhetas de guitarra', 'checked' => false],
                ],
            ],
            [
                'user'         => $joao,
                'title'        => 'Contatos importantes para o Festival Estoque de Madeira',
                'related_type' => 'administrative',
                'is_priority'  => false,
                'note'         => "Produção do festival: (11) 98765-4321\nDiretora artística: Yoko Onodera — yoko@estoquedemadeira.com.br\nPatrocinador master: Cervejaria Revolução Acoustic\n\nLembrar: eles precisam do rider até dia 25/04.",
                'list_items'   => null,
            ],
            [
                'user'         => $bingo,
                'title'        => 'Ideia de música sobre o processo judicial do síndico',
                'related_type' => 'others',
                'is_priority'  => false,
                'note'         => 'O síndico do edifício onde fizemos o show no telhado está nos processando. Isso merece uma música. Título provisório: "Hey Síndico". Guitarrada no início. Jorge vai querer 8 minutos de solo. Não deixar.',
                'list_items'   => null,
            ],
            [
                'user'         => $jorge,
                'title'        => 'Acordes do sitar que descobri',
                'related_type' => 'others',
                'is_priority'  => false,
                'note'         => 'Sa Re Ga Ma Pa Dha Ni — isso vai mudar tudo. Estou praticando às escondidas. Vou surpreender a banda no próximo ensaio. Eles vão agradecer.',
                'list_items'   => null,
            ],
            [
                'user'         => $paulo,
                'title'        => 'Checklist pré-show',
                'related_type' => 'tasks',
                'is_priority'  => true,
                'note'         => null,
                'list_items'   => [
                    ['text' => 'Confirmar horário de entrada com a produção', 'checked' => false],
                    ['text' => 'Checar bateria do DI do baixo', 'checked' => false],
                    ['text' => 'Testar monitor do palco', 'checked' => false],
                    ['text' => 'Confirmar setlist impressa com todos', 'checked' => false],
                    ['text' => 'Avisar João para não trazer a esposa (de novo)', 'checked' => false],
                    ['text' => 'Verificar se Bingo trouxe as baquetas certas (não as colheres)', 'checked' => false],
                    ['text' => 'Checar se Jorge está com o sitar na bagagem', 'checked' => false],
                ],
            ],
            [
                'user'         => $joao,
                'title'        => 'Observações da sessão de fotos com Jimmie Pageant',
                'related_type' => 'contents',
                'is_priority'  => false,
                'note'         => "Fotos ficaram ótimas. Usar foto #23 para capa do álbum.\nFoto #47 para o Instagram (não tem o Jorge nela — perfeita).\nFoto #88 enviar para a gravadora.\n\nO Jimmie cobrou 20% a mais porque o Bingo quebrou um refletor.",
                'list_items'   => null,
            ],
        ];

        foreach ($fastNotesData as $noteRow) {
            \App\Models\FastNote::create([
                'user_id'      => $noteRow['user']->id,
                'title'        => $noteRow['title'],
                'related_type' => $noteRow['related_type'],
                'note'         => $noteRow['note'],
                'list_items'   => $noteRow['list_items'],
                'is_priority'  => $noteRow['is_priority'],
            ]);
        }
    }
}
