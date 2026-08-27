<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Pago;
use App\Models\Cargo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Devuelve estadísticas reales y consolidadas del sistema.
     */
    public function index(Request $request)
    {
        $mesFiltro = $request->input('mes'); // Formato YYYY-MM (Ej: 2026-08)
        if ($mesFiltro) {
            $carbonMes = Carbon::parse($mesFiltro . '-01');
        } else {
            $carbonMes = Carbon::now();
        }

        // --- 1. DATOS GENERALES HISTÓRICOS ---
        $totalProyectado = (float) Cargo::sum('monto');
        $totalRecaudado = (float) Pago::sum('monto_pagado');
        $totalPendiente = (float) Cargo::where('estado', 'pendiente')->sum('monto');

        // --- 2. DATOS FILTRADOS POR MES ---
        $mesProyectado = (float) Cargo::whereMonth('fecha_emision', $carbonMes->month)
            ->whereYear('fecha_emision', $carbonMes->year)
            ->sum('monto');

        $mesRecaudado = (float) Pago::whereMonth('fecha_pago', $carbonMes->month)
            ->whereYear('fecha_pago', $carbonMes->year)
            ->sum('monto_pagado');

        // --- 3. LISTA DE COBRADORES (Afectada por el mes filtrado) ---
        $cobradores = DB::table('pagos')
            ->join('users', 'pagos.usuario_id', '=', 'users.id')
            ->join('cargos', 'pagos.cargo_id', '=', 'cargos.id')
            ->join('contratos', 'cargos.contrato_id', '=', 'contratos.id')
            ->whereMonth('pagos.fecha_pago', $carbonMes->month)
            ->whereYear('pagos.fecha_pago', $carbonMes->year)
            ->select(
                'users.name as cobrador_nombre',
                DB::raw('COUNT(DISTINCT contratos.cliente_id) as clientes_cobrados'),
                DB::raw('SUM(pagos.monto_pagado) as total_recaudado')
            )
            ->groupBy('users.id', 'users.name')
            ->get();

        // --- 4. LISTA DE CLIENTES ATRASADOS (Global) ---
        $clientesAtrasados = DB::table('clientes')
            ->join('contratos', 'clientes.id', '=', 'contratos.cliente_id')
            ->join('cargos', 'contratos.id', '=', 'cargos.contrato_id')
            ->where('cargos.estado', 'pendiente')
            ->select(
                'clientes.nombre',
                'clientes.codigo_cliente',
                DB::raw('COUNT(cargos.id) as cargos_atrasados')
            )
            ->groupBy('clientes.id', 'clientes.nombre', 'clientes.codigo_cliente')
            ->having('cargos_atrasados', '>', 0)
            ->orderBy('cargos_atrasados', 'desc')
            ->get();

        // --- 5. DATOS PARA GRÁFICOS (Últimos 6 meses) ---
        $chartLabels = [];
        $chartProyectado = [];
        $chartRecaudado = [];

        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $chartLabels[] = $m->translatedFormat('M');

            $chartProyectado[] = round((float) Cargo::whereMonth('fecha_emision', $m->month)
                ->whereYear('fecha_emision', $m->year)
                ->sum('monto'), 2);

            $chartRecaudado[] = round((float) Pago::whereMonth('fecha_pago', $m->month)
                ->whereYear('fecha_pago', $m->year)
                ->sum('monto_pagado'), 2);
        }

        return response()->json([
            'general' => [
                'total_proyectado' => round($totalProyectado, 2),
                'total_recaudado'  => round($totalRecaudado, 2),
                'total_pendiente'  => round($totalPendiente, 2),
            ],
            'mensual' => [
                'mes_nombre'      => $carbonMes->translatedFormat('F Y'),
                'mes_filtro'      => $carbonMes->format('Y-m'),
                'mes_proyectado'  => round($mesProyectado, 2),
                'mes_recaudado'   => round($mesRecaudado, 2),
            ],
            'chart_data' => [
                'labels'     => $chartLabels,
                'proyectado' => $chartProyectado,
                'recaudado'  => $chartRecaudado,
            ],
            'cobradores'         => $cobradores,
            'clientes_atrasados' => $clientesAtrasados,
        ]);
    }
}
