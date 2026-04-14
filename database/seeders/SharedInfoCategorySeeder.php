<?php

namespace Database\Seeders;

use App\Models\SharedInfoCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class SharedInfoCategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();

        $data = [
            ['name' => 'Documentação', 'icon' => 'mdi:file-document-outline'],
            ['name' => 'Editais', 'icon' => 'mdi:stamp-text-outline'],
            ['name' => 'Produção', 'icon' => 'mdi:clipboard-text-outline'],
        ];

        foreach ($data as $item) {
            SharedInfoCategory::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['icon' => $item['icon']],
            );
        }
    }
}