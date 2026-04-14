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
            ['name' => 'Casa de show', 'color' => '#ef4444', 'icon' => 'mdi:storefront-outline'],
            ['name' => 'Bar', 'color' => '#f59e0b', 'icon' => 'mdi:glass-cocktail'],
            ['name' => 'Festival', 'color' => '#8b5cf6', 'icon' => 'mdi:star-outline'],
        ];

        foreach ($types as $item) {
            VenueType::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color'], 'icon' => $item['icon']],
            );
        }

        $categories = [
            ['name' => 'Indie', 'color' => '#22c55e', 'icon' => 'mdi:heart-outline'],
            ['name' => 'Mainstream', 'color' => '#3b82f6', 'icon' => 'mdi:lightning-bolt-outline'],
            ['name' => 'Alternativo', 'color' => '#f97316', 'icon' => 'mdi:compass-outline'],
        ];

        foreach ($categories as $item) {
            VenueCategory::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color'], 'icon' => $item['icon']],
            );
        }

        $venueStyles = [
            ['name' => 'Acústico', 'color' => '#0ea5e9', 'icon' => 'mdi:music-clef-treble', 'domain' => VenueStyle::DOMAIN_VENUES],
            ['name' => 'Eletrônico', 'color' => '#a855f7', 'icon' => 'mdi:waveform', 'domain' => VenueStyle::DOMAIN_VENUES],
            ['name' => 'Vintage', 'color' => '#a16207', 'icon' => 'mdi:microphone-variant', 'domain' => VenueStyle::DOMAIN_VENUES],
        ];

        foreach ($venueStyles as $item) {
            VenueStyle::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name'], 'domain' => $item['domain']],
                ['color' => $item['color'], 'icon' => $item['icon']],
            );
        }

        $contentStyles = [
            ['name' => 'Performance', 'color' => '#0f766e', 'icon' => 'mdi:guitar-electric', 'domain' => VenueStyle::DOMAIN_CONTENT],
            ['name' => 'Bastidores', 'color' => '#dc2626', 'icon' => 'mdi:movie-open-outline', 'domain' => VenueStyle::DOMAIN_CONTENT],
            ['name' => 'Lançamento', 'color' => '#1d4ed8', 'icon' => 'mdi:rocket-launch-outline', 'domain' => VenueStyle::DOMAIN_CONTENT],
        ];

        foreach ($contentStyles as $item) {
            VenueStyle::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name'], 'domain' => $item['domain']],
                ['color' => $item['color'], 'icon' => $item['icon']],
            );
        }
    }
}
