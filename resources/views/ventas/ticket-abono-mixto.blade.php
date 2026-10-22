<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comprobante Abono Global</title>
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
    .large   { font-size: 14px; }
    .xlarge  { font-size: 18px; }

    .sep-dashed { border-top: 1px dashed #000; margin: 5px 0; }
    .sep-solid  { border-top: 2px solid #000; margin: 6px 0; }

    .row { display: flex; justify-content: space-between; margin: 2px 0; }
    .row .label { color: #444; }

    .total-row  { display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; color: #166534; margin: 4px 0; }
    .saldo-row  { display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; margin: 3px 0; }
    .saldo-cero { color: #166534; }
    .saldo-pend { color: #991b1b; }

    .venta-item { margin: 4px 0; padding: 3px 0; border-bottom: 1px dotted #ccc; }
    .venta-item:last-child { border-bottom: none; }

    .no-print { background: #f3f4f6; padding: 10px; text-align: center; }
    .no-print button, .no-print a {
        display: inline-block; margin: 3px 5px;
        padding: 7px 16px; border-radius: 6px;
        border: 1px solid #d1d5db; font-size: 13px;
        cursor: pointer; text-decoration: none;
        color: #374151; background: #fff;
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
    $totalPagado    = $pagos->sum('monto');
    $saldoRestante  = $cliente->ventas()->activas()->where('es_credito', true)
                        ->whereIn('estado_credito', ['Parcial','Mora'])->sum('saldo_pendiente');
    $enEmbed        = request()->boolean('embed');
    $primero        = $pagos->first();
@endphp

@unless($enEmbed)
<div class="no-print">
    <button class="btn-primary" onclick="window.print()">🖨️ Imprimir Comprobante</button>
    <a href="{{ route('clientes.show', $cliente) }}">Ver Cliente</a>
    <a href="{{ route('clientes.index') }}?con_deuda=1">← Créditos</a>
</div>
@endunless

{{-- Cabecera comercio --}}
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
</div>

<div class="sep-solid"></div>
<div class="center small bold">** COMPROBANTE DE ABONO GLOBAL **</div>
<div class="sep-dashed"></div>

{{-- Datos generales --}}
<div class="row"><span class="label">Fecha</span><span>{{ $primero->created_at->format('d/m/Y H:i') }}</span></div>
<div class="row"><span class="label">Registró</span><span>{{ $primero->usuario->nombre }}</span></div>
<div class="row"><span class="label">Método</span><span>{{ $primero->metodo }}</span></div>
@if($primero->observacion)
<div class="row"><span class="label">Nota</span><span>{{ $primero->observacion }}</span></div>
@endif

<div class="sep-dashed"></div>

{{-- Cliente --}}
<div class="small bold">CLIENTE</div>
<div class="row"><span class="label">Nombre</span><span>{{ $cliente->nombre }}</span></div>
@if($cliente->documento)
<div class="row"><span class="label">Cédula</span><span>{{ $cliente->documento }}</span></div>
@endif
@if($cliente->telefono)
<div class="row"><span class="label">Teléfono</span><span>{{ $cliente->telefono }}</span></div>
@endif

<div class="sep-dashed"></div>

{{-- Detalle por venta --}}
<div class="small bold">DISTRIBUCIÓN DEL ABONO</div>
<div style="margin-top:3px;">
    @foreach($pagos as $pago)
    @php $v = $pago->venta; @endphp
    <div class="venta-item">
        <div class="row">
            <span class="bold">Venta #{{ str_pad($v->id, 6, '0', STR_PAD_LEFT) }}</span>
            <span class="bold">${{ number_format($pago->monto, 0, ',', '.') }}</span>
        </div>
        <div class="row small">
            <span class="label">Fecha venta: {{ $v->created_at->format('d/m/Y') }}</span>
            @if($v->saldo_pendiente <= 0)
            <span style="color:#166534;font-weight:bold;">SALDADA</span>
            @else
            <span class="saldo-pend">Queda: ${{ number_format($v->saldo_pendiente, 0, ',', '.') }}</span>
            @endif
        </div>
    </div>
    @endforeach
</div>

<div class="sep-solid"></div>

{{-- Total --}}
<div class="total-row">
    <span>TOTAL ABONADO</span>
    <span>${{ number_format($totalPagado, 0, ',', '.') }}</span>
</div>

<div class="sep-dashed"></div>

<div class="saldo-row {{ $saldoRestante <= 0 ? 'saldo-cero' : 'saldo-pend' }}">
    <span>SALDO TOTAL PENDIENTE:</span>
    <span>
        @if($saldoRestante <= 0)
            CANCELADO
        @else
            ${{ number_format($saldoRestante, 0, ',', '.') }}
        @endif
    </span>
</div>

<div class="sep-solid"></div>

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
