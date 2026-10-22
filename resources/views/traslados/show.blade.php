@extends('layouts.app')

@section('title', 'Traslado ' . $traslado->numero_traslado)
@section('page-title', 'Detalle Traslado')

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    <div class="flex items-center justify-between">
        <a href="{{ route('traslados.index') }}" class="btn-secondary btn-sm">← Volver</a>
        <span class="font-mono text-lg font-bold text-blue-600">{{ $traslado->numero_traslado }}</span>
    </div>

    {{-- Info general --}}
    <div class="card">
        <div class="card-body">
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-3">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Bodega Origen</p>
                    <p class="font-semibold text-gray-900">{{ $traslado->bodegaOrigen->nombre ?? '—' }}</p>
                    @if($traslado->bodegaOrigen?->sucursal)
                    <p class="text-xs text-gray-500">{{ $traslado->bodegaOrigen->sucursal->nombre }}</p>
                    @endif
                </div>
                <div class="flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Bodega Destino</p>
                    <p class="font-semibold text-gray-900">{{ $traslado->bodegaDestino->nombre ?? '—' }}</p>
                    @if($traslado->bodegaDestino?->sucursal)
                    <p class="text-xs text-gray-500">{{ $traslado->bodegaDestino->sucursal->nombre }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-5 pt-5 border-t border-gray-100">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Registrado por</p>
                    <p class="text-sm text-gray-900">{{ $traslado->usuario->nombre ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Fecha</p>
                    <p class="text-sm text-gray-900">{{ $traslado->created_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($traslado->observacion)
                <div class="col-span-2">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Observación</p>
                    <p class="text-sm text-gray-700">{{ $traslado->observacion }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Detalle de productos --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Productos trasladados</h3>
        </div>
        <div class="card-body p-0">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Producto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Cantidad</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Costo Unitario</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($traslado->detalles as $d)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $d->detalleProducto?->producto?->nombre ?? '—' }}</p>
                            @if($d->detalleProducto?->talla)
                            <p class="text-xs text-gray-500">Talla: {{ $d->detalleProducto->talla }}</p>
                            @endif
                            <p class="text-xs text-gray-400 font-mono">{{ $d->detalleProducto?->codigo_barras }}</p>
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-semibold">{{ $d->cantidad }}</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">
                            ${{ number_format($d->costo_unitario, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-semibold">
                            ${{ number_format($d->cantidad * $d->costo_unitario, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total costo traslado</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                            ${{ number_format($traslado->detalles->sum(fn($d) => $d->cantidad * $d->costo_unitario), 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
