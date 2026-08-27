<?php

namespace App\Http\Controllers\Publico\WebFront;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicDataController extends Controller
{
    public function init()
    {
        // Consolidamos las peticiones públicas
        $data = [
            'slider' => Cache::rememberForever('public_slider', function () {
                $images = DB::table('slider_images')->orderBy('created_at', 'desc')->get();

                return $images->map(function ($img) {
                    return [
                        'id' => $img->id,
                        'url' => asset($img->image_path), 
                        'category' => $img->category,
                        'main_title' => $img->main_title,
                        'subtitle' => $img->subtitle,
                    ];
                });
            }),
            'galeria' => [],
            'settings' => Cache::rememberForever('public_settings', function () {
                return DB::table('configuraciones_sitio')->pluck('valor', 'clave');
            }),
            'servicios' => Cache::rememberForever('public_servicios', function () {
                $settings = DB::table('configuraciones_sitio')->pluck('valor', 'clave');
                $categorias = \App\Models\ServicioCategoria::with('planes')->get();

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
            }),
            'nosotros' => Cache::rememberForever('public_nosotros', function () {
                $settings = DB::table('configuraciones_sitio')->pluck('valor', 'clave');
                $registros = DB::table('sobre_nosotros')->get()->groupBy('tipo');

                $colegios = $registros->has('colegio') ? $registros['colegio'] : ($registros->has('unidad') ? $registros['unidad'] : []);
                $fundadores = $registros->has('fundador') ? $registros['fundador'] : [];

                return [
                    'encabezado' => [
                        'titulo' => $settings['about_title'] ?? 'Sobre Nosotros',
                        'subtitulo' => $settings['about_subtitle'] ?? 'Nuestra Empresa',
                        'descripcion' => $settings['about_description'] ?? 'Conoce más sobre nuestra trayectoria y compromiso de entregarte la mejor señal y conectividad.',
                        'bg_color' => $settings['about_bg_color'] ?? '#0f172a',
                        'bg_image' => $settings['about_bg_image'] ?? null
                    ],
                    'colegios' => $colegios,
                    'fundadores' => $fundadores
                ];
            })
        ];

        return response()->json($data);
    }
}
