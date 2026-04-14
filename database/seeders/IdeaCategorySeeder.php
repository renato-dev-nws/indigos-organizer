<?php

namespace Database\Seeders;

use App\Models\IdeaCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class IdeaCategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();

        $data = [
            ['name' => 'Divulgação', 'icon' => 'mdi:bullhorn-variant-outline'],
            ['name' => 'Marketing', 'icon' => 'mdi:chart-line'],
            ['name' => 'Informativo', 'icon' => 'mdi:information-outline'],
            ['name' => 'Série', 'icon' => 'mdi:format-list-bulleted'],
            ['name' => 'Humor', 'icon' => 'mdi:emoticon-outline'],
            ['name' => 'História', 'icon' => 'mdi:book-open-page-variant-outline'],
        ];

        foreach ($data as $item) {
            IdeaCategory::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['icon' => $item['icon']],
            );
        }
    }
}
