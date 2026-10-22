<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cuadre de Caja — {{ $cuadreCaja->fecha_apertura->format('d/m/Y') }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #222; padding: 20px; }
    .header { text-align: center; border-bottom: 2px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 16px; }
    .header h1 { font-size: 18px; color: #1d4ed8; }
    .header p { color: #666; font-size: 10px; margin-top: 3px; }
    .section { margin-bottom: 16px; }
    .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; color: #374151; background: #f3f4f6; padding: 5px 8px; margin-bottom: 8px; border-left: 3px solid #1d4ed8; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #1d4ed8; color: white; padding: 5px 8px; text-align: left; font-size: 10px; }
    td { padding: 4px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .text-right { text-align: right; }
    .summary-box { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; background: #f9fafb; }
    .summary-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 11px; }
    .summary-row.total { font-weight: bold; font-size: 13px; border-top: 1px solid #9ca3af; padding-top: 6px; margin-top: 4px; }
    .summary-row.diff-pos { color: #065f46; font-weight: bold; }
    .summary-row.diff-neg { color: #991b1b; font-weight: bold; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .footer { margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
    .firma { border-top: 1px solid #374151; padding-top: 5px; text-align: center; font-size: 10px; color: #6b7280; }
    @media print {
        body { padding: 10px; }
        .no-print { display: none; }
    }
</style>
</head>
<body>

<div class="no-print" style="text-align:center; margin-bottom:16px;">
    <button onclick="window.print()" style="background:#1d4ed8;color:white;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px;">
        Imprimir Cuadre
    </button>
    <a href="{{ route('cuadre-caja.show', $cuadreCaja) }}" style="margin-left:10px;color:#6b7280;font-size:12px;">← Volver</a>
</div>

<div class="header">
    <h1>Cuadre de Caja Diario</h1>
    <p>Fecha: {{ $cuadreCaja->fecha_apertura->format('d/m/Y') }} | Cajero: {{ $cuadreCaja->usuario->nombre }}</p>
    <p>Apertura: {{ $cuadreCaja->fecha_apertura->format('H:i') }}
       @if($cuadreCaja->fecha_cierre) | Cierre: {{ $cuadreCaja->fecha_cierre->format('H:i') }} @endif
    </p>
</div>

<div class="two-col">
    {{-- Columna izquierda: Ingresos --}}
    <div>
        <div class="section">
            <div class="section-title">Ventas al Contado</div>
            <table>
                <thead><tr><th>Cliente</th><th>Método</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                    @foreach($ventas->where('es_credito', false) as $v)
                    <tr>
                        <td>{{ $v->cliente?->nombre ?? 'Directo' }}</td>
                        <td>{{ $v->metodo_pago }}</td>
                        <td class="text-right">${{ number_format($v->total, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    @if($ventas->where('es_credito', false)->isEmpty())
                    <tr><td colspan="3" style="text-align:center;color:#9ca3af;">Sin ventas</td></tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Cobros a Crédito</div>
            <table>
                <thead><tr><th>Cliente</th><th>Método</th><th class="text-right">Monto</th></tr></thead>
                <tbody>
                    @foreach($pagosDetalle as $p)
                    <tr>
                        <td>{{ $p->venta->cliente?->nombre ?? '—' }}</td>
                        <td>{{ $p->metodo }}</td>
                        <td class="text-right">${{ number_format($p->monto, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    @if($pagosDetalle->isEmpty())
                    <tr><td colspan="3" style="text-align:center;color:#9ca3af;">Sin cobros</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Columna derecha: Gastos y Resumen --}}
    <div>
        <div class="section">
            <div class="section-title">Gastos del Día</div>
            <table>
                <thead><tr><th>Descripción</th><th class="text-right">Valor</th></tr></thead>
                <tbody>
                    @foreach($gastosDetalle as $g)
                    <tr>
                        <td>{{ $g->descripcion }}</td>
                        <td class="text-right">${{ number_format($g->valor, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    @if($gastosDetalle->isEmpty())
                    <tr><td colspan="2" style="text-align:center;color:#9ca3af;">Sin gastos</td></tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Resumen Final</div>
            <div class="summary-box">
                <div class="summary-row"><span>Saldo inicial:</span><span>${{ number_format($cuadreCaja->saldo_inicial, 0, ',', '.') }}</span></div>
                <div class="summary-row"><span>+ Ventas efectivo:</span><span>${{ number_format($ventasEfectivo, 0, ',', '.') }}</span></div>
                <div class="summary-row"><span>+ Ventas tarjeta/transf.:</span><span>${{ number_format($ventasTarjeta + $ventasTransferencia, 0, ',', '.') }}</span></div>
                @if($ventasMixto > 0)
                <div class="summary-row"><span>+ Ventas mixto:</span><span>${{ number_format($ventasMixto, 0, ',', '.') }}</span></div>
                @endif
                <div class="summary-row"><span>+ Cobros crédito:</span><span>${{ number_format($pagosCredito, 0, ',', '.') }}</span></div>
                <div class="summary-row"><span>− Gastos:</span><span>-${{ number_format($gastos, 0, ',', '.') }}</span></div>
                @if($liquidacionesExternas > 0)
                <div class="summary-row"><span>− Pagos a externos:</span><span>-${{ number_format($liquidacionesExternas, 0, ',', '.') }}</span></div>
                @endif
                <div class="summary-row total"><span>TOTAL SISTEMA:</span><span>${{ number_format($totalSistema, 0, ',', '.') }}</span></div>
                @if($cuadreCaja->estado === 'Cerrado')
                @if($cuadreCaja->efectivo_contado > 0 || $cuadreCaja->transferencia_contada > 0)
                <div class="summary-row" style="font-size:10px;color:#6b7280;"><span>↳ Efectivo contado:</span><span>${{ number_format($cuadreCaja->efectivo_contado, 0, ',', '.') }}</span></div>
                @if($cuadreCaja->transferencia_contada > 0)
                <div class="summary-row" style="font-size:10px;color:#6b7280;"><span>↳ Transf./Tarjeta contadas:</span><span>${{ number_format($cuadreCaja->transferencia_contada, 0, ',', '.') }}</span></div>
                @endif
                @endif
                <div class="summary-row"><span>Total contado:</span><span>${{ number_format($cuadreCaja->total_contado, 0, ',', '.') }}</span></div>
                <div class="summary-row {{ $cuadreCaja->diferencia >= 0 ? 'diff-pos' : 'diff-neg' }}">
                    <span>Diferencia:</span>
                    <span>{{ $cuadreCaja->diferencia >= 0 ? '+' : '' }}${{ number_format($cuadreCaja->diferencia, 0, ',', '.') }}</span>
                </div>
                @if($cuadreCaja->justificacion)
                <p style="font-size:9px;color:#6b7280;margin-top:5px;font-style:italic;">Justificación: {{ $cuadreCaja->justificacion }}</p>
                @endif
                @endif
            </div>
        </div>

        @if($desglosePorCuenta->isNotEmpty())
        <div class="section">
            <div class="section-title">Desglose por Cuenta Bancaria</div>
            <div class="summary-box">
                @foreach($desglosePorCuenta as $item)
                <div class="summary-row"><span>{{ $item['nombre'] }}:</span><span>${{ number_format($item['total'], 0, ',', '.') }}</span></div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<div class="footer">
    <div class="firma">
        <br><br>
        ___________________________________<br>
        Firma del Cajero<br>
        {{ $cuadreCaja->usuario->nombre }}
    </div>
    <div class="firma">
        <br><br>
        ___________________________________<br>
        Firma del Supervisor<br>
        &nbsp;
    </div>
</div>

</body>
</html>
