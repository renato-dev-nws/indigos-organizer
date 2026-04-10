<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'demo@band.com'],
            [
                'name' => 'Demo Band',
                'password' => Hash::make('password'),
                'theme' => 'system',
            ]
        );
    }
}
