<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DetalleTecnico;
use App\Models\Contrato;

class DetalleTecnicoController extends Controller
{
    /**
     * Devuelve todos los contratos activos con sus detalles técnicos.
     */
    public function index()
    {
        // Traemos contratos con su cliente, plan y detalles técnicos
        $contratos = Contrato::with(['cliente', 'plan', 'detalleTecnico'])
            ->where('estado', 'activo')
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($contratos);
    }

    /**
     * Guarda o actualiza los detalles técnicos de un contrato específico.
     */
    public function update(Request $request, $contrato_id)
    {
        $request->validate([
            'router_marca' => 'nullable|string|max:100',
            'router_modelo' => 'nullable|string|max:100',
            'direccion_ip' => 'nullable|string|max:45',
            'router_usuario' => 'nullable|string|max:100',
            'router_password' => 'nullable|string|max:100',
            'wifi_ssid' => 'nullable|string|max:100',
            'wifi_password' => 'nullable|string|max:100',
            'coordenadas_gps' => 'nullable|string|max:150',
            'notas' => 'nullable|string',
        ]);

        $contrato = Contrato::findOrFail($contrato_id);

        $detalle = DetalleTecnico::updateOrCreate(
            ['contrato_id' => $contrato->id],
            [
                'router_marca' => $request->router_marca,
                'router_modelo' => $request->router_modelo,
                'direccion_ip' => $request->direccion_ip,
                'router_usuario' => $request->router_usuario,
                'router_password' => $request->router_password,
                'wifi_ssid' => $request->wifi_ssid,
                'wifi_password' => $request->wifi_password,
                'coordenadas_gps' => $request->coordenadas_gps,
                'notas' => $request->notas,
            ]
        );

        $contrato->load(['cliente', 'plan', 'detalleTecnico']);
        return response()->json($contrato);
    }
}
