<?php

namespace Database\Seeders;

use App\Models\ContentType;
use Illuminate\Database\Seeder;

class ContentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Reel', 'Shorts', 'Post', 'Story', 'Live'] as $name) {
            ContentType::firstOrCreate(['user_id' => null, 'name' => $name]);
        }
    }
}
