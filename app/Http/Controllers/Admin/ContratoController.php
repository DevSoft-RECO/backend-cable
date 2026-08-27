<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contrato;
use App\Models\Cargo;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContratoController extends Controller
{
    /**
     * Devuelve los contratos.
     */
    public function index()
    {
        $contratos = Contrato::with(['cliente', 'plan'])->orderBy('id', 'desc')->get();
        return response()->json($contratos);
    }

    /**
     * Previsualiza los cargos antes de registrar el contrato.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'plan_id'                => 'required|exists:planes,id',
            'precio_mensual_pactado' => 'required|numeric|min:0',
            'costo_instalacion'      => 'required|numeric|min:0',
            'fecha_inicio'           => 'required|date',
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $precioPactado = $request->precio_mensual_pactado;
        $costoInstalacion = $request->costo_instalacion;
        $fechaInicio = Carbon::parse($request->fecha_inicio);

        $preview = $this->calculateCargos($precioPactado, $costoInstalacion, $fechaInicio);

        return response()->json($preview);
    }

    /**
     * Registra un nuevo contrato y genera sus cargos iniciales.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id'             => 'required|exists:clientes,id',
            'plan_id'                => 'required|exists:planes,id',
            'precio_mensual_pactado' => 'required|numeric|min:0',
            'costo_instalacion'      => 'required|numeric|min:0',
            'fecha_inicio'           => 'required|date',
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio);

        // Validar que el cliente no tenga ya un contrato activo (un cliente, un contrato para simplificar)
        $existeActivo = Contrato::where('cliente_id', $request->cliente_id)
            ->where('estado', 'activo')
            ->exists();

        if ($existeActivo) {
            return response()->json([
                'message' => 'El cliente ya tiene un contrato activo.'
            ], 422);
        }

        $contrato = DB::transaction(function () use ($request, $fechaInicio) {
            // 1. Crear el contrato
            $contrato = Contrato::create([
                'cliente_id'             => $request->cliente_id,
                'plan_id'                => $request->plan_id,
                'precio_mensual_pactado' => $request->precio_mensual_pactado,
                'costo_instalacion'      => $request->costo_instalacion,
                'fecha_inicio'           => $request->fecha_inicio,
                'estado'                 => 'activo',
            ]);

            // 2. Calcular los cargos
            $calculos = $this->calculateCargos(
                $request->precio_mensual_pactado,
                $request->costo_instalacion,
                $fechaInicio
            );

            // 3. Crear cargos en base de datos
            foreach ($calculos['cargos'] as $c) {
                Cargo::create([
                    'contrato_id'       => $contrato->id,
                    'concepto'          => $c['concepto'],
                    'tipo'              => $c['tipo'],
                    'monto'             => $c['monto'],
                    'fecha_emision'     => $c['fecha_emision'],
                    'fecha_vencimiento' => $c['fecha_vencimiento'],
                    'estado'            => 'pendiente',
                ]);
            }

            return $contrato;
        });

        return response()->json([
            'message'  => 'Contrato registrado y cargos iniciales generados con éxito.',
            'contrato' => $contrato->load(['cliente', 'plan', 'cargos'])
        ], 201);
    }

    /**
     * Función interna para calcular los cargos iniciales.
     */
    private function calculateCargos($precioPactado, $costoInstalacion, Carbon $fechaInicio)
    {
        $cargos = [];
        $totalCargos = 0;

        // --- 1. Cálculo de Prorrateo Mensualidad ---
        $diasTotalesMes = $fechaInicio->daysInMonth;
        $diaInicio = $fechaInicio->day;
        
        // Días restantes en el mes (incluyendo el de inicio)
        $diasActivos = $diasTotalesMes - $diaInicio + 1;

        // Monto prorrateado
        if ($diasActivos === $diasTotalesMes) {
            $montoProrrateado = $precioPactado;
            $conceptoMensualidad = "Mensualidad Completa - " . $this->getMonthNameInSpanish($fechaInicio->month) . " " . $fechaInicio->year;
        } else {
            $montoProrrateado = round(($precioPactado / $diasTotalesMes) * $diasActivos, 2);
            $conceptoMensualidad = "Proporcional " . $diasActivos . " días - " . $this->getMonthNameInSpanish($fechaInicio->month) . " " . $fechaInicio->year;
        }

        // Fecha de Vencimiento: Día 5 del mes de inicio. 
        // Si la contratación es posterior al día 5, se vence el día 5 del siguiente mes para ser justos.
        $fechaVencimiento = $fechaInicio->copy()->day(5);
        if ($diaInicio > 5) {
            $fechaVencimiento->addMonth();
        }

        if ($montoProrrateado > 0) {
            $cargos[] = [
                'concepto'          => $conceptoMensualidad,
                'tipo'              => 'mensualidad',
                'monto'             => $montoProrrateado,
                'fecha_emision'     => $fechaInicio->toDateString(),
                'fecha_vencimiento' => $fechaVencimiento->toDateString(),
            ];
            $totalCargos += $montoProrrateado;
        }

        // --- 2. Cargo de Instalación ---
        if ($costoInstalacion > 0) {
            $cargos[] = [
                'concepto'          => "Costo de Instalación de Servicio",
                'tipo'              => 'instalacion',
                'monto'             => $costoInstalacion,
                'fecha_emision'     => $fechaInicio->toDateString(),
                'fecha_vencimiento' => $fechaVencimiento->toDateString(),
            ];
            $totalCargos += $costoInstalacion;
        }

        return [
            'dias_activos'      => $diasActivos,
            'dias_totales_mes'  => $diasTotalesMes,
            'fecha_vencimiento' => $fechaVencimiento->toDateString(),
            'cargos'            => $cargos,
            'total_preview'     => round($totalCargos, 2)
        ];
    }

    private function getMonthNameInSpanish($monthNumber)
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[$monthNumber] ?? '';
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $contrato = Contrato::findOrFail($id);

        $request->validate([
            'plan_id'                => 'required|exists:planes,id',
            'precio_mensual_pactado' => 'required|numeric|min:0',
            'costo_instalacion'      => 'required|numeric|min:0',
            'fecha_inicio'           => 'required|date',
            'estado'                 => 'required|in:activo,suspendido,cancelado',
        ]);

        $contrato->update([
            'plan_id'                => $request->plan_id,
            'precio_mensual_pactado' => $request->precio_mensual_pactado,
            'costo_instalacion'      => $request->costo_instalacion,
            'fecha_inicio'           => $request->fecha_inicio,
            'estado'                 => $request->estado,
        ]);

        return response()->json([
            'message'  => 'Contrato actualizado exitosamente.',
            'contrato' => $contrato->load(['cliente', 'plan'])
        ]);
    }
}
