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
            ['name' => 'Pendente', 'color' => '#dc2626', 'order' => 1],
            ['name' => 'Em Execucao', 'color' => '#3b82f6', 'order' => 2],
            ['name' => 'Aguardando Revisao', 'color' => '#eab308', 'order' => 3],
            ['name' => 'Concluido', 'color' => '#16a34a', 'order' => 4],
        ];

        foreach ($data as $item) {
            TaskStatus::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color'], 'order' => $item['order']]
            );
        }
    }
}
