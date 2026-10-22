@extends('layouts.app')

@section('title', 'Cuenta — ' . $cuenta->nombre)
@section('page-title', 'Cuenta — ' . $cuenta->nombre)

@section('content')
<div class="max-w-4xl mx-auto space-y-5" x-data="{ modalMovimiento: false, tipoMovimiento: 'Consignacion' }">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('cuentas-bancarias.index') }}" class="hover:text-gray-700">Cuentas Bancarias</a>
        <span>/</span>
        <span class="text-gray-900 font-medium">{{ $cuenta->nombre }}</span>
    </div>

    @if(session('success'))
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Saldo actual --}}
    <div class="card border-2 border-emerald-200">
        <div class="card-body flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Saldo actual</p>
                <p class="text-3xl font-bold text-emerald-700 text-money">$ {{ number_format($cuenta->saldo_actual, 0, ',', '.') }}</p>
                @if($cuenta->banco)
                <p class="text-xs text-gray-400 mt-0.5">{{ $cuenta->banco }}</p>
                @endif
            </div>
            @can('cuentas.gestionar')
            <div class="flex gap-2">
                <button @click="tipoMovimiento = 'Consignacion'; modalMovimiento = true" class="btn-success btn-sm">+ Consignación</button>
                <button @click="tipoMovimiento = 'Retiro'; modalMovimiento = true" class="btn-danger btn-sm">− Retiro</button>
            </div>
            @endcan
        </div>
    </div>

    {{-- Historial de movimientos --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Movimientos</h3></div>
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Referencia</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Observación</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Monto</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($movimientos as $mov)
                        @php
                            $esEntrada = in_array($mov->tipo, ['Venta', 'Abono', 'Consignacion']);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $esEntrada ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $mov->tipo }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($mov->venta)
                                <a href="{{ route('ventas.show', $mov->venta) }}" class="text-blue-600 hover:underline">Venta #{{ str_pad($mov->venta->id, 5, '0', STR_PAD_LEFT) }}</a>
                                @else
                                <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $mov->observacion ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $mov->usuario->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm font-mono tabular-nums font-semibold {{ $esEntrada ? 'text-green-700' : 'text-red-600' }}">
                                {{ $esEntrada ? '+' : '−' }}$ {{ number_format($mov->monto, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-mono tabular-nums text-gray-500">
                                $ {{ number_format($mov->saldo_resultante, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-8 text-gray-400">Sin movimientos registrados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">{{ $movimientos->links() }}</div>
    </div>

    {{-- Modal registrar movimiento --}}
    @can('cuentas.gestionar')
    <div x-show="modalMovimiento" x-cloak class="modal-overlay" x-transition style="display:none">
        <div class="modal-container max-w-sm" @click.stop>
            <div class="modal-header">
                <h3 class="card-title" x-text="tipoMovimiento === 'Consignacion' ? 'Registrar consignación' : 'Registrar retiro'"></h3>
                <button @click="modalMovimiento = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <form method="POST" action="{{ route('cuentas-bancarias.movimiento', $cuenta) }}">
                @csrf
                <input type="hidden" name="tipo" :value="tipoMovimiento">
                <div class="modal-body space-y-3">
                    <p class="text-sm text-gray-600" x-show="tipoMovimiento === 'Consignacion'">
                        Dinero que entró a esta cuenta (ej. efectivo de caja que se llevó al banco).
                    </p>
                    <p class="text-sm text-gray-600" x-show="tipoMovimiento === 'Retiro'">
                        Dinero que salió de esta cuenta. Saldo disponible: <span class="font-bold text-money">${{ number_format($cuenta->saldo_actual, 0, ',', '.') }}</span>
                    </p>
                    <div class="form-group">
                        <label class="form-label">Monto *</label>
                        <div class="relative" x-data="moneyInput(0)">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input type="text" inputmode="numeric" x-ref="display" @input="update($event)"
                                class="form-input pl-6" required placeholder="0">
                            <input type="hidden" name="monto" :value="raw">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Observación (opcional)</label>
                        <input type="text" name="observacion" maxlength="255" class="form-input" placeholder="Ej: Consignación de cierre del día...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="modalMovimiento = false" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

</div>
@endsection
