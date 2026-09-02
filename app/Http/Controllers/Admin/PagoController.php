<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Cargo;
use App\Models\Pago;
use App\Models\Contrato;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    /**
     * Listar todos los pagos registrados.
     */
    public function index()
    {
        $pagos = Pago::with(['cargo.contrato.cliente', 'cargo.contrato.plan', 'user'])
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($pagos);
    }

    /**
     * Buscar clientes por Código, Nombre o Identificación y calcular saldos pendientes en tiempo real.
     */
    public function buscarCliente(Request $request)
    {
        $queryStr = $request->input('query');

        if (empty($queryStr)) {
            return response()->json([], 200);
        }

        // Buscar clientes que coincidan por código de cliente, número de identificación o nombre
        $clientes = Cliente::where('codigo_cliente', $queryStr)
            ->orWhere('numero_identificacion', $queryStr)
            ->orWhere('nombre', 'LIKE', '%' . $queryStr . '%')
            ->with(['contratos' => function ($query) {
                $query->whereIn('estado', ['activo', 'suspendido'])->with(['plan', 'campanaDescuento']);
            }])
            ->get();

        $resultados = [];
        $hoy = Carbon::now();

        foreach ($clientes as $cliente) {
            foreach ($cliente->contratos as $contrato) {
                $cargosPendientes = [];
                $totalPendiente = 0;

                // Obtener cargos pendientes o vencidos para este contrato
                $cargos = Cargo::where('contrato_id', $contrato->id)
                    ->whereIn('estado', ['pendiente', 'parcial'])
                    ->orderBy('fecha_emision', 'asc')
                    ->get();

                foreach ($cargos as $cargo) {
                    $montoOriginal = $cargo->monto;
                    $saldoPendiente = $cargo->saldo_pendiente ?? $cargo->monto;
                    $montoMora = 0.00;

                    // Calcular mora si aplica según el plan
                    $plan = $contrato->plan;
                    if ($plan && $plan->mora_base > 0 && $saldoPendiente == $montoOriginal) {
                        $diasGracia = $plan->dias_gracia ?? 0;
                        $fechaVencimientoLimite = Carbon::parse($cargo->fecha_vencimiento)->addDays($diasGracia);

                        if ($hoy->greaterThan($fechaVencimientoLimite)) {
                            $montoMora = $plan->mora_base;
                        }
                    }

                    $totalCargo = round($saldoPendiente + $montoMora, 2);
                    $totalPendiente += $totalCargo;

                    $cargosPendientes[] = [
                        'id'                 => $cargo->id,
                        'concepto'           => $cargo->concepto,
                        'tipo'               => $cargo->tipo,
                        'monto_base'         => $montoOriginal,
                        'saldo_pendiente'    => $saldoPendiente,
                        'estado'             => $cargo->estado,
                        'descuento_aplicado' => $cargo->descuento_aplicado ?? 0.00,
                        'monto_mora'         => $montoMora,
                        'total'              => $totalCargo,
                        'fecha_emision'      => $cargo->fecha_emision->toDateString(),
                        'fecha_vencimiento'  => $cargo->fecha_vencimiento->toDateString(),
                    ];
                }

                $resultados[] = [
                    'cliente' => [
                        'id'                    => $cliente->id,
                        'codigo_cliente'        => $cliente->codigo_cliente,
                        'nombre'                => $cliente->nombre,
                        'numero_identificacion' => $cliente->numero_identificacion,
                    ],
                    'contrato' => [
                        'id'                     => $contrato->id,
                        'plan_nombre'            => $contrato->plan->nombre,
                        'precio_mensual_pactado' => $contrato->precio_mensual_pactado,
                        'direccion_servicio'     => $contrato->direccion_servicio ?? $cliente->direccion,
                        'estado'                 => $contrato->estado,
                    ],
                    'cargos_pendientes'  => $cargosPendientes,
                    'total_pendiente'    => round($totalPendiente, 2),
                ];
            }
        }

        return response()->json($resultados);
    }

    /**
     * Obtener un pago específico.
     */
    public function show($id)
    {
        $pago = Pago::with(['cargo.contrato.cliente', 'cargo.contrato.plan', 'user'])
            ->findOrFail($id);
            
        return response()->json($pago);
    }

    public function registrarPagoGlobal(Request $request)
    {
        $request->validate([
            'contrato_id'  => 'required|exists:contratos,id',
            'monto_pago'   => 'required|numeric|min:1',
            'metodo_pago'  => 'required|in:efectivo,transferencia,deposito',
            'referencia'   => 'nullable|string|max:100',
        ]);

        $montoRestante = $request->monto_pago;
        $contrato = Contrato::with('plan')->findOrFail($request->contrato_id);
        
        // Obtener cargos pendientes o parciales
        $cargos = Cargo::where('contrato_id', $contrato->id)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('fecha_emision', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($cargos->isEmpty()) {
            return response()->json(['message' => 'No hay deuda pendiente.'], 422);
        }

        $pagosGenerados = [];

        DB::transaction(function () use ($request, $cargos, &$montoRestante, &$pagosGenerados, $contrato) {
            // Generar folio secuencial REC-AAAA-NNNN único para toda la transacción
            $codigoRecibo = $this->generarCodigoRecibo();
            $hoy = Carbon::now();

            foreach ($cargos as $cargo) {
                if ($montoRestante <= 0) break;

                // Re-calcular mora (simplificado como estaba en el original)
                $montoMora = 0.00;
                $plan = $contrato->plan;
                if ($plan && $plan->mora_base > 0) {
                    $diasGracia = $plan->dias_gracia ?? 0;
                    $fechaVencimientoLimite = Carbon::parse($cargo->fecha_vencimiento)->addDays($diasGracia);
                    if ($hoy->greaterThan($fechaVencimientoLimite)) {
                        $montoMora = $plan->mora_base;
                    }
                }

                // Si el saldo_pendiente es nulo, asumimos que es el monto total
                $saldoDeuda = $cargo->saldo_pendiente ?? $cargo->monto;
                if ($saldoDeuda == $cargo->monto) {
                    $saldoDeuda += $montoMora; // Primera vez que se abona
                }

                // ¿Cuánto vamos a pagar de este cargo?
                $montoAAplicar = min((float) $montoRestante, (float) $saldoDeuda);
                $montoRestante -= $montoAAplicar;

                $nuevoSaldo = round($saldoDeuda - $montoAAplicar, 2);
                $nuevoEstado = $nuevoSaldo <= 0 ? 'pagado' : 'parcial';

                // Actualizar cargo
                $cargo->update([
                    'saldo_pendiente' => max(0, $nuevoSaldo),
                    'estado' => $nuevoEstado
                ]);

                // Crear el pago
                $pago = Pago::create([
                    'cargo_id'     => $cargo->id,
                    'usuario_id'   => auth()->id() ?? 1,
                    'codigo_recibo'=> $codigoRecibo,
                    'monto_pagado' => $montoAAplicar,
                    'metodo_pago'  => $request->metodo_pago,
                    'referencia'   => $request->referencia,
                    'fecha_pago'   => $hoy,
                ]);

                $pagosGenerados[] = $pago;
            }

            // Si el contrato tenía una campaña de descuento asignada, la limpiamos si se pagó algo
            if ($contrato->campana_descuento_id && count($pagosGenerados) > 0) {
                $contrato->update(['campana_descuento_id' => null]);
            }
        });

        return response()->json([
            'message' => 'Cobro registrado exitosamente.',
            'pagos'    => $pagosGenerados,
            'recibo'   => count($pagosGenerados) > 0 ? $pagosGenerados[0]->codigo_recibo : null
        ], 201);
    }

    /**
     * Registrar el cobro/pago de un cargo pendiente. (Individual)
     */
    public function registrarPago(Request $request)
    {
        $request->validate([
            'cargo_id'     => 'required|exists:cargos,id',
            'metodo_pago'  => 'required|in:efectivo,transferencia,deposito',
            'referencia'   => 'nullable|string|max:100',
        ]);

        $cargo = Cargo::findOrFail($request->cargo_id);

        if ($cargo->estado === 'pagado') {
            return response()->json([
                'message' => 'Este cargo ya fue pagado anteriormente.'
            ], 422);
        }

        $contrato = Contrato::findOrFail($cargo->contrato_id);
        $hoy = Carbon::now();
        $montoOriginal = $cargo->monto;
        $montoMora = 0.00;

        // Re-calcular mora al momento de registrar el pago por seguridad
        $plan = $contrato->plan;
        if ($plan && $plan->mora_base > 0) {
            $diasGracia = $plan->dias_gracia ?? 0;
            $fechaVencimientoLimite = Carbon::parse($cargo->fecha_vencimiento)->addDays($diasGracia);

            if ($hoy->greaterThan($fechaVencimientoLimite)) {
                $montoMora = $plan->mora_base;
            }
        }

        $montoFinal = round($montoOriginal + $montoMora, 2);

        $pago = DB::transaction(function () use ($request, $cargo, $montoFinal, $contrato) {
            // Generar folio secuencial REC-AAAA-NNNN
            $codigoRecibo = $this->generarCodigoRecibo();

            // 1. Crear el pago
            $pago = Pago::create([
                'cargo_id'     => $cargo->id,
                'usuario_id'   => auth()->id() ?? 1, // Fallback al admin principal
                'codigo_recibo'=> $codigoRecibo,
                'monto_pagado' => $montoFinal,
                'metodo_pago'  => $request->metodo_pago,
                'referencia'   => $request->referencia,
                'fecha_pago'   => Carbon::now(),
            ]);

            // 2. Marcar cargo como pagado
            $cargo->update([
                'estado' => 'pagado'
            ]);

            // Si el contrato tenía una campaña de descuento asignada, la limpiamos después de cobrar
            // para que no siga aplicando descuento de forma indefinida en futuros cobros (descuento de un solo mes)
            if ($contrato->campana_descuento_id) {
                $contrato->update([
                    'campana_descuento_id' => null
                ]);
            }

            return $pago;
        });

        return response()->json([
            'message' => 'Cobro registrado exitosamente.',
            'pago'    => $pago->load(['cargo.contrato.cliente', 'user'])
        ], 201);
    }

    /**
     * Generar e imprimir en formato ticket térmico el recibo PDF.
     */
    public function descargarRecibo($id)
    {
        $pagoInicial = Pago::findOrFail($id);

        // Buscar todos los pagos que comparten el mismo recibo
        // Si no tiene código de recibo (pagos antiguos), solo traemos ese pago
        if ($pagoInicial->codigo_recibo) {
            $pagos = Pago::with(['cargo.contrato.cliente', 'cargo.contrato.plan', 'user'])
                ->where('codigo_recibo', $pagoInicial->codigo_recibo)
                ->get();
        } else {
            $pagos = Pago::with(['cargo.contrato.cliente', 'cargo.contrato.plan', 'user'])
                ->where('id', $id)
                ->get();
        }

        $siteSettings = DB::table('configuraciones_sitio')->pluck('valor', 'clave')->toArray();

        // Enviamos la colección de pagos a la vista en lugar de uno solo
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.recibo', compact('pagos', 'siteSettings'));
        
        // 80mm de ancho en puntos es 226.77 pt. Altura variable según items, base 450 pt.
        $altura = 400 + (count($pagos) * 50);
        $pdf->setPaper([0, 0, 226.77, $altura], 'portrait');

        return $pdf->stream('recibo-' . ($pagoInicial->codigo_recibo ?? $pagoInicial->id) . '.pdf');
    }

    /**
     * Genera un código de recibo secuencial único (REC-AAAA-NNNN) resistente a colisiones.
     */
    private function generarCodigoRecibo(): string
    {
        $year = Carbon::now()->year;
        $latestPagoThisYear = Pago::where('codigo_recibo', 'LIKE', "REC-{$year}-%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(codigo_recibo, "-", -1) AS UNSIGNED) DESC')
            ->first();

        $nextSequence = 1;
        if ($latestPagoThisYear && $latestPagoThisYear->codigo_recibo) {
            $parts = explode('-', $latestPagoThisYear->codigo_recibo);
            if (count($parts) === 3) {
                $nextSequence = ((int) $parts[2]) + 1;
            }
        }

        // Bucle de seguridad por si acaso ya existe el correlativo en la BD
        do {
            $codigoRecibo = 'REC-' . $year . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
            $exists = Pago::where('codigo_recibo', $codigoRecibo)->exists();
            if ($exists) {
                $nextSequence++;
            }
        } while ($exists);

        return $codigoRecibo;
    }
}
