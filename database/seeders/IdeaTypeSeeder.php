<?php

namespace Database\Seeders;

use App\Models\IdeaType;
use Illuminate\Database\Seeder;

class IdeaTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Video', 'color' => '#ef4444', 'icon' => 'mdi:video-outline'],
            ['name' => 'Reel', 'color' => '#8b5cf6', 'icon' => 'mdi:play-circle-outline'],
            ['name' => 'Story', 'color' => '#f59e0b', 'icon' => 'mdi:clock-outline'],
            ['name' => 'Post', 'color' => '#3b82f6', 'icon' => 'mdi:image-outline'],
            ['name' => 'Produção musical', 'color' => '#10b981', 'icon' => 'mdi:microphone-outline'],
            ['name' => 'Identidade visual', 'color' => '#ec4899', 'icon' => 'mdi:palette-outline'],
        ];

        foreach ($data as $item) {
            IdeaType::updateOrCreate(
                ['user_id' => null, 'name' => $item['name']],
                ['color' => $item['color'], 'icon' => $item['icon']]
            );
        }
    }
}
