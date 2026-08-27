<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CampanaDescuento;

class CampanaDescuentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $campanas = CampanaDescuento::orderBy('id', 'desc')->get();
        return response()->json($campanas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo'       => 'required|string|max:100|unique:campanas_descuento,codigo',
            'descripcion'  => 'required|string|max:255',
            'tipo'         => 'required|in:porcentaje,monto_fijo',
            'valor'        => 'required|numeric|min:0',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $campana = CampanaDescuento::create([
            'codigo'       => strtoupper($validated['codigo']),
            'descripcion'  => $validated['descripcion'],
            'tipo'         => $validated['tipo'],
            'valor'        => $validated['valor'],
            'fecha_inicio' => $validated['fecha_inicio'] ?? null,
            'fecha_fin'    => $validated['fecha_fin'] ?? null,
            'activo'       => true,
        ]);

        return response()->json([
            'message' => 'Campaña de descuento creada exitosamente.',
            'campana' => $campana
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $campana = CampanaDescuento::findOrFail($id);

        $validated = $request->validate([
            'codigo'       => 'required|string|max:100|unique:campanas_descuento,codigo,' . $id,
            'descripcion'  => 'required|string|max:255',
            'tipo'         => 'required|in:porcentaje,monto_fijo',
            'valor'        => 'required|numeric|min:0',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'activo'       => 'required|boolean',
        ]);

        $validated['codigo'] = strtoupper($validated['codigo']);
        $campana->update($validated);

        return response()->json([
            'message' => 'Campaña de descuento actualizada exitosamente.',
            'campana' => $campana
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $campana = CampanaDescuento::findOrFail($id);
        $campana->delete();

        return response()->json([
            'message' => 'Campaña de descuento eliminada exitosamente.'
        ]);
    }
}
