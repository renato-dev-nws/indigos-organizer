<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            'Database\\Seeders\\UserSeeder',
            'Database\\Seeders\\VenueSizeSeeder',
            'Database\\Seeders\\IdeaTypeSeeder',
            'Database\\Seeders\\IdeaCategorySeeder',
            'Database\\Seeders\\ContentPlatformSeeder',
            'Database\\Seeders\\VenueTaxonomySeeder',
            'Database\\Seeders\\TaskStatusSeeder',
            'Database\\Seeders\\EventTypeSeeder',
            'Database\\Seeders\\SharedInfoCategorySeeder',
            'Database\\Seeders\\DemoDataSeeder',
            'Database\\Seeders\\PlanSeeder',
            'Database\\Seeders\\EventSeeder',
            'Database\\Seeders\\SharedInfoSeeder',
        ]);
    }
}
