<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Support\SystemSettingsRegistry;
use Illuminate\Database\Seeder;

class SystemModuleSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SystemSettingsRegistry::moduleDefaultColors() as $moduleKey => $colorToken) {
            SystemSetting::set("module_color_{$moduleKey}", $colorToken);
        }
    }
}
