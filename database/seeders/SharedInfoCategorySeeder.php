<?php

namespace Database\Seeders;

use App\Models\SharedInfoCategory;
use Illuminate\Database\Seeder;

class SharedInfoCategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Documentação', 'icon' => 'mdi:file-document-outline'],
            ['name' => 'Editais', 'icon' => 'mdi:stamp-text-outline'],
            ['name' => 'Produção', 'icon' => 'mdi:clipboard-text-outline'],
        ];

        foreach ($data as $item) {
            SharedInfoCategory::updateOrCreate(
                ['user_id' => null, 'name' => $item['name']],
                ['icon' => $item['icon']],
            );
        }
    }
}