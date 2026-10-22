<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
    h1 { font-size: 16px; color: #b91c1c; margin-bottom: 4px; }
    .header { border-bottom: 2px solid #b91c1c; padding-bottom: 10px; margin-bottom: 15px; }
    .meta { color: #666; font-size: 10px; }
    .summary { background: #fee2e2; border: 1px solid #fca5a5; padding: 8px 12px; border-radius: 6px; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { background-color: #b91c1c; color: white; padding: 6px 8px; text-align: left; font-size: 10px; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) { background-color: #fff7f7; }
    .text-right { text-align: right; }
    .text-red { color: #b91c1c; font-weight: bold; }
    .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #9ca3af; }
</style>
</head>
<body>
<div class="header">
    <h1>Cartera de Clientes — Deudas Pendientes</h1>
    <p class="meta">Generado: {{ now()->format('d/m/Y H:i') }} | Sistema Tienda</p>
</div>

<div class="summary">
    <strong>Total cartera pendiente: ${{ number_format($totalCartera, 0, ',', '.') }}</strong>
    | {{ $clientes->count() }} clientes con saldo
</div>

<table>
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Documento</th>
            <th>Teléfono</th>
            <th class="text-right">Deuda Pendiente</th>
        </tr>
    </thead>
    <tbody>
        @foreach($clientes as $cliente)
        @php $deuda = $cliente->ventas->sum('saldo_pendiente'); @endphp
        <tr>
            <td>{{ $cliente->nombre }}</td>
            <td>{{ $cliente->documento }}</td>
            <td>{{ $cliente->telefono ?? '—' }}</td>
            <td class="text-right text-red">${{ number_format($deuda, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">Sistema Tienda — Reporte de Cartera generado automáticamente</div>
</body>
</html>
