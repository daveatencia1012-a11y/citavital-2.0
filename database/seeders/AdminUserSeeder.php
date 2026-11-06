<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('citavital.admin_password');

        if (blank($password)) {
            $this->command?->warn(
                'No se creó el administrador demo: define CITAVITAL_ADMIN_PASSWORD en el entorno.'
            );

            return;
        }

        User::query()->updateOrCreate(
            ['email' => config('citavital.admin_email')],
            [
                'name' => 'Administrador Demo',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
