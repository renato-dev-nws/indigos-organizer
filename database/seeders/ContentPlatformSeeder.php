<?php

namespace Database\Seeders;

use App\Models\ContentPlatform;
use Illuminate\Database\Seeder;

class ContentPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'TikTok', 'icon' => 'mdi:music-note-eighth'],
            ['name' => 'Instagram', 'icon' => 'mdi:instagram'],
            ['name' => 'YouTube', 'icon' => 'mdi:youtube'],
        ];

        foreach ($data as $item) {
            ContentPlatform::updateOrCreate(
                ['user_id' => null, 'name' => $item['name']],
                ['icon' => $item['icon']],
            );
        }
    }
}
