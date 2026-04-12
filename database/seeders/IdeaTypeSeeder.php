<?php

namespace Database\Seeders;

use App\Models\IdeaType;
use App\Models\User;
use Illuminate\Database\Seeder;

class IdeaTypeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();

        $data = [
            ['name' => 'Video', 'color' => '#ef4444', 'icon' => 'pi pi-video'],
            ['name' => 'Reel', 'color' => '#8b5cf6', 'icon' => 'pi pi-play-circle'],
            ['name' => 'Story', 'color' => '#f59e0b', 'icon' => 'pi pi-clock'],
            ['name' => 'Post', 'color' => '#3b82f6', 'icon' => 'pi pi-image'],
            ['name' => 'Produção musical', 'color' => '#10b981', 'icon' => 'pi pi-microphone'],
            ['name' => 'Identidade visual', 'color' => '#ec4899', 'icon' => 'pi pi-palette'],
        ];

        foreach ($data as $item) {
            IdeaType::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color'], 'icon' => $item['icon']]
            );
        }
    }
}
