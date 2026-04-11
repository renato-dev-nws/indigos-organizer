<?php

namespace Database\Seeders;

use App\Models\ContentType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();

        foreach (['Reel', 'Shorts', 'Post', 'Story', 'Live'] as $name) {
            ContentType::firstOrCreate(['user_id' => $user->id, 'name' => $name]);
        }
    }
}
