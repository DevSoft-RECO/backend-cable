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
            ->with(['contratos.plan', 'contratos.campanaDescuento'])
            ->get();

        $resultados = [];
        $hoy = Carbon::now();

        foreach ($clientes as $cliente) {
            // Obtener contrato activo o suspendido
            $contrato = $cliente->contratos->first(function ($c) {
                return $c->estado === 'activo' || $c->estado === 'suspendido';
            });

            $cargosPendientes = [];
            $totalPendiente = 0;

            if ($contrato) {
                // Obtener cargos pendientes o vencidos para este contrato
                $cargos = Cargo::where('contrato_id', $contrato->id)
                    ->where('estado', 'pendiente')
                    ->get();

                foreach ($cargos as $cargo) {
                    $montoOriginal = $cargo->monto;
                    $montoMora = 0.00;

                    // Calcular mora si aplica según el plan
                    $plan = $contrato->plan;
                    if ($plan && $plan->mora_base > 0) {
                        $diasGracia = $plan->dias_gracia ?? 0;
                        $fechaVencimientoLimite = Carbon::parse($cargo->fecha_vencimiento)->addDays($diasGracia);

                        if ($hoy->greaterThan($fechaVencimientoLimite)) {
                            $montoMora = $plan->mora_base;
                        }
                    }

                    $totalCargo = round($montoOriginal + $montoMora, 2);
                    $totalPendiente += $totalCargo;

                    $cargosPendientes[] = [
                        'id'                 => $cargo->id,
                        'concepto'           => $cargo->concepto,
                        'tipo'               => $cargo->tipo,
                        'monto_base'         => $montoOriginal,
                        'descuento_aplicado' => $cargo->descuento_aplicado ?? 0.00,
                        'monto_mora'         => $montoMora,
                        'total'              => $totalCargo,
                        'fecha_emision'      => $cargo->fecha_emision->toDateString(),
                        'fecha_vencimiento'  => $cargo->fecha_vencimiento->toDateString(),
                    ];
                }
            }

            $resultados[] = [
                'id'                 => $cliente->id,
                'codigo_cliente'     => $cliente->codigo_cliente,
                'nombre'             => $cliente->nombre,
                'numero_identificacion' => $cliente->numero_identificacion,
                'telefono'           => $cliente->telefono,
                'direccion'          => $cliente->direccion,
                'activo'             => $cliente->activo,
                'contrato'           => $contrato ? [
                    'id'                     => $contrato->id,
                    'plan_nombre'            => $contrato->plan->nombre,
                    'precio_mensual_pactado' => $contrato->precio_mensual_pactado,
                    'estado'                 => $contrato->estado,
                ] : null,
                'cargos_pendientes'  => $cargosPendientes,
                'total_pendiente'    => round($totalPendiente, 2),
            ];
        }

        return response()->json($resultados);
    }

    /**
     * Registrar el cobro/pago de un cargo pendiente.
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
            // 1. Crear el pago
            $pago = Pago::create([
                'cargo_id'     => $cargo->id,
                'usuario_id'   => auth()->id() ?? 1, // Fallback al admin principal si no hay auth activo en la sesion
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
}
