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
            ['name' => 'Divulgação', 'icon' => 'pi pi-megaphone'],
            ['name' => 'Marketing', 'icon' => 'pi pi-chart-line'],
            ['name' => 'Informativo', 'icon' => 'pi pi-info-circle'],
            ['name' => 'Série', 'icon' => 'pi pi-list'],
            ['name' => 'Humor', 'icon' => 'pi pi-face-smile'],
            ['name' => 'História', 'icon' => 'pi pi-book'],
        ];

        foreach ($data as $item) {
            IdeaCategory::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['icon' => $item['icon']],
            );
        }
    }
}
