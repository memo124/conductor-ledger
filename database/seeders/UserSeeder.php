<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\EncryptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasColumn('users', 'encrypted_dek')) {
            $this->command?->warn('Columnas de cifrado no existen en users. Ejecuta la migración RBAC primero.');

            return;
        }

        $encryption = app(EncryptionService::class);

        $conductorKeys = $encryption->createUserKeyEnvelope('password123');
        $adminKeys = $encryption->createUserKeyEnvelope('admin123');

        $conductor = User::query()->updateOrCreate(
            ['email' => 'conductor@conductorledger.local'],
            [
                'name' => 'Oscar Conductor',
                'password' => Hash::make('password123'),
                'dui' => '12345678-9',
                'is_active' => true,
                'role' => 'user',
                'theme_preference' => 'auto',
            'locale_preference' => 'es',
            'currency_preference' => 'USD',
                'email_verified_at' => now(),
                'encrypted_dek' => $conductorKeys['encrypted_dek'],
                'admin_wrapped_dek' => $conductorKeys['admin_wrapped_dek'],
                'dek_salt' => $conductorKeys['dek_salt'],
                'kdf_params' => $conductorKeys['kdf_params'],
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@conductorledger.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'dui' => '98765432-1',
                'is_active' => true,
                'role' => 'admin',
                'theme_preference' => 'auto',
            'locale_preference' => 'es',
            'currency_preference' => 'USD',
                'email_verified_at' => now(),
                'encrypted_dek' => $adminKeys['encrypted_dek'],
                'admin_wrapped_dek' => $adminKeys['admin_wrapped_dek'],
                'dek_salt' => $adminKeys['dek_salt'],
                'kdf_params' => $adminKeys['kdf_params'],
            ]
        );

        $conductorRole = Role::query()->where('slug', 'conductor')->first();
        $adminRole = Role::query()->where('slug', 'administrador')->first();

        if ($conductorRole) {
            $conductor->roles()->sync([$conductorRole->id]);
        }

        if ($adminRole) {
            $admin->roles()->sync([$adminRole->id]);
        }
    }
}
