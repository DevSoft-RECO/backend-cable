<?php

namespace App\Http\Controllers\Publico\WebAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class WebsiteSettingsController extends Controller
{
    /**
     * Obtener todas las configuraciones del sitio.
     */
    public function index()
    {
        // Retornar como { 'clave': 'valor', ... } para fácil uso en frontend
        return Cache::rememberForever('public_settings', function () {
            return DB::table('configuraciones_sitio')->pluck('valor', 'clave');
        });
    }

    /**
     * Actualizar configuraciones del sitio, incluyendo carga de logo.
     */
    public function update(Request $request)
    {
        $fileKeys = ['site_logo', 'contacto_hero_bg_image'];
        $data = $request->except(array_merge($fileKeys, ['delete_contacto_hero_bg_image']));

        // Procesar archivos si se suben
        foreach ($fileKeys as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $destinationPath = public_path('uploads/config');

                if (!File::exists($destinationPath)) {
                    File::makeDirectory($destinationPath, 0755, true);
                }

                // Borrar archivo anterior para no acumular basura
                $currentFile = DB::table('configuraciones_sitio')->where('clave', $fileKey)->value('valor');
                if ($currentFile) {
                    // Si la ruta no empieza por barra o sí, lo localizamos
                    $cleanPath = ltrim($currentFile, '/');
                    $oldPath = public_path($cleanPath);
                    if (File::exists($oldPath)) {
                        File::delete($oldPath);
                    }
                }

                $filename = $fileKey . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);
                
                $filePath = 'uploads/config/' . $filename;
                
                DB::table('configuraciones_sitio')->updateOrInsert(
                    ['clave' => $fileKey],
                    [
                        'valor' => $filePath,
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // Eliminar imagen de fondo si se solicita
        if ($request->input('delete_contacto_hero_bg_image') === 'true') {
            $currentFile = DB::table('configuraciones_sitio')->where('clave', 'contacto_hero_bg_image')->value('valor');
            if ($currentFile) {
                $cleanPath = ltrim($currentFile, '/');
                $oldPath = public_path($cleanPath);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
            DB::table('configuraciones_sitio')->updateOrInsert(
                ['clave' => 'contacto_hero_bg_image'],
                [
                    'valor' => null,
                    'updated_at' => now(),
                ]
            );
        }

        foreach ($data as $key => $value) {
            // Ignorar archivos u otros valores inválidos
            if ($request->hasFile($key) || is_array($value)) {
                continue;
            }
            DB::table('configuraciones_sitio')->updateOrInsert(
                ['clave' => $key],
                [
                    'valor' => $value,
                    'updated_at' => now(),
                ]
            );
        }

        Cache::forget('public_settings');

        return response()->json([
            'message' => 'Configuraciones actualizadas correctamente',
            'settings' => DB::table('configuraciones_sitio')->pluck('valor', 'clave')
        ]);
    }
}
