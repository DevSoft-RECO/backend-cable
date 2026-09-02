<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear o actualizar el usuario Super Admin principal
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('123456'),
            ]
        );

        // 2. Ejecutar Seeder de Roles y Permisos
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
