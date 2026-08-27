<?php

namespace App\Http\Controllers\Publico\WebAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServicioCategoria;
use App\Models\ServicioPlan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ServiciosController extends Controller
{
    /**
     * Devuelve toda la información para el catálogo público de planes y servicios.
     */
    public function indexPublic(Request $request)
    {
        $data = Cache::rememberForever('public_servicios', function () {
            $settings = DB::table('configuraciones_sitio')->pluck('valor', 'clave');
            $categorias = ServicioCategoria::with('planes')->get();

            $categoriasMapeadas = $categorias->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'nombre' => $cat->nombre,
                    'label' => $cat->label ?? 'Plan',
                    'descripcion_web' => $cat->descripcion_web ?? '',
                    'theme' => $cat->theme ?? 'blue',
                    'planes' => $cat->planes->map(function ($plan) {
                        return [
                            'id' => $plan->id,
                            'nombre' => $plan->nombre,
                            'subtitulo' => $plan->subtitulo,
                            'velocidad' => $plan->velocidad,
                            'badge' => $plan->badge,
                            'icon' => $plan->icon ?? 'wifi',
                            'detalles' => $plan->detalles ?? [],
                        ];
                    })
                ];
            });

            return [
                'encabezado' => [
                    'titulo' => $settings['servicios_title'] ?? 'Nuestros Planes y Servicios',
                    'subtitulo' => $settings['servicios_subtitle'] ?? 'Velocidad y Conectividad',
                    'descripcion' => $settings['servicios_description'] ?? 'Elige el plan ideal para tu hogar o empresa con navegación ilimitada de alta velocidad.',
                    'servicios_hero_bg_image' => $settings['servicios_hero_bg_image'] ?? null,
                ],
                'categorias' => $categoriasMapeadas
            ];
        });

        return response()->json($data);
    }

    /**
     * Devuelve la información para el panel de administración.
     */
    public function getAdminData()
    {
        $settings = DB::table('configuraciones_sitio')->pluck('valor', 'clave');
        $categorias = ServicioCategoria::with('planes')->get();

        $encabezado = [
            'titulo' => $settings['servicios_title'] ?? 'Nuestros Planes y Servicios',
            'subtitulo' => $settings['servicios_subtitle'] ?? 'Velocidad y Conectividad',
            'descripcion' => $settings['servicios_description'] ?? 'Elige el plan ideal para tu hogar o empresa con navegación ilimitada de alta velocidad.',
            'servicios_hero_bg_image' => $settings['servicios_hero_bg_image'] ?? null,
        ];

        return response()->json([
            'encabezado' => $encabezado,
            'categorias' => $categorias
        ]);
    }

    /**
     * Guarda o actualiza el encabezado general del catálogo.
     */
    public function saveEncabezado(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string'
        ]);

        DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'servicios_title'],
            ['valor' => $request->titulo, 'updated_at' => now()]
        );

        DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'servicios_subtitle'],
            ['valor' => $request->subtitulo, 'updated_at' => now()]
        );

        DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'servicios_description'],
            ['valor' => $request->descripcion, 'updated_at' => now()]
        );

        // Procesar imagen
        if ($request->hasFile('servicios_hero_bg_image')) {
            $file = $request->file('servicios_hero_bg_image');
            $destinationPath = public_path('uploads/config');
            if (!\Illuminate\Support\Facades\File::exists($destinationPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($destinationPath, 0755, true);
            }

            // Borrar archivo anterior
            $currentFile = DB::table('configuraciones_sitio')->where('clave', 'servicios_hero_bg_image')->value('valor');
            if ($currentFile) {
                $oldPath = public_path(ltrim($currentFile, '/'));
                if (\Illuminate\Support\Facades\File::exists($oldPath)) {
                    \Illuminate\Support\Facades\File::delete($oldPath);
                }
            }

            $filename = 'servicios_hero_bg_image_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $filename);
            $filePath = 'uploads/config/' . $filename;

            DB::table('configuraciones_sitio')->updateOrInsert(
                ['clave' => 'servicios_hero_bg_image'],
                ['valor' => $filePath, 'updated_at' => now()]
            );
        }

        // Eliminar imagen si se solicita
        if ($request->input('delete_servicios_hero_bg_image') === 'true') {
            $currentFile = DB::table('configuraciones_sitio')->where('clave', 'servicios_hero_bg_image')->value('valor');
            if ($currentFile) {
                $oldPath = public_path(ltrim($currentFile, '/'));
                if (\Illuminate\Support\Facades\File::exists($oldPath)) {
                    \Illuminate\Support\Facades\File::delete($oldPath);
                }
            }
            DB::table('configuraciones_sitio')->updateOrInsert(
                ['clave' => 'servicios_hero_bg_image'],
                ['valor' => null, 'updated_at' => now()]
            );
        }

        $this->clearCache();

        $newImage = DB::table('configuraciones_sitio')->where('clave', 'servicios_hero_bg_image')->value('valor');

        return response()->json([
            'message' => 'Encabezado de servicios actualizado exitosamente.',
            'encabezado' => [
                'titulo' => $request->titulo,
                'subtitulo' => $request->subtitulo,
                'descripcion' => $request->descripcion,
                'servicios_hero_bg_image' => $newImage
            ]
        ]);
    }

    /**
     * CRUD - Crear nueva Categoría de Servicios.
     */
    public function storeCategoria(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'descripcion_web' => 'nullable|string',
            'theme' => 'nullable|string|max:50'
        ]);

        $categoria = ServicioCategoria::create($request->all());
        $this->clearCache();

        return response()->json([
            'message' => 'Categoría de servicios creada exitosamente.',
            'categoria' => $categoria
        ], 201);
    }

    /**
     * CRUD - Actualizar Categoría de Servicios.
     */
    public function updateCategoria(Request $request, $id)
    {
        $categoria = ServicioCategoria::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'descripcion_web' => 'nullable|string',
            'theme' => 'nullable|string|max:50'
        ]);

        $categoria->update($request->all());
        $this->clearCache();

        return response()->json([
            'message' => 'Categoría de servicios actualizada exitosamente.',
            'categoria' => $categoria
        ]);
    }

    /**
     * CRUD - Eliminar Categoría de Servicios.
     */
    public function destroyCategoria($id)
    {
        $categoria = ServicioCategoria::findOrFail($id);
        $categoria->delete();
        $this->clearCache();

        return response()->json([
            'message' => 'Categoría de servicios eliminada exitosamente.'
        ]);
    }

    /**
     * CRUD - Crear un Plan dentro de una Categoría.
     */
    public function storePlan(Request $request)
    {
        $request->validate([
            'servicio_categoria_id' => 'required|exists:servicio_categorias,id',
            'nombre' => 'required|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'velocidad' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'detalles' => 'nullable|array',
        ]);

        $plan = ServicioPlan::create($request->all());
        $this->clearCache();

        return response()->json([
            'message' => 'Plan de servicios creado exitosamente.',
            'plan' => $plan
        ], 201);
    }

    /**
     * CRUD - Actualizar un Plan existente.
     */
    public function updatePlan(Request $request, $id)
    {
        $plan = ServicioPlan::findOrFail($id);

        $request->validate([
            'servicio_categoria_id' => 'required|exists:servicio_categorias,id',
            'nombre' => 'required|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'velocidad' => 'nullable|string|max:255',
            'badge' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'detalles' => 'nullable|array',
        ]);

        $plan->update($request->all());
        $this->clearCache();

        return response()->json([
            'message' => 'Plan de servicios actualizado exitosamente.',
            'plan' => $plan
        ]);
    }

    /**
     * CRUD - Eliminar un Plan.
     */
    public function destroyPlan($id)
    {
        $plan = ServicioPlan::findOrFail($id);
        $plan->delete();
        $this->clearCache();

        return response()->json([
            'message' => 'Plan de servicios eliminado exitosamente.'
        ]);
    }

    /**
     * Limpia la caché pública.
     */
    private function clearCache()
    {
        Cache::forget('public_servicios');
        // También limpiamos la inicialización general pública
        Cache::forget('public_init');
    }
}
