<?php

namespace Database\Seeders;

use App\Models\VenueSize;
use Illuminate\Database\Seeder;

class VenueSizeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Pequena', 'Media', 'Grande'] as $size) {
            VenueSize::firstOrCreate(['name' => $size]);
        }
    }
}
