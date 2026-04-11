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
            ['name' => 'João Silva', 'email' => 'joao@band.com'],
            ['name' => 'Maria Souza', 'email' => 'maria@band.com'],
            ['name' => 'Carlos Lima', 'email' => 'carlos@band.com'],
            ['name' => 'Ana Oliveira', 'email' => 'ana@band.com'],
        ];

        foreach ($members as $member) {
            User::updateOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => Hash::make('password'),
                    'theme' => 'system',
                ]
            );
        }

        User::where('email', 'demo@band.com')->delete();
    }
}
