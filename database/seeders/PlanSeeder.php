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
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();
        $taskStatusId = TaskStatus::query()->orderBy('order')->value('id');

        $plan = Plan::updateOrCreate(
            ['user_id' => $user->id, 'title' => 'Gravação do Álbum Indie'],
            [
                'description' => 'Plano de produção do álbum da banda.',
                'start_date' => '2026-04-01',
                'end_date' => '2026-12-31',
                'progress' => 25,
                'status' => 'running',
            ]
        );

        $phases = [
            [
                'title' => 'Captação',
                'order' => 1,
                'tasks' => [
                    'Reservar estúdio de captação',
                    'Afinar instrumentos e testar equipamentos',
                    'Gravação das bases (bateria, baixo)',
                    'Gravação de guitarras e teclados',
                    'Gravação de vocais principais',
                ],
            ],
            [
                'title' => 'Mixagem',
                'order' => 2,
                'tasks' => [
                    'Seleção e organização das takes',
                    'Mixagem das faixas com engenheiro',
                    'Revisão e aprovação da mixagem pela banda',
                ],
            ],
            [
                'title' => 'Masterização',
                'order' => 3,
                'tasks' => [
                    'Envio das mixagens para masterização',
                    'Revisão do master e aprovação final',
                ],
            ],
            [
                'title' => 'Lançamento',
                'order' => 4,
                'tasks' => [
                    'Distribuição digital (Spotify, Apple Music, etc.)',
                    'Criação de arte da capa',
                    'Campanha de lançamento nas redes sociais',
                    'Live de lançamento do álbum',
                ],
            ],
        ];

        foreach ($phases as $phaseData) {
            $phase = $plan->phases()->updateOrCreate(
                ['title' => $phaseData['title']],
                [
                    'user_id' => $user->id,
                    'description' => null,
                    'order' => $phaseData['order'],
                ]
            );

            foreach ($phaseData['tasks'] as $title) {
                Task::updateOrCreate(
                    ['plan_phase_id' => $phase->id, 'title' => $title],
                    [
                        'user_id' => $user->id,
                        'assigned_user_id' => null,
                        'related_type' => 'plan',
                        'plan_id' => $plan->id,
                        'content_id' => null,
                        'description' => null,
                        'task_status_id' => $taskStatusId,
                        'priority' => 'medium',
                        'due_date' => null,
                    ]
                );
            }
        }
    }
}
