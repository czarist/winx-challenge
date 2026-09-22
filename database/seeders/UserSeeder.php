<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'teste@productapi.com'],
            ['name' => 'Usuário de Teste', 'password' => 'password123'],
        );
    }
}
