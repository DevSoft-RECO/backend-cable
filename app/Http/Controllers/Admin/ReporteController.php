<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    /**
     * Obtener el cuadre de caja consolidado y detallado por cobrador.
     */
    public function cuadreCaja(Request $request)
    {
        $fechaInicioStr = $request->input('fecha_inicio');
        $fechaFinStr = $request->input('fecha_fin');

        // Si no se especifican, usar el día de hoy
        if ($fechaInicioStr) {
            $fechaInicio = Carbon::parse($fechaInicioStr)->startOfDay();
        } else {
            $fechaInicio = Carbon::today();
        }

        if ($fechaFinStr) {
            $fechaFin = Carbon::parse($fechaFinStr)->endOfDay();
        } else {
            $fechaFin = Carbon::today()->endOfDay();
        }

        // Consultar todos los pagos del rango
        $pagosQuery = Pago::with(['cargo', 'user'])
            ->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);

        $pagos = $pagosQuery->get();

        // Totales generales
        $totalEfectivo = 0.00;
        $totalTransferencia = 0.00;
        $totalDeposito = 0.00;
        $totalMora = 0.00;
        $totalDescuentos = 0.00;

        foreach ($pagos as $pago) {
            $monto = (float) $pago->monto_pagado;
            
            // Separar por método
            if ($pago->metodo_pago === 'efectivo') {
                $totalEfectivo += $monto;
            } elseif ($pago->metodo_pago === 'transferencia') {
                $totalTransferencia += $monto;
            } elseif ($pago->metodo_pago === 'deposito') {
                $totalDeposito += $monto;
            }

            // Sumar mora si el cargo tenía mora
            if ($pago->cargo) {
                // Si el pago es mayor al monto del cargo original, la diferencia es mora
                $montoCargo = (float) $pago->cargo->monto;
                if ($monto > $montoCargo) {
                    $totalMora += ($monto - $montoCargo);
                }
                
                // Descuento aplicado en el cargo
                $totalDescuentos += (float) ($pago->cargo->descuento_aplicado ?? 0.00);
            }
        }

        $totalRecaudado = $totalEfectivo + $totalTransferencia + $totalDeposito;

        // Desglose agrupado por Cobrador (Usuario)
        $cobradores = Pago::whereBetween('fecha_pago', [$fechaInicio, $fechaFin])
            ->join('users', 'pagos.usuario_id', '=', 'users.id')
            ->select(
                'users.name as cobrador_nombre',
                DB::raw("SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto_pagado ELSE 0 END) as efectivo"),
                DB::raw("SUM(CASE WHEN metodo_pago = 'transferencia' THEN monto_pagado ELSE 0 END) as transferencia"),
                DB::raw("SUM(CASE WHEN metodo_pago = 'deposito' THEN monto_pagado ELSE 0 END) as deposito"),
                DB::raw("SUM(monto_pagado) as total")
            )
            ->groupBy('users.id', 'users.name')
            ->get();

        return response()->json([
            'rango' => [
                'inicio' => $fechaInicio->toDateString(),
                'fin' => $fechaFin->toDateString()
            ],
            'totales' => [
                'efectivo' => round($totalEfectivo, 2),
                'transferencia' => round($totalTransferencia, 2),
                'deposito' => round($totalDeposito, 2),
                'mora' => round($totalMora, 2),
                'descuentos' => round($totalDescuentos, 2),
                'total_recaudado' => round($totalRecaudado, 2),
            ],
            'cobradores' => $cobradores,
            'pagos_detalle' => $pagos->map(function($pago) {
                return [
                    'id' => $pago->id,
                    'codigo_recibo' => $pago->codigo_recibo,
                    'cliente' => $pago->cargo->contrato->cliente->nombre ?? 'N/A',
                    'codigo_cliente' => $pago->cargo->contrato->cliente->codigo_cliente ?? 'N/A',
                    'concepto' => $pago->cargo->concepto ?? 'N/A',
                    'monto' => $pago->monto_pagado,
                    'metodo_pago' => $pago->metodo_pago,
                    'referencia' => $pago->referencia,
                    'fecha_pago' => $pago->fecha_pago->toDateTimeString(),
                    'cobrador' => $pago->user->name ?? 'N/A'
                ];
            })
        ]);
    }
}
