<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Estado de Cuenta — {{ $cliente->nombre }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; padding: 20px; }
    .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: flex-start; }
    .header h1 { font-size: 16px; color: #1d4ed8; }
    .header .info { text-align: right; font-size: 10px; color: #666; }
    .cliente-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; }
    .cliente-box h2 { font-size: 14px; font-weight: bold; }
    .cliente-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 6px; font-size: 10px; color: #374151; }
    .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; background: #f3f4f6; padding: 5px 8px; margin: 12px 0 6px; border-left: 3px solid #1d4ed8; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #1d4ed8; color: white; padding: 5px 8px; text-align: left; font-size: 10px; }
    td { padding: 4px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .resumen { background: #1e3a5f; color: white; padding: 12px 14px; border-radius: 6px; margin-top: 16px; }
    .resumen-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 11px; }
    .resumen-row.big { font-size: 14px; font-weight: bold; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 8px; margin-top: 6px; }
    .tag { display: inline-block; padding: 1px 6px; border-radius: 20px; font-size: 9px; }
    .tag-green { background: #d1fae5; color: #065f46; }
    .tag-yellow { background: #fef3c7; color: #92400e; }
    .tag-red { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 24px; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print" style="text-align:center;margin-bottom:16px;">
    <button onclick="window.print()" style="background:#1d4ed8;color:white;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px;">
        Imprimir Estado de Cuenta
    </button>
    <a href="{{ route('clientes.show', $cliente) }}" style="margin-left:10px;color:#6b7280;font-size:12px;">← Volver</a>
</div>

<div class="header">
    <div>
        <h1>Estado de Cuenta</h1>
        <p style="color:#666;font-size:10px;margin-top:3px;">Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <div class="info">
        <p><strong>Sistema Tienda</strong></p>
        <p>Documento confidencial</p>
    </div>
</div>

<div class="cliente-box">
    <h2>{{ $cliente->nombre }}</h2>
    <div class="cliente-grid">
        <div><strong>Documento:</strong> {{ $cliente->documento }}</div>
        <div><strong>Teléfono:</strong> {{ $cliente->telefono ?? '—' }}</div>
        <div><strong>Correo:</strong> {{ $cliente->correo ?? '—' }}</div>
        <div><strong>Dirección:</strong> {{ $cliente->direccion ?? '—' }}</div>
        <div><strong>Cliente desde:</strong> {{ $cliente->created_at->format('d/m/Y') }}</div>
        <div><strong>Límite crédito:</strong> ${{ number_format($cliente->limite_credito ?? 0, 0, ',', '.') }}</div>
    </div>
</div>

{{-- Créditos pendientes --}}
@if($ventasCredito->count())
<div class="section-title">Créditos Pendientes</div>
<table>
    <thead>
        <tr>
            <th>#Venta</th>
            <th>Fecha</th>
            <th>Vencimiento</th>
            <th class="text-right">Total Venta</th>
            <th class="text-right">Abonado</th>
            <th class="text-right">Saldo</th>
            <th class="text-center">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ventasCredito as $venta)
        @php $abonado = $venta->pagosCredito->sum('monto'); @endphp
        <tr>
            <td>#{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $venta->created_at->format('d/m/Y') }}</td>
            <td>{{ $venta->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
            <td class="text-right">${{ number_format($venta->total, 0, ',', '.') }}</td>
            <td class="text-right">${{ number_format($abonado, 0, ',', '.') }}</td>
            <td class="text-right" style="font-weight:bold;color:#991b1b;">${{ number_format($venta->saldo_pendiente, 0, ',', '.') }}</td>
            <td class="text-center">
                <span class="tag {{ $venta->estado_credito === 'Mora' ? 'tag-red' : 'tag-yellow' }}">{{ $venta->estado_credito }}</span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Historial de abonos --}}
@php $todosAbonos = $ventasCredito->flatMap->pagosCredito->sortByDesc('created_at'); @endphp
@if($todosAbonos->count())
<div class="section-title">Historial de Abonos</div>
<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>#Venta</th>
            <th>Método</th>
            <th class="text-right">Monto</th>
            <th>Observación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($todosAbonos as $abono)
        <tr>
            <td>{{ $abono->created_at->format('d/m/Y H:i') }}</td>
            <td>#{{ str_pad($abono->id_venta, 5, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $abono->metodo }}</td>
            <td class="text-right" style="color:#065f46;font-weight:bold;">${{ number_format($abono->monto, 0, ',', '.') }}</td>
            <td>{{ $abono->observacion ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Resumen --}}
<div class="resumen">
    <div class="resumen-row"><span>Total compras histórico:</span><span>${{ number_format($cliente->ventas->sum('total'), 0, ',', '.') }}</span></div>
    <div class="resumen-row"><span>Total abonado:</span><span>${{ number_format($todosAbonos->sum('monto'), 0, ',', '.') }}</span></div>
    <div class="resumen-row big"><span>SALDO PENDIENTE:</span><span>${{ number_format($saldoPendiente, 0, ',', '.') }}</span></div>
</div>

<div class="footer">
    Este estado de cuenta fue generado automáticamente por el Sistema Tienda el {{ now()->format('d/m/Y \a \l\a\s H:i') }}.
    Los valores mostrados son a la fecha de generación y pueden cambiar si se registran nuevos abonos o ventas.
</div>

</body>
</html>
