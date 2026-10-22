<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
    h1 { font-size: 16px; color: #1d4ed8; margin-bottom: 4px; }
    .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 15px; }
    .meta { color: #666; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background-color: #1d4ed8; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) { background-color: #f9fafb; }
    .total-row td { font-weight: bold; background-color: #eff6ff; border-top: 2px solid #1d4ed8; }
    .text-right { text-align: right; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 20px; font-size: 9px; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
    <h1>Reporte de Ventas — Sistema Tienda</h1>
    <p class="meta">Período: {{ $fechaInicio }} al {{ $fechaFin }} | Generado: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Vendedor</th>
            <th>Método</th>
            <th class="text-right">Total</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ventas as $venta)
        <tr>
            <td>{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $venta->cliente?->nombre ?? 'Venta directa' }}</td>
            <td>{{ $venta->usuario->nombre }}</td>
            <td>{{ $venta->metodo_pago }}</td>
            <td class="text-right">${{ number_format($venta->total, 0, ',', '.') }}</td>
            <td>{{ $venta->es_credito ? $venta->estado_credito : 'Pagado' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="5" class="text-right">TOTAL:</td>
            <td class="text-right">${{ number_format($totalVentas, 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

<div class="footer">Sistema Tienda — Reporte generado automáticamente</div>
</body>
</html>
