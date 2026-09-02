<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. Crear los 13 Permisos Oficiales ---
        $permissions = [
            'dashboard',
            'cobro_movil',
            'mapa_servicio',
            'gestion_ususarios',
            'plan_cobros',
            'gestion_clientes',
            'contratos',
            'promociones',
            'admin_cobros',
            'generar_qr',
            'admin_recibos',
            'admin_router',
            'ajustes_web',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // --- 2. Crear los Roles ---
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $duenoRole      = Role::firstOrCreate(['name' => 'Dueño', 'guard_name' => 'web']);
        $secretarioRole = Role::firstOrCreate(['name' => 'Secretario(a)', 'guard_name' => 'web']);
        $cobradorRole   = Role::firstOrCreate(['name' => 'Cobrador', 'guard_name' => 'web']);

        // --- 3. Limpiar permisos por defecto de roles (los permisos se asignan individualmente por usuario) ---
        $duenoRole->syncPermissions([]);
        $secretarioRole->syncPermissions([]);
        $cobradorRole->syncPermissions([]);

        // --- 4. Asignar Rol Super Admin al usuario Administrador ---
        $superAdminUser = User::where('email', 'admin@admin.com')->first();
        if ($superAdminUser && !$superAdminUser->hasRole('Super Admin')) {
            $superAdminUser->assignRole($superAdminRole);
        }

    }
}
