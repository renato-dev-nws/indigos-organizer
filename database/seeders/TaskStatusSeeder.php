<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();

        $data = [
            ['name' => 'Pendente', 'color' => '#94a3b8', 'order' => 1],
            ['name' => 'Em Execucao', 'color' => '#3b82f6', 'order' => 2],
            ['name' => 'Aguardando Revisao', 'color' => '#f59e0b', 'order' => 3],
            ['name' => 'Concluido', 'color' => '#10b981', 'order' => 4],
        ];

        foreach ($data as $item) {
            TaskStatus::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color'], 'order' => $item['order']]
            );
        }
    }
}
