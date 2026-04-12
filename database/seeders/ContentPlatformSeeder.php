<?php

namespace Database\Seeders;

use App\Models\ContentPlatform;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();

        $data = [
            ['name' => 'TikTok', 'icon' => 'pi pi-mobile'],
            ['name' => 'Instagram', 'icon' => 'pi pi-camera'],
            ['name' => 'YouTube', 'icon' => 'pi pi-youtube'],
        ];

        foreach ($data as $item) {
            ContentPlatform::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['icon' => $item['icon']],
            );
        }
    }
}
