<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contrato;
use App\Models\Cargo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FacturarMensualidad extends Command
{
    protected $signature = 'facturar:mensualidad {--fecha= : Simular fecha de facturacion YYYY-MM-DD}';
    protected $description = 'Genera cargos de mensualidad masivamente para contratos activos al inicio de cada mes';

    public function handle()
    {
        $fechaReferencia = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : Carbon::now();
        $this->info("Iniciando facturación masiva para el mes de: " . $fechaReferencia->format('Y-m'));

        $contratos = Contrato::with('campanaDescuento')
            ->where('estado', 'activo')
            ->get();

        $cargosCreados = 0;
        $cargosIgnorados = 0;

        foreach ($contratos as $contrato) {
            $mesActual = $fechaReferencia->month;
            $añoActual = $fechaReferencia->year;

            // Evitar generar cobros duplicados en el mismo mes/año para el mismo contrato
            $existeCargo = Cargo::where('contrato_id', $contrato->id)
                ->where('tipo', 'mensualidad')
                ->whereMonth('fecha_emision', $mesActual)
                ->whereYear('fecha_emision', $añoActual)
                ->exists();

            if ($existeCargo) {
                $cargosIgnorados++;
                continue;
            }

            // Calcular monto neto
            $precioPactado = $contrato->precio_mensual_pactado;
            $descuentoAplicado = 0;
            $campanaId = null;

            if ($contrato->campanaDescuento && $contrato->campanaDescuento->activo) {
                $campana = $contrato->campanaDescuento;
                
                // Validar vigencia de la campaña de descuento
                $hoyStr = $fechaReferencia->format('Y-m-d');
                $vigente = true;
                if ($campana->fecha_inicio && $hoyStr < $campana->fecha_inicio->format('Y-m-d')) {
                    $vigente = false;
                }
                if ($campana->fecha_fin && $hoyStr > $campana->fecha_fin->format('Y-m-d')) {
                    $vigente = false;
                }

                if ($vigente) {
                    $campanaId = $campana->id;
                    if ($campana->tipo === 'porcentaje') {
                        $descuentoAplicado = round(($precioPactado * $campana->valor) / 100, 2);
                    } else {
                        $descuentoAplicado = min($campana->valor, $precioPactado);
                    }
                }
            }

            $montoNeto = round($precioPactado - $descuentoAplicado, 2);

            DB::transaction(function () use ($contrato, $montoNeto, $descuentoAplicado, $campanaId, $fechaReferencia) {
                $meses = [
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                ];
                $nombreMes = $meses[$fechaReferencia->month] ?? '';
                $concepto = "Mensualidad - " . $nombreMes . " " . $fechaReferencia->year;

                Cargo::create([
                    'contrato_id'          => $contrato->id,
                    'concepto'             => $concepto,
                    'tipo'                 => 'mensualidad',
                    'monto'                => $montoNeto,
                    'fecha_emision'        => $fechaReferencia->copy()->day(1)->toDateString(),
                    'fecha_vencimiento'    => $fechaReferencia->copy()->day(5)->toDateString(),
                    'estado'               => 'pendiente',
                    'campana_descuento_id' => $campanaId,
                    'descuento_aplicado'   => $descuentoAplicado,
                ]);
            });

            $cargosCreados++;
        }

        $this->info("Proceso terminado. Creados: {$cargosCreados}. Ignorados: {$cargosIgnorados}.");
        return 0;
    }
}
