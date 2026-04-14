<?php

namespace Database\Seeders;

use App\Models\EventType;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'joao@band.com')->first() ?? User::firstOrFail();

        $data = [
            ['name' => 'Show', 'color' => '#dc2626', 'icon' => 'mdi:ticket-confirmation-outline'],
            ['name' => 'Festival', 'color' => '#7c3aed', 'icon' => 'mdi:music-circle-outline'],
            ['name' => 'Workshop', 'color' => '#2563eb', 'icon' => 'mdi:guitar-acoustic'],
        ];

        foreach ($data as $item) {
            EventType::updateOrCreate(
                ['user_id' => $user->id, 'name' => $item['name']],
                ['color' => $item['color'], 'icon' => $item['icon']],
            );
        }
    }
}