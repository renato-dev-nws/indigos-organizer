<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            ['name' => 'João Leno',       'email' => 'joao@demo.com',  'is_admin' => true],
            ['name' => 'Paulo Macarti',   'email' => 'paulo@demo.com', 'is_admin' => true],
            ['name' => 'Jorge Cleberson', 'email' => 'jorge@demo.com', 'is_admin' => false],
            ['name' => 'Bingo Estrella',  'email' => 'bingo@demo.com', 'is_admin' => false],
        ];

        foreach ($members as $member) {
            User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => Hash::make('password'),
                    'is_admin' => $member['is_admin'],
                    'avatar_url' => null,
                    'theme' => 'system',
                ]
            );
        }
    }
}
