@extends('layouts.app')

@section('title', 'Historial de Ventas')
@section('page-title', 'Historial de Ventas')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-500">Registro completo de todas las transacciones</p>
        @can('ventas.crear')
        <a href="{{ route('ventas.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nueva Venta
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('ventas.index') }}" class="flex flex-wrap gap-3">
                @if($esAdmin && $sucursales->count() > 1)
                <select name="sucursal" class="form-input w-auto">
                    <option value="">Todos los locales</option>
                    @foreach($sucursales as $s)
                    <option value="{{ $s->id }}" {{ request('sucursal') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
                @endif
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="form-input w-auto" title="Desde">
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="form-input w-auto" title="Hasta">
                <input type="text" name="cliente" value="{{ request('cliente') }}" class="form-input min-w-40" placeholder="Buscar cliente...">
                <select name="metodo_pago" class="form-select w-auto">
                    <option value="">Todos los métodos</option>
                    @foreach(['Efectivo','Tarjeta','Transferencia','Crédito','Mixto'] as $m)
                    <option value="{{ $m }}" {{ request('metodo_pago') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary">Filtrar</button>
                @if(request()->hasAny(['fecha_inicio','fecha_fin','cliente','metodo_pago','sucursal']))
                <a href="{{ route('ventas.index') }}" class="btn-secondary">Limpiar</a>
                @endif
            </form>
            @if(!$esAdmin && auth()->user()->sucursal)
            <p class="text-xs text-gray-400 mt-2">
                Mostrando ventas de: <span class="font-medium text-gray-600">{{ auth()->user()->sucursal->nombre }}</span>
            </p>
            @endif
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
            <x-per-page-selector :current="request('per_page', 25)" />
            @can('ventas.ver')<x-export-btn />@endcan
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th>Método</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $venta)
                    <tr>
                        <td>
                            <a href="{{ route('ventas.show', $venta) }}" class="text-blue-600 font-bold hover:underline">
                                #{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}
                            </a>
                        </td>
                        <td class="text-sm text-gray-600">
                            <span class="block">{{ $venta->created_at->format('d/m/Y') }}</span>
                            <span class="text-xs text-gray-400">{{ $venta->created_at->format('H:i') }}</span>
                        </td>
                        <td class="text-gray-700">{{ $venta->cliente?->nombre ?? 'Venta directa' }}</td>
                        <td class="text-gray-600 text-sm">{{ $venta->usuario->nombre }}</td>
                        <td>
                            <span class="badge {{ $venta->es_credito ? 'badge-warning' : 'badge-info' }}">
                                {{ $venta->metodo_pago }}
                            </span>
                        </td>
                        <td class="text-right font-bold text-money">${{ number_format($venta->total, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($venta->anulada)
                                <span class="badge badge-danger">Anulada</span>
                            @elseif(!$venta->es_credito)
                                <span class="badge badge-success">Pagado</span>
                            @elseif($venta->estado_credito === 'Pagado')
                                <span class="badge badge-success">Crédito Saldado</span>
                            @elseif($venta->estado_credito === 'Mora')
                                <span class="badge badge-danger">En Mora</span>
                            @else
                                <span class="badge badge-warning">Parcial</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('ventas.ticket', $venta) }}" class="btn btn-secondary btn-sm" title="Ver ticket">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </a>
                                <a href="{{ route('ventas.show', $venta) }}" class="btn btn-secondary btn-sm">Ver</a>
                                @can('ventas.anular')
                                @if(!$venta->anulada)
                                <div x-data="{ open: false }">
                                    <button type="button" @click="open = true" class="btn btn-danger btn-sm">Anular</button>
                                    <div x-show="open" x-cloak class="modal-overlay" x-transition>
                                        <div class="modal-container max-w-sm" @click.stop>
                                            <div class="modal-header">
                                                <h3 class="card-title text-red-600">Anular Venta #{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}</h3>
                                                <button @click="open = false" class="text-gray-400 hover:text-gray-600">✕</button>
                                            </div>
                                            <form method="POST" action="{{ route('ventas.destroy', $venta) }}">
                                                @csrf @method('DELETE')
                                                <div class="modal-body space-y-3">
                                                    <p class="text-sm text-gray-600">El stock de los productos será restaurado. El registro se conserva para trazabilidad.</p>
                                                    <div class="form-group">
                                                        <label class="form-label">Motivo de anulación (opcional)</label>
                                                        <input type="text" name="motivo_anulacion" class="form-input" placeholder="Ej: Error en precio, devolución...">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" @click="open = false" class="btn btn-secondary">Cancelar</button>
                                                    <button type="submit" class="btn btn-danger">Confirmar Anulación</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-8 text-gray-400">No hay ventas registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">{{ $ventas->links() }}</div>
    </div>

</div>
@endsection
