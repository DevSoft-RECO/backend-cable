<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago</title>
    <style>
        @page {
            size: 80mm auto; /* Permitir altura automática */
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            color: #000;
            margin: 10px;
            line-height: 1.3;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .header { margin-bottom: 8px; }
        .header h1 { font-size: 13px; margin: 0; padding: 0; font-weight: bold; }
        .header p { margin: 2px 0 0 0; font-size: 8px; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .info-table, .details-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 1px 0; vertical-align: top; }
        .details-table th, .details-table td { padding: 3px 0; vertical-align: top; }
        .details-table th { border-bottom: 1px dashed #000; font-weight: bold; }
        .totals-section { margin-top: 6px; text-align: right; }
        .totals-section .grand-total { font-size: 11px; font-weight: bold; }
        .footer { margin-top: 12px; font-size: 8px; }
    </style>
</head>
<body>
    @php
        // Extraemos el primer pago para datos globales (cliente, recibo)
        $pagoInicial = $pagos->first();
        $totalPagado = $pagos->sum('monto_pagado');
        $cliente = $pagoInicial->cargo->contrato->cliente;
    @endphp

    <!-- Encabezado de la Empresa -->
    <div class="text-center header">
        @if(isset($siteSettings['site_logo']) && $siteSettings['site_logo'] && file_exists(public_path($siteSettings['site_logo'])))
            <img src="{{ public_path($siteSettings['site_logo']) }}" style="max-height: 35px; max-width: 160px; margin-bottom: 4px;" /><br>
        @endif
        <h1 class="uppercase">{{ $siteSettings['site_name'] ?? 'ALIANZA' }}</h1>
        <p>{{ $siteSettings['site_subtitle'] ?? 'Suscripción TV Cable + Internet' }}</p>
        
        <div class="divider"></div>
        <div style="font-size: 11px; font-weight: bold; margin: 4px 0;">
            COMPROBANTE DE PAGO
        </div>
        <div class="divider"></div>
    </div>

    <!-- Información del Recibo -->
    <table class="info-table">
        <tr>
            <td class="font-bold" style="width: 25%;">No. Recibo:</td>
            <td>{{ $pagoInicial->codigo_recibo ?? str_pad($pagoInicial->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td class="font-bold">Fecha:</td>
            <td>{{ $pagoInicial->fecha_pago ? $pagoInicial->fecha_pago->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="font-bold">Cobrador:</td>
            <td>{{ $pagoInicial->user ? $pagoInicial->user->name : 'Cobrador Autorizado' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Datos del Cliente -->
    <table class="info-table">
        <tr>
            <td class="font-bold" style="width: 25%;">Cliente:</td>
            <td class="uppercase">{{ $cliente->nombre ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="font-bold">Código:</td>
            <td>{{ $cliente->codigo_cliente ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="font-bold">Dirección:</td>
            <td>{{ $cliente->direccion ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Detalle de Cargos Pagados -->
    <table class="details-table">
        <thead>
            <tr>
                <th class="text-left">Concepto</th>
                <th class="text-right" style="width: 30%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pagos as $p)
            <tr>
                <td>
                    <div class="font-bold uppercase">
                        {{ $p->cargo->concepto }}
                        @if($p->cargo->estado === 'parcial')
                            <br><span style="font-size: 8px; font-weight: bold; color: #333;">** ABONO PARCIAL **</span>
                            <br><span style="font-size: 8px; font-style: italic;">Saldo Restante: Q{{ number_format($p->cargo->saldo_pendiente ?? $p->cargo->monto, 2) }}</span>
                        @else
                            <br><span style="font-size: 8px; font-weight: normal;">(Liquidado)</span>
                        @endif
                    </div>
                </td>
                <td class="text-right align-top">
                    Q{{ number_format($p->monto_pagado, 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <!-- Totales -->
    <div class="totals-section">
        <table style="width: 100%;">
            <tr class="grand-total">
                <td class="text-left">TOTAL ABONADO:</td>
                <td class="text-right">Q{{ number_format($totalPagado, 2) }}</td>
            </tr>
        </table>
        <p class="uppercase" style="margin: 4px 0 0 0; font-size: 8px; font-style: italic;">
            Q{{ number_format($totalPagado, 0) }} exactos
        </p>
    </div>

    <!-- Pie de Ticket -->
    <div class="text-center footer">
        <p class="font-bold">¡Gracias por su pago!</p>
        @if($pagos->contains(fn($p) => $p->cargo->estado === 'parcial'))
            <p style="font-weight: bold; margin-top: 4px; border: 1px dashed #000; padding: 4px;">AVISO: Su cuenta aún presenta saldo pendiente por cancelar.</p>
        @else
            <p>Conserve este ticket como comprobante oficial.</p>
        @endif
    </div>
</body>
</html>
