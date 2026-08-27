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

        // --- 1. Crear Permisos ---
        Permission::firstOrCreate(['name' => 'ver-dashboard', 'guard_name' => 'web']);

        // --- 2. Crear Roles (Idempotente con firstOrCreate) ---
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Direccion', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Secretaria', 'guard_name' => 'web']);

        // El "Super Admin" no necesita permisos explícitos gracias al Gate::before configurado en AppServiceProvider

        // --- 3. Asignar Super Admin al primer usuario de la tabla ---
        $superAdmin = User::first();
        if ($superAdmin && !$superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole($superAdminRole);
        }

        // --- 4. Semillar configuraciones básicas de identidad del sitio ---
        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'site_name'],
            [
                'valor' => 'Servicios de Cable',
                'etiqueta' => 'Nombre del Sitio',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'site_subtitle'],
            [
                'valor' => 'TV & Internet',
                'etiqueta' => 'Subtítulo del Sitio',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'site_logo'],
            [
                'valor' => null, // null por defecto para usar la imagen local del frontend
                'etiqueta' => 'Logo del Sitio',
                'tipo' => 'image',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'about_bg_color'],
            [
                'valor' => '#0f172a',
                'etiqueta' => 'Color de Fondo de Nosotros',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'contacto_hero_bg_color'],
            [
                'valor' => '#0f172a',
                'etiqueta' => 'Color de Fondo de Contacto',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'contacto_hero_bg_image'],
            [
                'valor' => null,
                'etiqueta' => 'Imagen de Fondo de Contacto',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'servicios_hero_bg_image'],
            [
                'valor' => null,
                'etiqueta' => 'Imagen de Fondo de Servicios',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // --- 4.5. Semillar configuraciones básicas del catálogo de servicios ---
        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'servicios_title'],
            [
                'valor' => 'Nuestros Planes y Servicios',
                'etiqueta' => 'Título de Servicios',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'servicios_subtitle'],
            [
                'valor' => 'Velocidad y Conectividad',
                'etiqueta' => 'Subtítulo de Servicios',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'servicios_description'],
            [
                'valor' => 'Elige el plan ideal para tu hogar o empresa con navegación ilimitada de alta velocidad y televisión en alta definición.',
                'etiqueta' => 'Descripción de Servicios',
                'tipo' => 'textarea',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // --- 5. Semillar configuraciones básicas del pie de página (Footer) ---
        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'footer_main_title'],
            [
                'valor' => 'Servicios de Cable',
                'etiqueta' => 'Título del Footer',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'footer_subtitle'],
            [
                'valor' => 'TV & Internet',
                'etiqueta' => 'Subtítulo del Footer',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'footer_description'],
            [
                'valor' => 'Llevando la mejor señal de televisión y conectividad a internet de alta velocidad a tu hogar.',
                'etiqueta' => 'Descripción del Footer',
                'tipo' => 'textarea',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'footer_location_title'],
            [
                'valor' => 'Ubicación',
                'etiqueta' => 'Título Ubicación',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'footer_address'],
            [
                'valor' => "Cantón Bella Vista,\nSan Ildefonso Ixtahuacán,\nHuehuetenango.",
                'etiqueta' => 'Dirección Física',
                'tipo' => 'textarea',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'footer_phone'],
            [
                'valor' => '+502 0000-0000',
                'etiqueta' => 'Teléfono del Footer',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'footer_links_title'],
            [
                'valor' => 'Enlaces',
                'etiqueta' => 'Título de Enlaces',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'footer_copyright'],
            [
                'valor' => '© 2026 Servicios de Cable. Todos los derechos reservados.',
                'etiqueta' => 'Copyright',
                'tipo' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // --- 6. Semillar datos iniciales de Sobre Nosotros (Sedes y Liderazgo) ---
        \Illuminate\Support\Facades\DB::table('sobre_nosotros')->updateOrInsert(
            ['nombre' => 'Sede Central - Servicios de Cable'],
            [
                'tipo' => 'colegio', // actúa como sucursal en el frontend
                'titulo' => 'Oficina Principal',
                'direccion' => 'Cantón Bella Vista, Huehuetenango',
                'descripcion' => "Nuestra sede principal está equipada con tecnología de punta en telecomunicaciones. Sirve como centro de operaciones regionales, monitoreo de señal de televisión y soporte técnico en vivo para todos nuestros afiliados.",
                'foto' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        \Illuminate\Support\Facades\DB::table('sobre_nosotros')->updateOrInsert(
            ['nombre' => 'Ing. Emanuel Morales'],
            [
                'tipo' => 'fundador', // directivo
                'titulo' => 'Director Ejecutivo / CEO',
                'direccion' => 'Guatemala',
                'descripcion' => "Director y fundador con más de 15 años de experiencia liderando proyectos de infraestructura y redes de fibra óptica. Su visión es conectar a todas las comunidades y garantizar una experiencia de entretenimiento e internet inigualable en el hogar.",
                'foto' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
