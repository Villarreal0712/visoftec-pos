@extends('layouts.app')

@section('title', 'Cuadre de Caja')
@section('page-title', 'Cuadre de Caja — ' . $cuadreCaja->fecha_apertura->format('d/m/Y'))

@section('content')
<div class="space-y-5 max-w-5xl mx-auto">

    <div class="flex flex-wrap gap-3">
        @if($cuadreCaja->estado === 'Cerrado')
        <a href="{{ route('cuadre-caja.imprimir', $cuadreCaja) }}" target="_blank" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimir
        </a>
        @endif
        <a href="{{ route('cuadre-caja.index') }}" class="btn-secondary">← Volver</a>
    </div>

    {{-- Resumen financiero --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div>
                <p class="stat-label">Ventas Efectivo</p>
                <p class="stat-value text-emerald-600 text-money text-lg">${{ number_format($ventasEfectivo, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <p class="stat-label">Ventas Tarjeta/Transf.</p>
                <p class="stat-value text-blue-600 text-money text-lg">${{ number_format($ventasTarjeta + $ventasTransferencia, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <p class="stat-label">Cobros Crédito</p>
                <p class="stat-value text-amber-600 text-money text-lg">${{ number_format($pagosCredito, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-red-100">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="stat-label">Gastos</p>
                <p class="stat-value text-red-600 text-money text-lg">${{ number_format($gastos, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Resumen total + cierre --}}
    <div class="grid md:grid-cols-2 gap-5">
        <div class="card bg-gray-900 text-white">
            <div class="card-body space-y-3">
                <h3 class="font-bold text-gray-300 text-sm uppercase tracking-wider">Resumen del Día</h3>
                <div class="flex justify-between text-sm"><span class="text-gray-400">Saldo inicial</span><span class="text-money">${{ number_format($cuadreCaja->saldo_inicial, 0, ',', '.') }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-400">+ Ventas efectivo</span><span class="text-emerald-400 text-money">+${{ number_format($ventasEfectivo, 0, ',', '.') }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-400">+ Ventas tarjeta/transf.</span><span class="text-blue-400 text-money">+${{ number_format($ventasTarjeta + $ventasTransferencia, 0, ',', '.') }}</span></div>
                @if($ventasMixto > 0)
                <div class="flex justify-between text-sm"><span class="text-gray-400">+ Ventas mixto</span><span class="text-purple-400 text-money">+${{ number_format($ventasMixto, 0, ',', '.') }}</span></div>
                @if($mixtoEfectivo > 0)
                <div class="flex justify-between text-xs pl-3"><span class="text-gray-500">en efectivo / por transferencia</span><span class="text-gray-400 text-money">${{ number_format($mixtoEfectivo, 0, ',', '.') }} / ${{ number_format($mixtoElectronico, 0, ',', '.') }}</span></div>
                @endif
                @endif
                <div class="flex justify-between text-sm"><span class="text-gray-400">+ Cobros crédito</span><span class="text-amber-400 text-money">+${{ number_format($pagosCredito, 0, ',', '.') }}</span></div>
                {{-- Solo los gastos en efectivo restan del total; los pagados desde una cuenta Banco no salen de la caja --}}
                <div class="flex justify-between text-sm"><span class="text-gray-400">− Gastos en efectivo</span><span class="text-red-400 text-money">-${{ number_format($gastosEfectivo, 0, ',', '.') }}</span></div>
                @if($gastos > $gastosEfectivo)
                <div class="flex justify-between text-xs pl-3"><span class="text-gray-500">+ pagados desde cuenta bancaria (no afectan la caja)</span><span class="text-gray-400 text-money">${{ number_format($gastos - $gastosEfectivo, 0, ',', '.') }}</span></div>
                @endif
                @if($liquidacionesExternas > 0)
                <div class="flex justify-between text-sm"><span class="text-gray-400">− Pagos a externos</span><span class="text-orange-400 text-money">-${{ number_format($liquidacionesExternas, 0, ',', '.') }}</span></div>
                @endif
                <div class="border-t border-gray-700 pt-3 flex justify-between font-extrabold text-xl">
                    <span>TOTAL SISTEMA</span>
                    <span class="text-money">${{ number_format($totalSistema, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Cierre de caja --}}
        @if($cuadreCaja->estado === 'Abierto')
        @can('caja.cerrar')
        @php
            // Mismo total que totalSistema, repartido entre cajón y transferencias:
            // - la parte en efectivo de las ventas mixtas va al cajón, el resto a transferencias;
            // - los abonos y gastos van al lado por el que realmente entró/salió la plata.
            $efectivoEsperado    = $cuadreCaja->saldo_inicial + $ventasEfectivo + $mixtoEfectivo
                                 + $pagosCreditoEfectivo - $gastosEfectivo - $liquidacionesExternas;
            $electronicoEsperado = $ventasTarjeta + $ventasTransferencia + $mixtoElectronico + $pagosCreditoElectronico;
        @endphp
        <div class="card">
            <div class="card-header"><h3 class="card-title">Cerrar Caja</h3></div>
            <div class="card-body">
                {{-- Guía de referencia para el cajero --}}
                <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl mb-5 space-y-1 text-sm">
                    <p class="font-semibold text-blue-700 text-xs uppercase tracking-wide mb-2">Referencia del sistema</p>
                    <div class="flex justify-between text-blue-800">
                        <span>Efectivo esperado en cajón:</span>
                        <span class="font-bold text-money">${{ number_format($efectivoEsperado, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-blue-800">
                        <span>Transferencias esperadas (tarjeta/Nequi):</span>
                        <span class="font-bold text-money">${{ number_format($electronicoEsperado, 0, ',', '.') }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('cuadre-caja.cerrar', $cuadreCaja) }}" class="space-y-5"
                      x-data="desgloseBilletes()" x-init="init()">
                    @csrf @method('PUT')

                    {{-- Desglose de billetes (efectivo físico) --}}
                    <div>
                        <p class="form-label mb-2">Desglose de Billetes y Monedas (efectivo) *</p>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <template x-for="denom in denominaciones" :key="denom">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-mono w-24 text-gray-600">$<span x-text="denom.toLocaleString('es-CO')"></span></span>
                                    <input type="number" :name="'desglose_billetes[' + denom + ']'" x-model.number="cantidades[denom]" min="0" class="form-input w-20 text-center" placeholder="0">
                                    <span class="text-sm text-gray-500">= $<span x-text="((cantidades[denom] || 0) * denom).toLocaleString('es-CO')"></span></span>
                                </div>
                            </template>
                        </div>
                        <div class="mt-3 p-2 bg-emerald-50 rounded-lg flex justify-between text-sm font-semibold text-emerald-700">
                            <span>Subtotal efectivo:</span>
                            <span class="text-money">$<span x-text="total.toLocaleString('es-CO')"></span></span>
                        </div>
                        <input type="hidden" name="efectivo_contado" :value="total">
                    </div>

                    {{-- Transferencias / Nequi / Daviplata / Tarjeta --}}
                    <div class="form-group">
                        <label class="form-label">
                            Transferencias / Tarjeta / Nequi / Daviplata recibidas
                            <span class="text-gray-400 font-normal text-xs ml-1">(verificado en cuenta bancaria)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input type="number" name="transferencia_contada" x-model.number="transferencias"
                                   class="form-input pl-6 text-money" step="100" min="0" placeholder="0">
                        </div>
                    </div>

                    {{-- Total combinado --}}
                    <div class="p-3 bg-gray-900 rounded-xl flex justify-between font-extrabold text-white text-lg">
                        <span>Total contado:</span>
                        <span class="text-money">$<span x-text="(total + (transferencias || 0)).toLocaleString('es-CO')"></span></span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Justificación de diferencia (si aplica)</label>
                        <input type="text" name="justificacion" class="form-input" placeholder="Explica diferencias encontradas">
                    </div>

                    <button type="submit" class="btn-danger w-full"
                            onclick="return confirm('¿Confirmar cierre de caja? No podrás reabrir este cuadre.')">
                        Cerrar Caja y Guardar Cuadre
                    </button>
                </form>
            </div>
        </div>
        @endcan
        @else
        <div class="card">
            <div class="card-header"><h3 class="card-title">Resultado del Cuadre</h3></div>
            <div class="card-body space-y-4">
                <div class="flex justify-between"><span class="text-gray-500 text-sm">Total sistema</span><span class="font-bold text-money">${{ number_format($cuadreCaja->total_sistema, 0, ',', '.') }}</span></div>
                @if($cuadreCaja->efectivo_contado > 0 || $cuadreCaja->transferencia_contada > 0)
                <div class="flex justify-between text-sm"><span class="text-gray-400 pl-2">↳ Efectivo contado</span><span class="text-money">${{ number_format($cuadreCaja->efectivo_contado, 0, ',', '.') }}</span></div>
                @if($cuadreCaja->transferencia_contada > 0)
                <div class="flex justify-between text-sm"><span class="text-gray-400 pl-2">↳ Transf./Tarjeta contadas</span><span class="text-money">${{ number_format($cuadreCaja->transferencia_contada, 0, ',', '.') }}</span></div>
                @endif
                @endif
                <div class="flex justify-between border-t pt-2"><span class="text-gray-500 text-sm">Total contado</span><span class="font-bold text-money">${{ number_format($cuadreCaja->total_contado, 0, ',', '.') }}</span></div>
                <div class="flex justify-between border-t pt-3">
                    <span class="font-bold">Diferencia</span>
                    <span class="font-extrabold text-lg text-money {{ $cuadreCaja->diferencia < 0 ? 'text-red-600' : ($cuadreCaja->diferencia > 0 ? 'text-emerald-600' : 'text-gray-600') }}">
                        {{ $cuadreCaja->diferencia >= 0 ? '+' : '' }}${{ number_format($cuadreCaja->diferencia, 0, ',', '.') }}
                    </span>
                </div>
                @if($cuadreCaja->justificacion)
                <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-600">
                    <p class="font-medium text-xs text-gray-400 mb-1">Justificación:</p>
                    {{ $cuadreCaja->justificacion }}
                </div>
                @endif
                <p class="text-xs text-gray-400">Cerrado: {{ $cuadreCaja->fecha_cierre?->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Desglose por cuenta bancaria — informativo, no afecta el total sistema --}}
    @if($desglosePorCuenta->isNotEmpty())
    <div class="card">
        <div class="card-header"><h3 class="card-title text-sm">Desglose por Cuenta Bancaria</h3></div>
        <div class="card-body space-y-2">
            @foreach($desglosePorCuenta as $item)
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">📦 {{ $item['nombre'] }}</span>
                <span class="font-semibold text-money">${{ number_format($item['total'], 0, ',', '.') }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Detalle de transacciones --}}
    <div class="grid lg:grid-cols-3 gap-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title text-sm">Ventas del Día ({{ $ventas->count() }})</h3></div>
            <div class="overflow-y-auto max-h-64">
                @foreach($ventas->take(20) as $v)
                <div class="flex justify-between px-4 py-2 border-b border-gray-50 text-sm">
                    <span class="text-gray-600">{{ $v->cliente?->nombre ?? 'Directo' }} · {{ $v->descripcionPago() }}</span>
                    <span class="font-medium text-money">${{ number_format($v->total, 0, ',', '.') }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title text-sm">Cobros Crédito ({{ $pagosDetalle->count() }})</h3></div>
            <div class="overflow-y-auto max-h-64">
                @foreach($pagosDetalle as $p)
                <div class="flex justify-between px-4 py-2 border-b border-gray-50 text-sm">
                    <span class="text-gray-600">{{ $p->venta->cliente?->nombre ?? 'N/A' }} · {{ $p->metodo }}</span>
                    <span class="font-medium text-money text-amber-600">${{ number_format($p->monto, 0, ',', '.') }}</span>
                </div>
                @endforeach
                @if($pagosDetalle->isEmpty())<p class="text-center text-gray-400 text-sm py-4">Sin cobros</p>@endif
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title text-sm">Gastos ({{ $gastosDetalle->count() }})</h3></div>
            <div class="overflow-y-auto max-h-64">
                @foreach($gastosDetalle as $g)
                <div class="flex justify-between px-4 py-2 border-b border-gray-50 text-sm">
                    <span class="text-gray-600 truncate">{{ Str::limit($g->descripcion, 25) }}</span>
                    <span class="font-medium text-money text-red-600 ml-2">-${{ number_format($g->valor, 0, ',', '.') }}</span>
                </div>
                @endforeach
                @if($gastosDetalle->isEmpty())<p class="text-center text-gray-400 text-sm py-4">Sin gastos</p>@endif
            </div>
        </div>
    </div>

</div>
@endsection
