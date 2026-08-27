<?php

namespace App\Http\Controllers\Publico\WebAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class SliderController extends Controller
{
    /**
     * Obtener todas las imágenes del slider unificadas.
     */
    public function index()
    {
        return Cache::rememberForever('public_slider', function () {
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
        });
    }

    /**
     * Crear un nuevo banner en el slider.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'category' => 'nullable|string|max:255',
            'main_title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $destinationPath = public_path('uploads/slider');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $filename);
            $dbPath = 'uploads/slider/' . $filename;

            $id = DB::table('slider_images')->insertGetId([
                'image_path' => $dbPath,
                'category' => $request->category,
                'main_title' => $request->main_title,
                'subtitle' => $request->subtitle,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Cache::forget('public_slider');

            return response()->json([
                'message' => 'Banner creado correctamente.',
                'data' => [
                    'id' => $id,
                    'url' => asset($dbPath),
                    'category' => $request->category,
                    'main_title' => $request->main_title,
                    'subtitle' => $request->subtitle,
                ]
            ], 201);
        }

        return response()->json(['message' => 'Error al subir la imagen del banner.'], 400);
    }

    /**
     * Actualizar datos o imagen del banner.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'category' => 'nullable|string|max:255',
            'main_title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $banner = DB::table('slider_images')->where('id', $id)->first();
        if (!$banner) {
            return response()->json(['message' => 'Banner no encontrado.'], 404);
        }

        $updateData = [
            'category' => $request->category,
            'main_title' => $request->main_title,
            'subtitle' => $request->subtitle,
            'updated_at' => now(),
        ];

        if ($request->hasFile('image')) {
            // Eliminar imagen vieja
            $oldPath = public_path($banner->image_path);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }

            $file = $request->file('image');
            $destinationPath = public_path('uploads/slider');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($destinationPath, $filename);

            $updateData['image_path'] = 'uploads/slider/' . $filename;
        }

        DB::table('slider_images')->where('id', $id)->update($updateData);

        Cache::forget('public_slider');

        $updatedBanner = DB::table('slider_images')->where('id', $id)->first();

        return response()->json([
            'message' => 'Banner actualizado correctamente.',
            'data' => [
                'id' => $updatedBanner->id,
                'url' => asset($updatedBanner->image_path),
                'category' => $updatedBanner->category,
                'main_title' => $updatedBanner->main_title,
                'subtitle' => $updatedBanner->subtitle,
            ]
        ]);
    }

    /**
     * Eliminar banner y archivo físico.
     */
    public function destroy($id)
    {
        $banner = DB::table('slider_images')->where('id', $id)->first();

        if (!$banner) {
            return response()->json(['message' => 'Banner no encontrado.'], 404);
        }

        $fullPath = public_path($banner->image_path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }

        DB::table('slider_images')->where('id', $id)->delete();

        Cache::forget('public_slider');

        return response()->json(['message' => 'Banner eliminado correctamente.']);
    }
}
