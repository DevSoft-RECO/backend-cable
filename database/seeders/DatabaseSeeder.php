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
        // Crear el Super Admin si aún no existe
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name'     => 'Ronald Emanuel Cardona',
                'password' => Hash::make('123456'),
            ]
        );

        // Crear roles, permisos y asignar Super Admin al primer usuario
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
