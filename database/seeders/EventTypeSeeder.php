<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Show', 'color' => '#dc2626', 'icon' => 'mdi:ticket-confirmation-outline'],
            ['name' => 'Festival', 'color' => '#7c3aed', 'icon' => 'mdi:music-circle-outline'],
            ['name' => 'Workshop', 'color' => '#2563eb', 'icon' => 'mdi:guitar-acoustic'],
        ];

        foreach ($data as $item) {
            EventType::updateOrCreate(
                ['user_id' => null, 'name' => $item['name']],
                ['color' => $item['color'], 'icon' => $item['icon']],
            );
        }
    }
}