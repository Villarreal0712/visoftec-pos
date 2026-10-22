<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket #{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</title>
<style>
    /* ── Página térmica {{ $comercio->ancho_ticket ?? '80mm' }} ── */
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

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 2px 0; font-size: 11px; }
    th { border-bottom: 1px dashed #000; text-align: left; font-size: 10px; }
    td.qty  { width: 28px; }
    td.price { width: 55px; text-align: right; }
    td.sub  { width: 65px; text-align: right; font-weight: bold; }

    .total-row { display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; margin: 4px 0; }
    .credito-badge { text-align: center; border: 1px dashed #000; padding: 3px; font-size: 10px; font-weight: bold; margin: 5px 0; }

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
    .no-print .btn-success  { background: #16a34a; color: #fff; border-color: #16a34a; }

    @media print {
        .no-print { display: none !important; }
        body { width: {{ $comercio->ancho_ticket ?? '80mm' }}; }
    }
</style>
<script>
    // Auto-imprimir al cargar — el diálogo se abre solo después de 400ms
    // (tiempo para que la fuente monospace y los estilos carguen)
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 400);
    });
</script>
</head>
<body>

{{-- ── Controles de pantalla (no se imprimen) ── --}}
<div class="no-print">
    <button class="btn-primary" onclick="window.print()">🖨️ Imprimir de nuevo</button>
    <a href="{{ route('ventas.create') }}" class="btn-success">+ Nueva Venta</a>
    <a href="{{ route('ventas.show', $venta) }}">Ver detalle</a>
    <a href="{{ route('ventas.index') }}">Todas las ventas</a>
</div>

{{-- ══════════════════════════════════════
     TICKET  (esto es lo que se imprime)
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

{{-- Tipo de venta --}}
@if($venta->es_credito)
<div class="credito-badge">★  VENTA A CRÉDITO  ★</div>
@else
<div class="center small bold">** COMPROBANTE DE VENTA **</div>
@endif

<div class="sep-dashed"></div>

{{-- Datos de la venta --}}
<div class="row"><span class="label">Ticket N°</span><span class="bold">{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</span></div>
<div class="row"><span class="label">Fecha</span><span>{{ $venta->created_at->format('d/m/Y H:i') }}</span></div>
<div class="row"><span class="label">Atendió</span><span>{{ $venta->usuario->nombre }}</span></div>
@if($venta->cliente)
<div class="row"><span class="label">Cliente</span><span>{{ $venta->cliente->nombre }}</span></div>
@if($venta->cliente->documento)
<div class="row"><span class="label">Cédula</span><span>{{ $venta->cliente->documento }}</span></div>
@endif
@endif
<div class="row"><span class="label">Método pago</span><span>{{ $venta->metodo_pago }}</span></div>
@if($venta->es_credito && $venta->fecha_vencimiento)
<div class="row"><span class="label">Vence crédito</span><span class="bold">{{ $venta->fecha_vencimiento->format('d/m/Y') }}</span></div>
@endif

<div class="sep-dashed"></div>

{{-- Productos --}}
<table>
    <thead>
        <tr>
            <th>Descripción</th>
            <th class="right" style="width:28px">Cant</th>
            <th class="right" style="width:60px">V.Unit</th>
            <th class="right" style="width:65px">Total</th>
        </tr>
    </thead>
    <tbody>
    @foreach($venta->historialVentas as $item)
    @php
        $detalle  = $item->lote->detalleProducto;
        $producto = $detalle->producto;
        $nombre   = $producto->nombre . ($detalle->talla ? ' (' . $detalle->talla . ')' : '');
        $esDir    = $item->lote->tipo_origen === 'Directo';
    @endphp
    <tr>
        <td>
            {{ $nombre }}
            @if($esDir)<br><span class="small">[DIRECTO]</span>@endif
        </td>
        <td class="qty right">{{ $item->cantidad }}</td>
        <td class="price">${{ number_format($item->precio_venta, 0, ',', '.') }}</td>
        <td class="sub">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>

<div class="sep-solid"></div>

{{-- Total --}}
<div class="total-row">
    <span>TOTAL</span>
    <span>${{ number_format($venta->total, 0, ',', '.') }}</span>
</div>

@if($venta->es_credito && $venta->saldo_pendiente > 0)
<div class="sep-dashed"></div>
<div class="row bold"><span>Saldo pendiente:</span><span>${{ number_format($venta->saldo_pendiente, 0, ',', '.') }}</span></div>
@endif

<div class="sep-solid"></div>

{{-- Mensaje de pie --}}
<div class="center small">
    @if($comercio->mensaje_ticket)
    {{ $comercio->mensaje_ticket }}
    @else
    ¡Gracias por su compra!<br>
    Conserve este comprobante
    @endif
</div>

@if($venta->observaciones)
<div class="sep-dashed"></div>
<div class="small center">Obs: {{ $venta->observaciones }}</div>
@endif

<div class="sep-dashed"></div>
<div class="center small" style="color:#555;">
    VISOFTEC POS · Ticket generado {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
