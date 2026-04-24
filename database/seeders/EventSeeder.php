<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $joao  = User::where('email', 'joao@demo.com')->firstOrFail();
        $paulo = User::where('email', 'paulo@demo.com')->firstOrFail();
        $jorge = User::where('email', 'jorge@demo.com')->firstOrFail();
        $bingo = User::where('email', 'bingo@demo.com')->firstOrFail();

        $eventTypes = EventType::whereNull('user_id')->get()->keyBy('name');
        $venues     = Venue::orderBy('name')->get()->keyBy('name');
        $statuses   = TaskStatus::whereNull('user_id')->orderBy('order')->get()->keyBy('name');

        $typeShow     = $eventTypes->get('Show');
        $typeFestival = $eventTypes->get('Festival');
        $typeWorkshop = $eventTypes->get('Workshop');
        $st = fn (string $name) => $statuses->get($name)?->id ?? $statuses->first()?->id;

        // Reference date: demo "today" is 2026-04-20
        $now = Carbon::create(2026, 4, 20);

        $eventsData = [
            // ── PARTICIPANTE (Os Bisouros tocando) ──────────────────────
            [
                'user'            => $joao,
                'title'           => 'Os Bisouros — Show no Telhado (Apresentação Histórica)',
                'type'            => $typeShow,
                'venue'           => 'Espaço Unimed',
                'attendance_mode' => 'participant',
                'is_online'       => false,
                'event_date'      => (clone $now)->subDays(20)->toDateString(),
                'event_time'      => '18:00',
                'end_date'        => (clone $now)->subDays(20)->toDateString(),
                'end_time'        => '22:00',
                'description'     => 'O show mais falado (e processado judicialmente) da história da banda. No telhado. No frio. Com câmeras. Sem permissão. Com arte.',
                'ticket_price_first_batch'  => null,
                'ticket_price_door'         => null,
                'ticket_link'               => null,
                'extraInfos' => [
                    ['title' => 'Entrada', 'information' => 'Acesso pelo elevador de serviço. Portão lateral. Não use o principal.', 'order' => 1],
                    ['title' => 'Dress Code', 'information' => 'Agasalho obrigatório. Está frio no telhado.', 'order' => 2],
                    ['title' => 'Aviso Legal', 'information' => 'A banda não se responsabiliza por processos do síndico.', 'order' => 3],
                ],
                'links' => [
                    ['title' => 'Vídeo no YouTube (foi sem querer)', 'url' => 'https://youtube.com/watch?v=bisouros-telhado'],
                    ['title' => 'Reportagem no jornal', 'url' => 'https://example.com/bisouros-telhado-policia'],
                ],
                'tasks' => [
                    ['title' => 'Montar e desmontar todo o equipamento do telhado', 'assignee' => $bingo, 'status' => 'Concluido', 'priority' => 'high'],
                ],
            ],
            [
                'user'            => $paulo,
                'title'           => 'Os Bisouros — Blue Note SP',
                'type'            => $typeShow,
                'venue'           => 'Blue Note SP',
                'attendance_mode' => 'participant',
                'is_online'       => false,
                'event_date'      => (clone $now)->addDays(5)->toDateString(),
                'event_time'      => '21:00',
                'end_date'        => (clone $now)->addDays(5)->toDateString(),
                'end_time'        => '23:30',
                'description'     => 'Show acústico especial no lendário Blue Note São Paulo. A banda prometeu não brigar no palco desta vez. Paulo está otimista.',
                'ticket_price_first_batch'  => 80.00,
                'ticket_price_second_batch' => 100.00,
                'ticket_price_door'         => 120.00,
                'ticket_link'               => 'https://bluenotejazz.com/saopaulo/bisouros',
                'extraInfos' => [
                    ['title' => 'Abertura da casa', 'information' => 'Portões abrem às 20h para jantar.', 'order' => 1],
                    ['title' => 'Setlist', 'information' => 'Show acústico com músicas do álbum Pistola e clássicos da carreira.', 'order' => 2],
                    ['title' => 'Convidado especial', 'information' => 'Artista convidado a ser confirmado (não é o Jorge).', 'order' => 3],
                ],
                'links' => [
                    ['title' => 'Comprar ingressos', 'url' => 'https://bluenotejazz.com/saopaulo/bisouros'],
                    ['title' => 'Evento no Instagram', 'url' => 'https://instagram.com/p/bisouros-bluenote'],
                ],
                'tasks' => [
                    ['title' => 'Preparar setlist acústico para o Blue Note', 'assignee' => $paulo, 'status' => 'Em Execucao', 'priority' => 'high'],
                    ['title' => 'Confirmar rider técnico com a produção', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'high'],
                ],
            ],
            [
                'user'            => $joao,
                'title'           => 'Os Bisouros — Vibra São Paulo',
                'type'            => $typeShow,
                'venue'           => 'Vibra São Paulo',
                'attendance_mode' => 'participant',
                'is_online'       => false,
                'event_date'      => (clone $now)->addDays(25)->toDateString(),
                'event_time'      => '20:00',
                'end_date'        => (clone $now)->addDays(25)->toDateString(),
                'end_time'        => '23:59',
                'description'     => 'O maior show da carreira dos Bisouros. Vibra SP, 15 mil pessoas. Paulo já está ensaiando o discurso de encerramento há 3 semanas.',
                'ticket_price_first_batch'  => 120.00,
                'ticket_price_second_batch' => 150.00,
                'ticket_price_third_batch'  => 180.00,
                'ticket_price_door'         => 220.00,
                'ticket_link'               => 'https://ticketmaster.com.br/bisouros-vibra',
                'extraInfos' => [
                    ['title' => 'Abertura de casa', 'information' => 'Portões abrem às 19h.', 'order' => 1],
                    ['title' => 'Abertura', 'information' => 'Suzi Quartzo abre o show às 20h.', 'order' => 2],
                    ['title' => 'Os Bisouros', 'information' => 'Sobem ao palco às 21h30.', 'order' => 3],
                    ['title' => 'Produção', 'information' => 'Show com cenografia especial, telões e pyrotecnia.', 'order' => 4],
                ],
                'links' => [
                    ['title' => 'Ingressos — Ticketmaster', 'url' => 'https://ticketmaster.com.br/bisouros-vibra'],
                    ['title' => 'Trailer do show', 'url' => 'https://youtube.com/watch?v=bisouros-vibra-trailer'],
                ],
                'tasks' => [
                    ['title' => 'Confirmar rider técnico completo (linha de produção)', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'urgent'],
                    ['title' => 'Ensaio geral com backline do Vibra', 'assignee' => $joao, 'status' => 'Pendente', 'priority' => 'high'],
                    ['title' => 'Contratar segurança (mínimo 10 pessoas)', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'high'],
                ],
            ],
            [
                'user'            => $joao,
                'title'           => 'Os Bisouros — Festival Estoque de Madeira',
                'type'            => $typeFestival,
                'venue'           => 'Festival Estoque de Madeira',
                'attendance_mode' => 'participant',
                'is_online'       => false,
                'event_date'      => (clone $now)->addDays(13)->toDateString(),
                'event_time'      => '22:30',
                'end_date'        => (clone $now)->addDays(13)->toDateString(),
                'end_time'        => '00:30',
                'description'     => 'Os Bisouros no slot principal do palco 2 do Festival Estoque de Madeira. Jorge pediu para ser headliner. Todos ignoraram.',
                'ticket_price_first_batch'  => 60.00,
                'ticket_price_second_batch' => 80.00,
                'ticket_price_door'         => 100.00,
                'ticket_link'               => 'https://estoquedemadeira.com.br/ingressos',
                'extraInfos' => [
                    ['title' => 'Palco', 'information' => 'Palco 2 — área central do festival.', 'order' => 1],
                    ['title' => 'Horário de entrada da equipe', 'information' => 'Equipe técnica deve chegar às 18h para montagem.', 'order' => 2],
                ],
                'links' => [
                    ['title' => 'Site do festival', 'url' => 'https://estoquedemadeira.com.br'],
                ],
                'tasks' => [
                    ['title' => 'Enviar rider técnico para o festival (ATRASADO)', 'assignee' => $paulo, 'status' => 'Pendente', 'priority' => 'urgent'],
                ],
            ],
            [
                'user'            => $paulo,
                'title'           => 'Os Bisouros — Vivo Rio',
                'type'            => $typeShow,
                'venue'           => 'Vivo Rio',
                'attendance_mode' => 'participant',
                'is_online'       => false,
                'event_date'      => (clone $now)->addDays(55)->toDateString(),
                'event_time'      => '21:00',
                'end_date'        => (clone $now)->addDays(55)->toDateString(),
                'end_time'        => '23:30',
                'description'     => 'Primeira vez dos Bisouros no Vivo Rio. Com vista para a Baía de Guanabara. Jorge pediu para tocar de frente para o Cristo. A produção disse que não é possível. Jorge insiste.',
                'ticket_price_first_batch'  => 110.00,
                'ticket_price_second_batch' => 140.00,
                'ticket_price_door'         => 170.00,
                'ticket_link'               => 'https://vivorio.com.br/bisouros',
                'extraInfos' => [
                    ['title' => 'Vista', 'information' => 'Janelas para a Baía de Guanabara durante o show.', 'order' => 1],
                    ['title' => 'Dica', 'information' => 'Chegue cedo para garantir a mesa com melhor vista.', 'order' => 2],
                ],
                'links' => [
                    ['title' => 'Ingressos Vivo Rio', 'url' => 'https://vivorio.com.br/bisouros'],
                ],
                'tasks' => [],
            ],
            [
                'user'            => $paulo,
                'title'           => 'Os Bisouros — Festival Montreux Brasil',
                'type'            => $typeFestival,
                'venue'           => 'Festival Montreux Brasil',
                'attendance_mode' => 'participant',
                'is_online'       => false,
                'event_date'      => (clone $now)->addDays(82)->toDateString(),
                'event_time'      => '20:00',
                'end_date'        => (clone $now)->addDays(82)->toDateString(),
                'end_time'        => '22:00',
                'description'     => 'Os Bisouros no Montreux Brasil. Paulo propõe incluir elementos de jazz no setlist. João diz que "é uma banda de rock". Isso vai demorar 3 semanas para resolver.',
                'ticket_price_first_batch'  => 180.00,
                'ticket_price_second_batch' => 220.00,
                'ticket_price_door'         => 260.00,
                'ticket_link'               => 'https://montreuxbrasil.com.br/bisouros',
                'extraInfos' => [
                    ['title' => 'Dress Code', 'information' => 'Elegante casual. Jorge vai de branco de qualquer jeito.', 'order' => 1],
                ],
                'links' => [
                    ['title' => 'Site do festival', 'url' => 'https://montreuxbrasil.com.br'],
                ],
                'tasks' => [],
            ],

            // ── AUDIÊNCIA (Os Bisouros assistindo) ──────────────────────
            [
                'user'            => $jorge,
                'title'           => 'Palestra: Roberto Frippo — Guitarra e o Cosmos',
                'type'            => $typeWorkshop,
                'venue'           => 'Sesc Pompeia',
                'attendance_mode' => 'audience',
                'is_online'       => false,
                'event_date'      => (clone $now)->subDays(2)->toDateString(),
                'event_time'      => '19:30',
                'end_date'        => (clone $now)->subDays(2)->toDateString(),
                'end_time'        => '22:00',
                'description'     => 'Roberto Frippo fala sobre técnica de guitarra, minimalismo sonoro e o universo. Jorge foi e voltou convicto de que já sabia de tudo. Paulo voltou com 3 páginas de anotações.',
                'ticket_price_first_batch'  => 45.00,
                'ticket_price_door'         => 60.00,
                'ticket_link'               => 'https://sesc.com.br/roberto-frippo',
                'extraInfos' => [
                    ['title' => 'Formato', 'information' => 'Palestra com demonstração ao vivo e sessão de perguntas.', 'order' => 1],
                    ['title' => 'Livro', 'information' => 'Roberto Frippo lança "O Vazio Sonoro" no evento.', 'order' => 2],
                ],
                'links' => [
                    ['title' => 'Página do evento', 'url' => 'https://sesc.com.br/roberto-frippo'],
                ],
                'tasks' => [],
            ],
            [
                'user'            => $joao,
                'title'           => 'Show: Jefferson Beck Experience',
                'type'            => $typeShow,
                'venue'           => 'Vibra São Paulo',
                'attendance_mode' => 'audience',
                'is_online'       => false,
                'event_date'      => (clone $now)->addDays(8)->toDateString(),
                'event_time'      => '21:00',
                'end_date'        => (clone $now)->addDays(8)->toDateString(),
                'end_time'        => '23:30',
                'description'     => 'Tributo oficial ao lendário guitarrista. Paulo quer ir por "razões técnicas". João quer ir por admiração. Bingo quer ir porque "rola um pós-show na zona sul". Jorge não foi convidado.',
                'ticket_price_first_batch'  => 150.00,
                'ticket_price_door'         => 200.00,
                'ticket_link'               => 'https://ticketmaster.com.br/jefferson-beck-exp',
                'extraInfos' => [
                    ['title' => 'Abertura', 'information' => 'Tributo com músicos renomados do circuito.', 'order' => 1],
                ],
                'links' => [
                    ['title' => 'Ingressos', 'url' => 'https://ticketmaster.com.br/jefferson-beck-exp'],
                ],
                'tasks' => [],
            ],
            [
                'user'            => $paulo,
                'title'           => 'Show: Éric Clápeton — Turnê de Despedida (3ª edição)',
                'type'            => $typeShow,
                'venue'           => 'Circo Voador',
                'attendance_mode' => 'audience',
                'is_online'       => false,
                'event_date'      => (clone $now)->addDays(30)->toDateString(),
                'event_time'      => '20:30',
                'end_date'        => (clone $now)->addDays(30)->toDateString(),
                'end_time'        => '23:00',
                'description'     => 'Terceira turnê de despedida de Éric Clápeton. Desta vez ele promete que é a última. Paulo acha que não é. João quer pedir para ele tocar numa faixa do Pistola. Jorge ainda não sabe que Éric gravou com os Bisouros.',
                'ticket_price_first_batch'  => 300.00,
                'ticket_price_second_batch' => 380.00,
                'ticket_price_door'         => 450.00,
                'ticket_link'               => 'https://ticketmaster.com.br/eric-clapeton',
                'extraInfos' => [
                    ['title' => 'Atenção', 'information' => 'Não mencionar para Jorge que Éric gravou com os Bisouros.', 'order' => 1],
                ],
                'links' => [
                    ['title' => 'Ingressos', 'url' => 'https://ticketmaster.com.br/eric-clapeton'],
                ],
                'tasks' => [],
            ],
            [
                'user'            => $joao,
                'title'           => 'Sarau Beatnik na Livraria Garagem',
                'type'            => $typeWorkshop,
                'venue'           => 'Grazie a Dio!',
                'attendance_mode' => 'audience',
                'is_online'       => false,
                'event_date'      => (clone $now)->subDays(8)->toDateString(),
                'event_time'      => '20:00',
                'end_date'        => (clone $now)->subDays(8)->toDateString(),
                'end_time'        => '23:00',
                'description'     => 'Noite de poesia beat, jazz e literatura. João foi inspirado. Voltou com 12 novos versos. Metade sobre paz. Metade sobre a esposa. Toda a banda estava lá exceto Jorge que "chegou na hora certa" para a última leitura.',
                'ticket_price_first_batch'  => null,
                'ticket_price_door'         => 20.00,
                'ticket_link'               => null,
                'extraInfos' => [],
                'links' => [],
                'tasks' => [],
            ],
            [
                'user'            => $paulo,
                'title'           => 'Show: Suzi Quartzo — EP de Estreia',
                'type'            => $typeShow,
                'venue'           => 'Bourbon Street',
                'attendance_mode' => 'audience',
                'is_online'       => false,
                'event_date'      => (clone $now)->subDays(15)->toDateString(),
                'event_time'      => '22:00',
                'end_date'        => (clone $now)->subDays(15)->toDateString(),
                'end_time'        => '00:00',
                'description'     => 'Lançamento ao vivo do EP de estreia de Suzi Quartzo, artista que abre shows dos Bisouros. Paulo foi para "fazer networking". João foi para apoiar. Bingo foi porque gosta de Suzi.',
                'ticket_price_first_batch'  => 40.00,
                'ticket_price_door'         => 55.00,
                'ticket_link'               => 'https://example.com/suzi-quartzo-ep',
                'extraInfos' => [
                    ['title' => 'Lançamento', 'information' => 'EP "Quilométrica" disponível em todas as plataformas.', 'order' => 1],
                ],
                'links' => [
                    ['title' => 'Pré-save no Spotify', 'url' => 'https://open.spotify.com/suziquartzo'],
                ],
                'tasks' => [],
            ],
            // ── ONLINE ────────────────────────────────────────────────
            [
                'user'            => $paulo,
                'title'           => 'Webinar: Produção Musical Independente no Brasil',
                'type'            => $typeWorkshop,
                'venue'           => null,
                'attendance_mode' => 'audience',
                'is_online'       => true,
                'event_date'      => (clone $now)->addDays(10)->toDateString(),
                'event_time'      => '19:00',
                'end_date'        => (clone $now)->addDays(10)->toDateString(),
                'end_time'        => '21:00',
                'description'     => 'Workshop online com principais produtores independentes. Tópicos: distribuição digital, licenciamento, contratos e como não ser enganado pela gravadora. Paulo vai com caderninho.',
                'ticket_price_first_batch'  => null,
                'ticket_price_door'         => null,
                'ticket_link'               => 'https://youtube.com/live/producaoindependente',
                'extraInfos' => [
                    ['title' => 'Plataforma', 'information' => 'YouTube Live — link público.', 'order' => 1],
                    ['title' => 'Certificado', 'information' => 'Certificado de participação para quem assistir ao vivo.', 'order' => 2],
                ],
                'links' => [
                    ['title' => 'Link do webinar', 'url' => 'https://youtube.com/live/producaoindependente'],
                ],
                'tasks' => [],
            ],
            [
                'user'            => $joao,
                'title'           => 'Live: Campanha Paz e Amor — Transmissão Global',
                'type'            => $typeShow,
                'venue'           => null,
                'attendance_mode' => 'participant',
                'is_online'       => true,
                'event_date'      => (clone $now)->addDays(3)->toDateString(),
                'event_time'      => '16:00',
                'end_date'        => (clone $now)->addDays(3)->toDateString(),
                'end_time'        => '18:00',
                'description'     => 'Live especial de João e sua esposa para a campanha "Paz e Amor". Transmissão simultânea no YouTube, Instagram e TikTok. Haverá música, discurso de paz e muito chá.',
                'ticket_price_first_batch'  => null,
                'ticket_price_door'         => null,
                'ticket_link'               => 'https://youtube.com/@bisouros/live',
                'extraInfos' => [
                    ['title' => 'Formato', 'information' => 'Transmissão da cama do casal. Estilo Bed-In for Peace.', 'order' => 1],
                ],
                'links' => [
                    ['title' => 'YouTube Live', 'url' => 'https://youtube.com/@bisouros/live'],
                    ['title' => 'Instagram Live', 'url' => 'https://instagram.com/bisouros'],
                ],
                'tasks' => [
                    ['title' => 'Preparar setlist para a live de paz e amor', 'assignee' => $joao, 'status' => 'Em Execucao', 'priority' => 'medium'],
                ],
            ],
        ];

        foreach ($eventsData as $eventData) {
            $venueModel = $eventData['venue'] ? $venues->get($eventData['venue']) : null;

            $event = Event::create([
                'user_id'                   => $eventData['user']->id,
                'event_type_id'             => $eventData['type']?->id,
                'venue_id'                  => $venueModel?->id,
                'title'                     => $eventData['title'],
                'attendance_mode'           => $eventData['attendance_mode'],
                'is_online'                 => $eventData['is_online'],
                'description'               => $eventData['description'],
                'event_date'                => $eventData['event_date'],
                'event_time'                => $eventData['event_time'],
                'end_date'                  => $eventData['end_date'],
                'end_time'                  => $eventData['end_time'],
                'ticket_link'               => $eventData['ticket_link'] ?? null,
                'ticket_price_first_batch'  => $eventData['ticket_price_first_batch'] ?? null,
                'ticket_price_second_batch' => $eventData['ticket_price_second_batch'] ?? null,
                'ticket_price_third_batch'  => $eventData['ticket_price_third_batch'] ?? null,
                'ticket_price_door'         => $eventData['ticket_price_door'] ?? null,
            ]);

            foreach ($eventData['extraInfos'] ?? [] as $info) {
                $event->extraInfos()->create($info);
            }

            foreach ($eventData['links'] ?? [] as $link) {
                $event->links()->create($link);
            }

            foreach ($eventData['tasks'] ?? [] as $taskData) {
                $task = Task::create([
                    'user_id'        => $taskData['assignee']->id,
                    'related_type'   => 'event',
                    'event_id'       => $event->id,
                    'content_id'     => null,
                    'plan_id'        => null,
                    'plan_phase_id'  => null,
                    'title'          => $taskData['title'],
                    'description'    => null,
                    'task_status_id' => $st($taskData['status']),
                    'priority'       => $taskData['priority'],
                    'due_date'       => $event->event_date,
                ]);
                $task->assignedUsers()->sync([$taskData['assignee']->id]);
            }
        }
    }
}