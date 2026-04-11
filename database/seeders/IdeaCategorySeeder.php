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

        foreach (['Divulgação', 'Marketing', 'Informativo', 'Série', 'Humor', 'História'] as $name) {
            IdeaCategory::firstOrCreate(['user_id' => $user->id, 'name' => $name]);
        }
    }
}
