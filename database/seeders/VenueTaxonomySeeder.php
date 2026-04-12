<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\VenueCategory;
use App\Models\VenueStyle;
use App\Models\VenueType;
use Illuminate\Database\Seeder;

class VenueTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();

        $types = [
            ['name' => 'Casa de show', 'color' => '#ef4444', 'icon' => 'pi pi-building'],
            ['name' => 'Bar', 'color' => '#f59e0b', 'icon' => 'pi pi-glass'],
            ['name' => 'Festival', 'color' => '#8b5cf6', 'icon' => 'pi pi-star'],
        ];

        foreach ($types as $item) {
            VenueType::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color'], 'icon' => $item['icon']],
            );
        }

        $categories = [
            ['name' => 'Indie', 'color' => '#22c55e', 'icon' => 'pi pi-heart'],
            ['name' => 'Mainstream', 'color' => '#3b82f6', 'icon' => 'pi pi-bolt'],
            ['name' => 'Alternativo', 'color' => '#f97316', 'icon' => 'pi pi-compass'],
        ];

        foreach ($categories as $item) {
            VenueCategory::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color'], 'icon' => $item['icon']],
            );
        }

        $styles = [
            ['name' => 'Acústico', 'color' => '#0ea5e9', 'icon' => 'pi pi-volume-up'],
            ['name' => 'Eletrônico', 'color' => '#a855f7', 'icon' => 'pi pi-wave-pulse'],
            ['name' => 'Vintage', 'color' => '#a16207', 'icon' => 'pi pi-camera'],
        ];

        foreach ($styles as $item) {
            VenueStyle::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color'], 'icon' => $item['icon']],
            );
        }
    }
}
