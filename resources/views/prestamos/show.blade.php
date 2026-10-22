@extends('layouts.app')

@section('title', 'Cuenta — ' . $localExterno->nombre_local)
@section('page-title', 'Cuenta: ' . $localExterno->nombre_local)

@section('breadcrumbs')
    <li class="flex items-center gap-2"><span>/</span><a href="{{ route('prestamos.index') }}" class="hover:text-blue-600">Cuentas Externos</a></li>
    <li class="flex items-center gap-2"><span>/</span><span class="text-gray-800 font-medium">{{ $localExterno->nombre_local }}</span></li>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('liquidar', false);
});
</script>
@endpush

@section('content')
<div class="max-w-5xl mx-auto space-y-5">

    {{-- Botón volver --}}
    <a href="{{ route('prestamos.index') }}" class="btn-secondary inline-flex items-center gap-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Volver
    </a>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <p class="text-xs text-gray-500 mb-1">Total vendido</p>
                <p class="text-lg font-extrabold text-gray-900 text-money">${{ number_format($totalVendido, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body text-center py-4">
                <p class="text-xs text-gray-500 mb-1">Deuda calculada</p>
                <p class="text-lg font-extrabold text-money {{ $totalDeuda > 0 ? 'text-red-600' : 'text-gray-400' }}">
                    {{ $totalDeuda > 0 ? '$'.number_format($totalDeuda, 0, ',', '.') : '—' }}
                </p>
                @if($sinCosto > 0)
                <p class="text-xs text-amber-500 mt-0.5">{{ $sinCosto }} sin costo</p>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-body text-center py-4">
                <p class="text-xs text-gray-500 mb-1">Ya pagado</p>
                <p class="text-lg font-extrabold text-emerald-600 text-money">${{ number_format($totalLiquidado, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body text-center py-4">
                <p class="text-xs text-gray-500 mb-1">Saldo pendiente</p>
                <p class="text-lg font-extrabold text-money {{ $saldoPendiente > 0 ? 'text-red-600' : ($saldoPendiente < 0 ? 'text-amber-600' : 'text-emerald-600') }}">
                    ${{ number_format($saldoPendiente, 0, ',', '.') }}
                </p>
                @if($saldoPendiente < 0)
                <p class="text-xs text-amber-600 mt-0.5">Sobrepago: se le pagó de más a este local</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Botón registrar pago --}}
    @can('prestamos.liquidar')
    @if($totalDeuda > 0 && $saldoPendiente > 0)
    <div class="flex justify-end">
        <button @click="$store.liquidar = true"
            class="bg-emerald-500 hover:bg-emerald-400 text-white font-bold px-5 py-2 rounded-xl text-sm flex items-center gap-2 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Registrar pago al local
        </button>
    </div>
    @endif
    @endcan

    {{-- Tabla préstamos pendientes --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ventas pendientes de cuadre</h3>
            <span class="text-xs text-gray-400">{{ $pendientes->count() }} ítem{{ $pendientes->count() !== 1 ? 's' : '' }}</span>
        </div>
        @if($pendientes->isEmpty())
        <div class="card-body text-center py-8 text-gray-400 text-sm">Sin ventas pendientes.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="text-left px-4 py-2.5">Fecha</th>
                        <th class="text-left px-4 py-2.5">Venta</th>
                        <th class="text-left px-4 py-2.5">Producto</th>
                        <th class="text-center px-3 py-2.5">Cant.</th>
                        <th class="text-right px-3 py-2.5">Precio vta.</th>
                        <th class="text-right px-3 py-2.5">Costo acordado</th>
                        <th class="text-right px-4 py-2.5">Deuda</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendientes as $prestamo)
                    <tr class="border-b border-gray-50 hover:bg-gray-50/60 transition-colors">
                        <td class="px-4 py-2.5 text-xs text-gray-400">{{ $prestamo->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('ventas.show', $prestamo->venta) }}"
                               class="text-blue-600 hover:underline font-mono text-xs">
                                #{{ str_pad($prestamo->id_venta, 5, '0', STR_PAD_LEFT) }}
                            </a>
                            @if($prestamo->venta->cliente)
                            <p class="text-xs text-gray-400">{{ $prestamo->venta->cliente->nombre }}</p>
                            @elseif($prestamo->venta->es_credito)
                            <p class="text-xs text-blue-500">A crédito</p>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">
                            <p class="font-medium text-gray-900">{{ $prestamo->detalleProducto->producto->nombre }}</p>
                            @if($prestamo->detalleProducto->talla)
                            <span class="text-xs text-gray-400">Talla: {{ $prestamo->detalleProducto->talla }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-center font-bold text-gray-700">{{ $prestamo->cantidad }}</td>
                        <td class="px-3 py-2.5 text-right text-gray-700 text-money">${{ number_format($prestamo->precio_venta, 0, ',', '.') }}</td>
                        <td class="px-3 py-2.5 text-right">
                            @if($prestamo->costo_acordado !== null)
                            <span class="text-gray-700 text-money">${{ number_format($prestamo->costo_acordado, 0, ',', '.') }}</span>
                            @else
                            {{-- Formulario inline para actualizar costo --}}
                            @can('prestamos.liquidar')
                            <form method="POST" action="{{ route('prestamos.costo', $prestamo) }}"
                                  class="flex items-center justify-end gap-1">
                                @csrf @method('PATCH')
                                <div class="relative w-24" x-data="moneyInput(0)">
                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">$</span>
                                    <input type="text" inputmode="numeric" x-ref="display" @input="update($event)"
                                        placeholder="0"
                                        title="Máx: ${{ number_format($prestamo->precio_venta, 0, ',', '.') }} (precio de venta)"
                                        class="w-full pl-5 pr-1 py-1 text-xs border border-amber-300 bg-amber-50 rounded-lg focus:ring-1 focus:ring-amber-400">
                                    <input type="hidden" name="costo_acordado" :value="raw">
                                </div>
                                <button type="submit" class="text-xs text-amber-600 hover:text-amber-800 font-medium">OK</button>
                            </form>
                            @else
                            <span class="text-xs text-amber-500">Sin definir</span>
                            @endcan
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-money {{ $prestamo->total_deuda !== null ? 'text-red-600' : 'text-gray-300' }}">
                            {{ $prestamo->total_deuda !== null ? '$'.number_format($prestamo->total_deuda, 0, ',', '.') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-200 text-sm font-bold">
                        <td colspan="6" class="px-4 py-3 text-right text-gray-600">Deuda total calculada</td>
                        <td class="px-4 py-3 text-right text-red-600 text-money">${{ number_format($totalDeuda, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    {{-- Historial de pagos --}}
    @if($liquidaciones->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Historial de pagos realizados</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="text-left px-4 py-2.5">Fecha</th>
                        <th class="text-left px-4 py-2.5">Método</th>
                        <th class="text-left px-4 py-2.5">Observaciones</th>
                        <th class="text-left px-4 py-2.5">Registrado por</th>
                        <th class="text-right px-4 py-2.5">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($liquidaciones as $liq)
                    <tr class="border-b border-gray-50 hover:bg-gray-50/60">
                        <td class="px-4 py-2.5 text-xs text-gray-400">{{ $liq->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2.5"><span class="badge badge-info">{{ $liq->metodo_pago }}</span></td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $liq->observaciones ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $liq->usuario?->nombre ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-emerald-600 text-money">${{ number_format($liq->monto, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-200 text-sm font-bold">
                        <td colspan="4" class="px-4 py-3 text-right text-gray-600">Total pagado</td>
                        <td class="px-4 py-3 text-right text-emerald-600 text-money">${{ number_format($totalLiquidado, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

</div>

{{-- Modal registrar pago --}}
<div x-show="$store.liquidar"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-end="opacity-0"
     style="display:none">

    <div class="absolute inset-0 bg-black/50" @click="$store.liquidar = false"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0 scale-95">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h2 class="text-base font-bold text-gray-900">Registrar pago</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $localExterno->nombre_local }}</p>
            </div>
            <button @click="$store.liquidar = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('prestamos.liquidar', $localExterno) }}">
            @csrf
            <div class="px-6 py-5 space-y-4">

                {{-- Monto --}}
                <div class="form-group">
                    <label class="form-label">Monto pagado *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">$</span>
                        <div x-data="moneyInput({{ old('monto', $saldoPendiente > 0 ? (int) $saldoPendiente : 0) }})">
                            <input type="text" inputmode="numeric" x-ref="display" @input="update($event)"
                                required class="form-input pl-7" placeholder="0">
                            <input type="hidden" name="monto" :value="raw">
                        </div>
                    </div>
                </div>

                {{-- Método de pago --}}
                <div class="form-group">
                    <label class="form-label">Método de pago *</label>
                    <div class="flex gap-2 flex-wrap">
                        @foreach(['Efectivo', 'Tarjeta', 'Transferencia'] as $metodo)
                        <label class="cursor-pointer">
                            <input type="radio" name="metodo_pago" value="{{ $metodo }}" class="sr-only peer" {{ $metodo === 'Efectivo' ? 'checked' : '' }}>
                            <span class="inline-block px-3 py-1.5 text-xs font-medium rounded-full border-2 transition-colors cursor-pointer
                                peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700
                                border-gray-200 text-gray-500 hover:border-gray-300">{{ $metodo }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Observaciones --}}
                <div class="form-group">
                    <label class="form-label">Observaciones</label>
                    <input type="text" name="observaciones"
                        placeholder="Ej: Cuadre semana 16-22 abr"
                        class="form-input" value="{{ old('observaciones') }}">
                </div>

            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3">
                <button type="button" @click="$store.liquidar = false" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-white font-bold px-5 py-2 rounded-xl text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Registrar pago
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
