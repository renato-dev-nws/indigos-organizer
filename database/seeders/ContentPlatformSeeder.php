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

        foreach (['TikTok', 'Instagram', 'YouTube'] as $name) {
            ContentPlatform::firstOrCreate(['user_id' => $user->id, 'name' => $name]);
        }
    }
}
