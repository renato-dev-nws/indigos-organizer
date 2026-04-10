<?php

namespace Database\Seeders;

use App\Models\IdeaType;
use App\Models\User;
use Illuminate\Database\Seeder;

class IdeaTypeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'demo@band.com')->firstOrFail();

        $data = [
            ['name' => 'Musica Nova', 'color' => '#6366f1'],
            ['name' => 'Campanha de MKT', 'color' => '#f59e0b'],
            ['name' => 'Ideia de Merch', 'color' => '#10b981'],
            ['name' => 'Clipe / Video', 'color' => '#ef4444'],
        ];

        foreach ($data as $item) {
            IdeaType::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color']]
            );
        }
    }
}
