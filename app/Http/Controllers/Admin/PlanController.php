<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $planes = Plan::orderBy('id', 'desc')->get();
        return response()->json($planes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'       => 'required|string|max:255',
            'categoria'    => 'nullable|string|max:255',
            'descripcion'  => 'nullable|string',
            'precio_base'  => 'required|numeric|min:0',
            'mora_base'    => 'nullable|numeric|min:0',
            'dias_gracia'  => 'nullable|integer|min:0',
            'activo'       => 'nullable|boolean',
        ]);

        $plan = Plan::create([
            'nombre'       => $validated['nombre'],
            'categoria'    => $validated['categoria'] ?? null,
            'descripcion'  => $validated['descripcion'] ?? null,
            'precio_base'  => $validated['precio_base'],
            'mora_base'    => $validated['mora_base'] ?? 0.00,
            'dias_gracia'  => $validated['dias_gracia'] ?? 0,
            'activo'       => $request->has('activo') ? (bool)$request->input('activo') : true,
        ]);

        return response()->json([
            'message' => 'Plan creado exitosamente.',
            'plan'    => $plan
        ], 201); // Using 201 for Created
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'nombre'       => 'required|string|max:255',
            'categoria'    => 'nullable|string|max:255',
            'descripcion'  => 'nullable|string',
            'precio_base'  => 'required|numeric|min:0',
            'mora_base'    => 'nullable|numeric|min:0',
            'dias_gracia'  => 'nullable|integer|min:0',
            'activo'       => 'required|boolean',
        ]);

        $plan->update($validated);

        return response()->json([
            'message' => 'Plan actualizado exitosamente.',
            'plan'    => $plan
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        
        // TODO: En futuros módulos, validar si el plan está asociado a contratos activos antes de eliminar.
        // Por ahora, se permite la eliminación directa o desactivación.
        $plan->delete();

        return response()->json([
            'message' => 'Plan eliminado exitosamente.'
        ]);
    }
}
