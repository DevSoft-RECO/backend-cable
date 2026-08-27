<?php

namespace App\Http\Controllers\Publico\WebAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SobreNosotros;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NosotrosController extends Controller
{
    /**
     * Obtener data de la sección "Sobre Nosotros" para el panel de administración.
     */
    public function getAdminData()
    {
        $settings = DB::table('configuraciones_sitio')->pluck('valor', 'clave');
        $encabezado = [
            'titulo' => $settings['about_title'] ?? 'Sobre Nosotros',
            'subtitulo' => $settings['about_subtitle'] ?? 'Nuestra Empresa',
            'descripcion' => $settings['about_description'] ?? 'Conoce más sobre nuestra trayectoria y compromiso de entregarte la mejor señal y conectividad.',
            'bg_color' => $settings['about_bg_color'] ?? '#0f172a',
            'bg_image' => $settings['about_bg_image'] ?? null
        ];
        
        $registros = SobreNosotros::all();

        return response()->json([
            'encabezado' => $encabezado,
            'registros' => $registros
        ]);
    }

    /**
     * Guardar/Actualizar el Encabezado en la tabla configuraciones_sitio.
     */
    public function saveEncabezado(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'bg_color' => 'nullable|string|max:7',
            'bg_image' => 'nullable|image|max:5120'
        ]);

        DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'about_title'],
            ['valor' => $request->titulo, 'updated_at' => now()]
        );

        DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'about_subtitle'],
            ['valor' => $request->subtitulo, 'updated_at' => now()]
        );

        DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'about_description'],
            ['valor' => $request->descripcion, 'updated_at' => now()]
        );

        DB::table('configuraciones_sitio')->updateOrInsert(
            ['clave' => 'about_bg_color'],
            ['valor' => $request->bg_color ?? '#0f172a', 'updated_at' => now()]
        );

        // Si se solicita explícitamente eliminar la imagen de fondo
        if ($request->input('delete_bg_image') === 'true') {
            $currentBgImage = DB::table('configuraciones_sitio')->where('clave', 'about_bg_image')->value('valor');
            if ($currentBgImage && File::exists(public_path($currentBgImage))) {
                File::delete(public_path($currentBgImage));
            }
            DB::table('configuraciones_sitio')->updateOrInsert(
                ['clave' => 'about_bg_image'],
                ['valor' => null, 'updated_at' => now()]
            );
        }

        // Si se sube una nueva imagen de fondo
        if ($request->hasFile('bg_image')) {
            $file = $request->file('bg_image');
            $destinationPath = public_path('uploads/nosotros');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            // Borrar imagen vieja para evitar basura
            $currentBgImage = DB::table('configuraciones_sitio')->where('clave', 'about_bg_image')->value('valor');
            if ($currentBgImage && File::exists(public_path($currentBgImage))) {
                File::delete(public_path($currentBgImage));
            }

            $filename = 'bg_about_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $filename);
            
            DB::table('configuraciones_sitio')->updateOrInsert(
                ['clave' => 'about_bg_image'],
                ['valor' => 'uploads/nosotros/' . $filename, 'updated_at' => now()]
            );
        }

        Cache::forget('public_settings');
        Cache::forget('public_nosotros');

        $updatedSettings = DB::table('configuraciones_sitio')->pluck('valor', 'clave');

        return response()->json([
            'message' => 'Encabezado guardado correctamente',
            'encabezado' => [
                'titulo' => $request->titulo,
                'subtitulo' => $request->subtitulo,
                'descripcion' => $request->descripcion,
                'bg_color' => $request->bg_color ?? '#0f172a',
                'bg_image' => $updatedSettings['about_bg_image'] ?? null
            ]
        ]);
    }

    /**
     * Crear un nuevo registro (Colegio/Unidad o Fundador/Equipo)
     */
    public function storeRegistro(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:colegio,fundador',
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'foto' => 'nullable|image|max:4096'
        ]);

        $data = $request->except('foto');

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('uploads/nosotros');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $data['foto'] = 'uploads/nosotros/' . $filename;
        }

        $registro = SobreNosotros::create($data);

        Cache::forget('public_nosotros');

        return response()->json([
            'message' => 'Registro creado exitosamente.',
            'registro' => $registro
        ], 201);
    }

    /**
     * Actualizar un registro (Colegio/Unidad o Fundador/Equipo)
     */
    public function updateRegistro(Request $request, $id)
    {
        $registro = SobreNosotros::findOrFail($id);

        $request->validate([
            'tipo' => 'required|in:colegio,fundador',
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'foto' => 'nullable|image|max:4096'
        ]);

        $data = $request->except('foto');

        if ($request->hasFile('foto')) {
            if ($registro->foto && File::exists(public_path($registro->foto))) {
                File::delete(public_path($registro->foto));
            }

            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('uploads/nosotros');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $data['foto'] = 'uploads/nosotros/' . $filename;
        }

        $registro->update($data);

        Cache::forget('public_nosotros');

        return response()->json([
            'message' => 'Registro actualizado exitosamente.',
            'registro' => $registro
        ]);
    }

    /**
     * Eliminar un registro físicamente y su foto
     */
    public function destroyRegistro($id)
    {
        $registro = SobreNosotros::findOrFail($id);

        if ($registro->foto && File::exists(public_path($registro->foto))) {
            File::delete(public_path($registro->foto));
        }

        $registro->delete();

        Cache::forget('public_nosotros');

        return response()->json([
            'message' => 'Registro eliminado exitosamente.'
        ]);
    }
}
