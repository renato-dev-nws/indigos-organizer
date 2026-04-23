<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InitialSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Global settings that do not depend on an existing user.
        $this->call([
            SystemModuleSettingsSeeder::class,
        ]);

        /* // User-scoped settings are only seeded when at least one user exists.
        if (! User::query()->exists()) {
            return;
        } */

        $this->call([
            IdeaTypeSeeder::class,
            IdeaCategorySeeder::class,
            ContentTypeSeeder::class,
            ContentCategorySeeder::class,
            ContentPlatformSeeder::class,
            VenueTaxonomySeeder::class,
            TaskStatusSeeder::class,
            EventTypeSeeder::class,
            SharedInfoCategorySeeder::class,
        ]);
    }
}
