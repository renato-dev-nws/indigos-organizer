<?php

namespace Database\Seeders;

use App\Models\IdeaCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class IdeaCategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'demo@band.com')->firstOrFail();

        foreach (['Identidade Visual', 'Redes Sociais', 'Shows ao Vivo', 'Estudio', 'Administrativo'] as $name) {
            IdeaCategory::firstOrCreate(['user_id' => $user->id, 'name' => $name]);
        }
    }
}
