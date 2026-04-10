<?php

namespace Database\Seeders;

use App\Models\ContentCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'demo@band.com')->firstOrFail();

        $data = [
            ['name' => 'Humor', 'color' => '#f59e0b'],
            ['name' => 'Divulgacao de Show', 'color' => '#6366f1'],
            ['name' => 'Making Of', 'color' => '#10b981'],
            ['name' => 'Bastidores', 'color' => '#ec4899'],
            ['name' => 'Educativo', 'color' => '#3b82f6'],
        ];

        foreach ($data as $item) {
            ContentCategory::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color']]
            );
        }
    }
}
