<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comprobante de Abono #{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</title>
<style>
    @page {
        size: {{ $comercio->ancho_ticket ?? '80mm' }} auto;
        margin: 4mm 3mm;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 12px;
        color: #000;
        background: #fff;
        width: {{ $comercio->ancho_ticket ?? '80mm' }};
        max-width: {{ $comercio->ancho_ticket ?? '80mm' }};
    }

    .center  { text-align: center; }
    .right   { text-align: right; }
    .bold    { font-weight: bold; }
    .small   { font-size: 10px; }
    .large   { font-size: 15px; }
    .xlarge  { font-size: 18px; }

    .sep-dashed  { border-top: 1px dashed #000; margin: 5px 0; }
    .sep-solid   { border-top: 2px solid #000; margin: 6px 0; }

    .row { display: flex; justify-content: space-between; margin: 2px 0; }
    .row .label { color: #444; }

    .abono-row { display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; color: #166534; margin: 4px 0; }
    .saldo-row { display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; margin: 3px 0; }
    .saldo-cero { color: #166534; }
    .saldo-pendiente { color: #991b1b; }

    /* Botones de pantalla — se ocultan al imprimir */
    .no-print { background: #f3f4f6; padding: 10px; text-align: center; }
    .no-print button, .no-print a {
        display: inline-block;
        margin: 3px 5px;
        padding: 7px 16px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
        color: #374151;
        background: #fff;
    }
    .no-print .btn-primary { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }

    @media print {
        .no-print { display: none !important; }
        body { width: {{ $comercio->ancho_ticket ?? '80mm' }}; }
    }
</style>
@unless(request()->boolean('embed'))
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 400);
    });
</script>
@endunless
</head>
<body>
@php
    $venta          = $pago->venta;
    $cliente        = $venta->cliente;
    $totalAbonado   = $venta->pagosCredito->sum('monto');
    $saldoRestante  = $venta->saldo_pendiente;
    $enEmbed        = request()->boolean('embed');
@endphp

{{-- Controles de pantalla (ocultos en modo embed y al imprimir) --}}
@unless($enEmbed)
<div class="no-print">
    <button class="btn-primary" onclick="window.print()">🖨️ Imprimir Comprobante</button>
    @if($cliente)
    <a href="{{ route('clientes.show', $cliente) }}">Ver Cliente</a>
    @endif
    <a href="{{ route('clientes.index') }}?con_deuda=1">← Créditos</a>
</div>
@endunless

{{-- ══════════════════════════════════════
     COMPROBANTE  (esto es lo que se imprime)
══════════════════════════════════════ --}}

{{-- Cabecera del comercio --}}
<div class="center">
    <div class="xlarge bold">{{ mb_strtoupper($comercio->nombre_comercio) }}</div>
    @if($comercio->nit)
    <div class="small">NIT: {{ $comercio->nit }}</div>
    @endif
    @if($comercio->direccion)
    <div class="small">{{ $comercio->direccion }}</div>
    @endif
    @if($comercio->ciudad || $comercio->telefono)
    <div class="small">{{ collect([$comercio->ciudad, $comercio->telefono])->filter()->implode(' · ') }}</div>
    @endif
    @if($comercio->email)
    <div class="small">{{ $comercio->email }}</div>
    @endif
</div>

<div class="sep-solid"></div>

<div class="center small bold">** COMPROBANTE DE ABONO A CRÉDITO **</div>

<div class="sep-dashed"></div>

{{-- Datos del abono --}}
<div class="row"><span class="label">Abono N°</span><span class="bold">{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</span></div>
<div class="row"><span class="label">Fecha</span><span>{{ $pago->created_at->format('d/m/Y H:i') }}</span></div>
<div class="row"><span class="label">Registró</span><span>{{ $pago->usuario->nombre }}</span></div>
<div class="row"><span class="label">Método pago</span><span>{{ $pago->metodo }}</span></div>
@if($pago->observacion)
<div class="row"><span class="label">Nota</span><span>{{ $pago->observacion }}</span></div>
@endif

<div class="sep-dashed"></div>

{{-- Cliente --}}
@if($cliente)
<div class="small bold">CLIENTE</div>
<div class="row"><span class="label">Nombre</span><span>{{ $cliente->nombre }}</span></div>
@if($cliente->documento)
<div class="row"><span class="label">Cédula</span><span>{{ $cliente->documento }}</span></div>
@endif
@if($cliente->telefono)
<div class="row"><span class="label">Teléfono</span><span>{{ $cliente->telefono }}</span></div>
@endif
<div class="sep-dashed"></div>
@endif

{{-- Crédito asociado --}}
<div class="small bold">CRÉDITO ASOCIADO</div>
<div class="row"><span class="label">Venta N°</span><span class="bold">{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</span></div>
<div class="row"><span class="label">Fecha venta</span><span>{{ $venta->created_at->format('d/m/Y') }}</span></div>
<div class="row"><span class="label">Total venta</span><span>${{ number_format($venta->total, 0, ',', '.') }}</span></div>

<div class="sep-solid"></div>

{{-- Montos --}}
<div class="abono-row">
    <span>ABONO RECIBIDO</span>
    <span>${{ number_format($pago->monto, 0, ',', '.') }}</span>
</div>

<div class="sep-dashed"></div>

<div class="row small"><span class="label">Total abonado a esta venta:</span><span class="bold">${{ number_format($totalAbonado, 0, ',', '.') }}</span></div>

<div class="saldo-row {{ $saldoRestante <= 0 ? 'saldo-cero' : 'saldo-pendiente' }}">
    <span>SALDO PENDIENTE:</span>
    <span>
        @if($saldoRestante <= 0)
            CANCELADO
        @else
            ${{ number_format($saldoRestante, 0, ',', '.') }}
        @endif
    </span>
</div>

@if($venta->fecha_vencimiento && $saldoRestante > 0)
<div class="row small {{ $venta->fecha_vencimiento < now() ? 'saldo-pendiente' : '' }}">
    <span class="label">Fecha límite pago:</span>
    <span>{{ $venta->fecha_vencimiento->format('d/m/Y') }}{{ $venta->fecha_vencimiento < now() ? ' (VENCIDO)' : '' }}</span>
</div>
@endif

<div class="sep-solid"></div>

{{-- Pie --}}
<div class="center small">
    @if($saldoRestante <= 0)
    *** CREDITO CANCELADO EN SU TOTALIDAD ***
    @else
    Gracias por su abono<br>
    Conserve este comprobante
    @endif
    @if($comercio->mensaje_ticket)
    <br>{{ $comercio->mensaje_ticket }}
    @endif
</div>

<div class="sep-dashed"></div>
<div class="center small" style="color:#555;">
    VISOFTEC POS · Generado {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
