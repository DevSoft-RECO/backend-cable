<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Pago;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clientes = Cliente::with('contratos.plan')->orderBy('id', 'desc')->get();
        return response()->json($clientes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'                => 'required|string|max:255',
            'numero_identificacion' => 'required|string|max:255|unique:clientes,numero_identificacion',
            'telefono'              => 'nullable|string|max:50',
            'telefono_secundario'   => 'nullable|string|max:50',
            'direccion'             => 'required|string',
        ]);

        // Autogeneración del código de cliente
        $latest = Cliente::orderBy('id', 'desc')->first();
        $nextNumber = $latest ? ((int) str_replace('CLI-', '', $latest->codigo_cliente)) + 1 : 1;
        $codigoCliente = 'CLI-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $cliente = Cliente::create([
            'codigo_cliente'        => $codigoCliente,
            'numero_identificacion' => $validated['numero_identificacion'],
            'nombre'                => $validated['nombre'],
            'telefono'              => $validated['telefono'] ?? null,
            'telefono_secundario'   => $validated['telefono_secundario'] ?? null,
            'direccion'             => $validated['direccion'],
            'activo'                => true,
        ]);

        return response()->json([
            'message' => 'Cliente registrado exitosamente.',
            'cliente' => $cliente
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);

        $validated = $request->validate([
            'nombre'                => 'required|string|max:255',
            'numero_identificacion' => 'required|string|max:255|unique:clientes,numero_identificacion,' . $id,
            'telefono'              => 'nullable|string|max:50',
            'telefono_secundario'   => 'nullable|string|max:50',
            'direccion'             => 'required|string',
            'activo'                => 'required|boolean',
        ]);

        $cliente->update($validated);

        return response()->json([
            'message' => 'Cliente actualizado exitosamente.',
            'cliente' => $cliente
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();

        return response()->json([
            'message' => 'Cliente eliminado exitosamente.'
        ]);
    }

    /**
     * Obtener el historial de pagos de un cliente.
     */
    public function getPagos($id)
    {
        $pagos = Pago::whereHas('cargo.contrato', function ($query) use ($id) {
            $query->where('cliente_id', $id);
        })->with(['cargo', 'user'])->orderBy('id', 'desc')->get();

        return response()->json($pagos);
    }
}
