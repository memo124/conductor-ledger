<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'conductor@conductorledger.local'],
            [
                'name' => 'Oscar Conductor',
                'password' => Hash::make('password123'),
                'dui' => '12345678-9',
                'is_active' => true,
                'role' => 'user',
                'theme_preference' => 'auto',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@conductorledger.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'dui' => '98765432-1',
                'is_active' => true,
                'role' => 'admin',
                'theme_preference' => 'auto',
            ]
        );
    }
}
