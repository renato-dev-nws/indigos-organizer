<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $joao  = User::where('email', 'joao@demo.com')->firstOrFail();
        $paulo = User::where('email', 'paulo@demo.com')->firstOrFail();
        $jorge = User::where('email', 'jorge@demo.com')->firstOrFail();
        $bingo = User::where('email', 'bingo@demo.com')->firstOrFail();

        $statuses = TaskStatus::whereNull('user_id')->orderBy('order')->get()->keyBy('name');
        $st = fn (string $name) => $statuses->get($name)?->id ?? $statuses->first()?->id;

        // Reference date: demo "today" is 2026-04-20
        $now = \Carbon\Carbon::create(2026, 4, 20);

        // ─────────────────────────────────────────────
        // PLANO 1 – Show no Telhado
        // ─────────────────────────────────────────────
        $planTelhado = Plan::updateOrCreate(
            ['user_id' => $joao->id, 'title' => 'Show no Telhado'],
            [
                'description' => 'Organizar, executar e registrar o lendário show improvisado no telhado do edifício da gravadora, que vai parar o trânsito, irritar os vizinhos e entrar para a história. Ou pelo menos para o YouTube.',
                'start_date'  => '2026-01-15',
                'end_date'    => '2026-06-30',
                'progress'    => 68,
                'status'      => 'running',
            ]
        );

        $phasesTelhado = [
            [
                'title'       => 'Planejamento e Logística do Telhado',
                'order'       => 1,
                'description' => 'Preparação completa para subir ao telhado sem morrer (nem preso).',
                'completed'=> true,
                'estimated_start_date' => '2026-01-15',
                'estimated_end_date'   => '2026-02-10',
                'tasks'       => [
                    ['title' => 'Verificar se o telhado aguenta o peso dos amplificadores (e do ego do João)', 'assignee' => $bingo, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(70)],
                    ['title' => 'Solicitar autorização do síndico do prédio', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(68)],
                    ['title' => 'Ignorar resposta do síndico e subir os equipamentos mesmo assim', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'urgent', 'due' => (clone $now)->subDays(65)],
                    ['title' => 'Contratar equipe de filmagem sem explicar o que vão filmar', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(63)],
                    ['title' => 'Definir setlist de músicas que ninguém ensaiou direito', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(60)],
                    ['title' => 'Avisar a polícia. Ou não.', 'assignee' => $bingo, 'status' => 'Concluido', 'priority' => 'low', 'due' => (clone $now)->subDays(58)],
                    ['title' => 'Comprar cobertores para os músicos (está frio lá em cima)', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(56)],
                    ['title' => 'Resolver briga entre João e Jorge sobre quem fica de frente para a câmera', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'urgent', 'due' => (clone $now)->subDays(54)],
                    ['title' => 'Testar microfones no telhado às 7h da manhã para acordar a vizinhança', 'assignee' => $bingo, 'status' => 'Concluido', 'priority' => 'low', 'due' => (clone $now)->subDays(52)],
                    ['title' => 'Fazer plano de fuga caso a polícia chegue antes da terceira música', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(50)],
                ],
            ],
            [
                'title'       => 'Execução do Show',
                'order'       => 2,
                'description' => 'O show em si. No telhado. Com frio. Sem permissão. Arte.',
                'completed'=> true,
                'estimated_start_date' => '2026-02-11',
                'estimated_end_date'   => '2026-02-28',
                'tasks'       => [
                    ['title' => 'Subir todos os equipamentos pelo elevador de serviço (3 viagens mínimo)', 'assignee' => $bingo, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(45)],
                    ['title' => 'Montar bateria no telhado sem fazer barulho (missão impossível)', 'assignee' => $bingo, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(44)],
                    ['title' => 'Aquecer a voz de João com chá de gengibre e reclamações', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(43)],
                    ['title' => 'Paulo tocar o baixo mesmo com os dedos congelando', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(43)],
                    ['title' => 'Jorge entrar atrasado no set com desculpa criativa', 'assignee' => $jorge, 'status' => 'Concluido', 'priority' => 'low', 'due' => (clone $now)->subDays(43)],
                    ['title' => 'Improvisar quando a corrente da guitarra do João partir', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'urgent', 'due' => (clone $now)->subDays(43)],
                    ['title' => 'Executar pelo menos 8 músicas antes da polícia chegar', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(43)],
                    ['title' => 'Negociar com o policial usando charme e "é arte, doutor"', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'urgent', 'due' => (clone $now)->subDays(43)],
                    ['title' => 'Encerrar o show com "Valeu, foi um prazer" e desaparecer', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(43)],
                ],
            ],
            [
                'title'       => 'Pós-show e Registro',
                'order'       => 3,
                'description' => 'Agora vem a parte chata: documentar, responder processo e ganhar a imortalidade.',
                'completed'=> false,
                'estimated_start_date' => '2026-03-01',
                'estimated_end_date'   => '2026-06-30',
                'tasks'       => [
                    ['title' => 'Editar o filme do show para ficar épico e não constrangedor', 'assignee' => $paulo, 'status' => 'Em Execucao', 'priority' => 'high', 'due' => (clone $now)->addDays(10)],
                    ['title' => 'Decidir se vai cortar a parte que Bindo tocou errado (não vai)', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(10)],
                    ['title' => 'Responder processo do síndico', 'assignee' => $paulo, 'status' => 'Em Execucao', 'priority' => 'urgent', 'due' => (clone $now)->addDays(5)],
                    ['title' => 'Escrever texto poético sobre "o fim de uma era" para a imprensa', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(20)],
                    ['title' => 'Fazer entrevistas separadas porque a banda mal se fala', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(15)],
                    ['title' => 'Lançar trilha sonora do show como álbum', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'high', 'due' => (clone $now)->addDays(30)],
                    ['title' => 'Fazer merchandising: camisetas "Eu estava no telhado (mentira)"', 'assignee' => $bingo, 'status' => 'Pendente', 'priority' => 'medium', 'due' => (clone $now)->addDays(45)],
                    ['title' => 'Subir vídeo do show na internet e fingir que foi sem querer', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'high', 'due' => (clone $now)->addDays(15)],
                    ['title' => 'Responder críticos que disseram que estava fora do tom', 'assignee' => $jorge, 'status' => 'Pendente', 'priority' => 'low', 'due' => (clone $now)->addDays(60)],
                    ['title' => 'Arquivar toda documentação do projeto como "patrimônio histórico"', 'assignee' => $bingo, 'status' => 'Pendente', 'priority' => 'low', 'due' => (clone $now)->addDays(90)],
                ],
            ],
        ];

        foreach ($phasesTelhado as $phaseData) {
            $phase = $planTelhado->phases()->updateOrCreate(
                ['title' => $phaseData['title']],
                [
                    'user_id'              => $joao->id,
                    'description'          => $phaseData['description'],
                    'order'                => $phaseData['order'],
                    'completed'         => $phaseData['completed'],
                    'estimated_start_date' => $phaseData['estimated_start_date'],
                    'estimated_end_date'   => $phaseData['estimated_end_date'],
                ]
            );

            foreach ($phaseData['tasks'] as $taskData) {
                $task = Task::updateOrCreate(
                    ['plan_phase_id' => $phase->id, 'title' => $taskData['title']],
                    [
                        'user_id'        => $taskData['assignee']->id,
                        'related_type'   => 'plan',
                        'plan_id'        => $planTelhado->id,
                        'content_id'     => null,
                        'description'    => null,
                        'task_status_id' => $st($taskData['status']),
                        'priority'       => $taskData['priority'],
                        'scheduled_for'  => null,
                        'due_date'       => $taskData['due']->toDateString(),
                    ]
                );
                $task->assignedUsers()->sync([$taskData['assignee']->id]);
            }
        }

        // ─────────────────────────────────────────────
        // PLANO 2 – Gravação do Álbum Pistola
        // ─────────────────────────────────────────────
        $planPistola = Plan::updateOrCreate(
            ['user_id' => $paulo->id, 'title' => 'Gravação do Álbum "Pistola"'],
            [
                'description' => 'Produzir, gravar, mixar e lançar o álbum "Pistola" — o mais ambicioso e conflituoso projeto da banda, com orquestrações, músicas de 8 minutos, solos que ninguém pediu e pelo menos uma faixa que só o Jorge gosta (que vai ser vetada).',
                'start_date'  => '2026-02-01',
                'end_date'    => '2026-12-15',
                'progress'    => 30,
                'status'      => 'running',
            ]
        );

        $phasesPistola = [
            [
                'title'        => 'Pré-produção e Composição',
                'order'        => 1,
                'description'  => 'A fase onde a criatividade encontra o caos. 47 músicas do João, 1 faixa de 8 minutos do Paulo, nenhuma do Jorge.',
                'completed' => true,
                'estimated_start_date' => '2026-02-01',
                'estimated_end_date'   => '2026-03-15',
                'tasks'        => [
                    ['title' => 'Reunião de composição que vira briga em 12 minutos', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(60)],
                    ['title' => 'João apresentar 47 músicas autorais e exigir que todas entrem', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(58)],
                    ['title' => 'Paulo compor faixa de 8 minutos "porque sim"', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(55)],
                    ['title' => 'Jorge propor músicas que serão educadamente rejeitadas', 'assignee' => $jorge, 'status' => 'Concluido', 'priority' => 'low', 'due' => (clone $now)->subDays(54)],
                    ['title' => 'Bindo pedir para cantar uma e todo mundo fingir que não ouviu', 'assignee' => $bingo, 'status' => 'Concluido', 'priority' => 'low', 'due' => (clone $now)->subDays(53)],
                    ['title' => 'Definir ordem das faixas com post-its na parede', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(50)],
                    ['title' => 'Contratar orquestra para as partes que a banda não sabe tocar', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(48)],
                    ['title' => 'Discutir nome do álbum por 3 semanas', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(40)],
                    ['title' => 'Aprovar capa do álbum sem consultar o Jorge (propositalmente)', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(38)],
                    ['title' => 'Alugar o estúdio mais caro de Londres porque "merece"', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(35)],
                ],
            ],
            [
                'title'        => 'Gravação Base',
                'order'        => 2,
                'description'  => 'As bases. O alicerce. Bindo vai precisar de mais takes do que deveria. Paulo vai precisar de menos. Jorge vai opinar sem ser pedido.',
                'completed' => false,
                'estimated_start_date' => '2026-03-16',
                'estimated_end_date'   => '2026-05-31',
                'tasks'        => [
                    ['title' => 'Gravar batida base de Bindo (previsão: 72 takes)', 'assignee' => $bingo, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(30)],
                    ['title' => 'Gravar baixo do Paulo (2 takes, perfeito, irritou todo mundo)', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'medium', 'due' => (clone $now)->subDays(28)],
                    ['title' => 'Gravar guitarra base do João enquanto o Jorge opina sem ser pedido', 'assignee' => $joao, 'status' => 'Concluido', 'priority' => 'high', 'due' => (clone $now)->subDays(25)],
                    ['title' => 'Gravar solo do Jorge que será cortado na mixagem', 'assignee' => $jorge, 'status' => 'Concluido', 'priority' => 'low', 'due' => (clone $now)->subDays(23)],
                    ['title' => 'Registrar momento em que Paulo e João brigam sobre acorde', 'assignee' => $paulo, 'status' => 'Concluido', 'priority' => 'low', 'due' => (clone $now)->subDays(22)],
                    ['title' => 'Gravar versão acústica "pra ter opção" (que vai virar a versão final)', 'assignee' => $joao, 'status' => 'Em Execucao', 'priority' => 'high', 'due' => (clone $now)->addDays(7)],
                    ['title' => 'Convidar Éric Clápeton pra tocar numa faixa sem contar pro Jorge', 'assignee' => $joao, 'status' => 'Em Execucao', 'priority' => 'medium', 'due' => (clone $now)->addDays(14)],
                    ['title' => 'Gravar faixa instrumental que ninguém entende mas todo mundo elogia', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'medium', 'due' => (clone $now)->addDays(21)],
                    ['title' => 'Regravar tudo que foi gravado na semana passada porque "estava errado"', 'assignee' => $joao, 'status' => 'Pendente', 'priority' => 'high', 'due' => (clone $now)->addDays(25)],
                    ['title' => 'Salvar os arquivos dessa vez (lembrar do ocorrido de março)', 'assignee' => $bingo, 'status' => 'Pendente', 'priority' => 'urgent', 'due' => (clone $now)->addDays(25)],
                ],
            ],
            [
                'title'        => 'Gravação de Vocais e Overdubs',
                'order'        => 3,
                'description'  => 'A fase dos vocais. João exige silêncio absoluto no estúdio. Jorge pede para cantar. Ninguém deixa.',
                'completed' => false,
                'estimated_start_date' => '2026-06-01',
                'estimated_end_date'   => '2026-08-31',
                'tasks'        => [
                    ['title' => 'Gravar vocal principal de João (silêncio absoluto no estúdio)', 'assignee' => $joao, 'status' => 'Em Execucao', 'priority' => 'high', 'due' => (clone $now)->addDays(45)],
                    ['title' => 'Gravar backing vocals do Paulo (vai deixar o João enciumado)', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'medium', 'due' => (clone $now)->addDays(50)],
                    ['title' => 'Tentar gravar vocal do Jorge (3 tentativas; resultado no lixo)', 'assignee' => $jorge, 'status' => 'Pendente', 'priority' => 'low', 'due' => (clone $now)->addDays(52)],
                    ['title' => 'Convencer Bindo a fazer percussão extra em vez de cantar', 'assignee' => $bingo, 'status' => 'Pendente', 'priority' => 'medium', 'due' => (clone $now)->addDays(55)],
                    ['title' => 'Gravar orquestra de cordas para faixa "A Valsa da Esposa"', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'high', 'due' => (clone $now)->addDays(60)],
                    ['title' => 'Adicionar efeito de galinha em "A Fazenda do Bisouros" (ideia do Bindo; funcionou)', 'assignee' => $bingo, 'status' => 'Pendente', 'priority' => 'low', 'due' => (clone $now)->addDays(63)],
                    ['title' => 'Gravar faixa B-side que vai ser melhor que o álbum inteiro', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'medium', 'due' => (clone $now)->addDays(70)],
                    ['title' => 'Gravar faixa bônus japonesa (que o Japão vai adorar mais que o álbum)', 'assignee' => $joao, 'status' => 'Pendente', 'priority' => 'low', 'due' => (clone $now)->addDays(75)],
                    ['title' => 'A esposa do João participar de uma faixa com "performance vocal experimental"', 'assignee' => $joao, 'status' => 'Pendente', 'priority' => 'medium', 'due' => (clone $now)->addDays(80)],
                ],
            ],
            [
                'title'        => 'Mixagem, Masterização e Lançamento',
                'order'        => 4,
                'description'  => '400 versões de mixagem. A escolhida vai ser a do dia 2. E o João vai pedir uma alteração de última hora.',
                'completed' => false,
                'estimated_start_date' => '2026-09-01',
                'estimated_end_date'   => '2026-12-15',
                'tasks'        => [
                    ['title' => 'Mixagem: 3 semanas, 400 versões, escolher a do dia 2', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'high', 'due' => (clone $now)->addDays(140)],
                    ['title' => 'Masterização com produtor que discorda de tudo mas faz mesmo assim', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'high', 'due' => (clone $now)->addDays(155)],
                    ['title' => 'Aprovar versão final (João vai pedir uma alteração no último minuto)', 'assignee' => $joao, 'status' => 'Pendente', 'priority' => 'high', 'due' => (clone $now)->addDays(162)],
                    ['title' => 'Enviar para gravadora com carta formal pedindo para "confiar no processo"', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'medium', 'due' => (clone $now)->addDays(165)],
                    ['title' => 'Fotografar capa: 4 integrantes cruzando a rua em fila (ideia genial e simples)', 'assignee' => $joao, 'status' => 'Pendente', 'priority' => 'medium', 'due' => (clone $now)->addDays(168)],
                    ['title' => 'Vazar acidentalmente uma faixa no rádio e fingir que foi estratégia', 'assignee' => $bingo, 'status' => 'Pendente', 'priority' => 'low', 'due' => (clone $now)->addDays(175)],
                    ['title' => 'Entrevista coletiva onde a banda anuncia que está "de férias indefinidas"', 'assignee' => $joao, 'status' => 'Pendente', 'priority' => 'medium', 'due' => (clone $now)->addDays(178)],
                    ['title' => 'Lançar álbum e vender 1 milhão de cópias na primeira semana', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'urgent', 'due' => (clone $now)->addDays(180)],
                    ['title' => 'Ler crítica do Jorge no jornal (ele disse que o álbum "tem potencial")', 'assignee' => $joao, 'status' => 'Pendente', 'priority' => 'low', 'due' => (clone $now)->addDays(182)],
                    ['title' => 'Arquivar fitas originais em cofre e nunca mais falar sobre o processo', 'assignee' => $joao, 'status' => 'Pendente', 'priority' => 'low', 'due' => (clone $now)->addDays(190)],
                ],
            ],
        ];

        foreach ($phasesPistola as $phaseData) {
            $phase = $planPistola->phases()->updateOrCreate(
                ['title' => $phaseData['title']],
                [
                    'user_id'              => $paulo->id,
                    'description'          => $phaseData['description'],
                    'order'                => $phaseData['order'],
                    'completed'         => $phaseData['completed'],
                    'estimated_start_date' => $phaseData['estimated_start_date'],
                    'estimated_end_date'   => $phaseData['estimated_end_date'],
                ]
            );

            foreach ($phaseData['tasks'] as $taskData) {
                $task = Task::updateOrCreate(
                    ['plan_phase_id' => $phase->id, 'title' => $taskData['title']],
                    [
                        'user_id'        => $taskData['assignee']->id,
                        'related_type'   => 'plan',
                        'plan_id'        => $planPistola->id,
                        'content_id'     => null,
                        'description'    => null,
                        'task_status_id' => $st($taskData['status']),
                        'priority'       => $taskData['priority'],
                        'scheduled_for'  => null,
                        'due_date'       => $taskData['due']->toDateString(),
                    ]
                );
                $task->assignedUsers()->sync([$taskData['assignee']->id]);
            }
        }
    }
}
