@extends('layouts.app')

@section('title', 'Historial de Movimientos')
@section('page-title', 'Historial: ' . $detalle->producto->nombre . ' — ' . ($detalle->talla ?? 'Única'))

@section('content')
<div class="space-y-5">

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('inventario.index') }}" class="btn-secondary">← Volver al Inventario</a>
        @can('inventario.entradas')
        <a href="{{ route('inventario.entrada') }}?detalle={{ $detalle->id }}" class="btn-success btn-sm">+ Registrar Entrada</a>
        @endcan
    </div>

    {{-- Resumen del producto --}}
    <div class="grid sm:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div>
                <p class="stat-label">Stock Actual</p>
                <p class="stat-value text-money text-blue-700">{{ $stockActual }}</p>
                <p class="text-xs text-gray-400">unidades disponibles</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </div>
            <div>
                <p class="stat-label">Total Entradas</p>
                <p class="stat-value text-money text-emerald-600">{{ $totalEntradas }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-red-100">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            </div>
            <div>
                <p class="stat-label">Total Salidas</p>
                <p class="stat-value text-money text-red-600">{{ $totalSalidas }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-amber-100">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="stat-label">Costo Promedio</p>
                <p class="stat-value text-money">${{ number_format($costoPromedio, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Lotes activos --}}
    @if($detalle->lotes->where('cantidad_disponible', '>', 0)->count())
    <div class="card">
        <div class="card-header"><h3 class="card-title">Lotes con Stock Disponible</h3></div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Lote</th>
                        <th>Fecha Ingreso</th>
                        <th>Tipo</th>
                        <th class="text-center">Disponible</th>
                        <th class="text-right">Costo Unitario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detalle->lotes->where('cantidad_disponible', '>', 0)->sortBy('fecha_ingreso') as $lote)
                    <tr>
                        <td class="font-mono text-sm text-gray-500">#{{ $lote->id }}</td>
                        <td class="text-sm text-gray-600">{{ $lote->fecha_ingreso?->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge {{ $lote->tipo_origen === 'Propio' ? 'badge-success' : 'badge-warning' }}">
                                {{ $lote->tipo_origen }}
                            </span>
                        </td>
                        <td class="text-center font-bold text-money">{{ $lote->cantidad_disponible }}</td>
                        <td class="text-right text-money">${{ number_format($lote->costo_unitario, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Historial de movimientos --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Historial de Movimientos</h3>
            <form method="GET" class="flex gap-2">
                <input type="hidden" name="detalle" value="{{ $detalle->id }}">
                <select name="tipo" class="form-select w-auto text-sm py-1">
                    <option value="">Todos</option>
                    <option value="Entrada" {{ request('tipo') === 'Entrada' ? 'selected' : '' }}>Entradas</option>
                    <option value="Salida" {{ request('tipo') === 'Salida' ? 'selected' : '' }}>Salidas</option>
                    <option value="Venta" {{ request('tipo') === 'Venta' ? 'selected' : '' }}>Ventas</option>
                </select>
                <button type="submit" class="btn-secondary btn-sm">Filtrar</button>
            </form>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Origen</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">Costo Unit.</th>
                        <th>Usuario</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $mov)
                    <tr>
                        <td class="text-sm text-gray-600">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge {{ $mov->tipo_operacion === 'Entrada' ? 'badge-success' : ($mov->tipo_operacion === 'Venta' ? 'badge-info' : 'badge-danger') }}">
                                {{ $mov->tipo_operacion }}
                            </span>
                        </td>
                        <td class="text-sm text-gray-500">{{ $mov->tipo_origen ?? '—' }}</td>
                        <td class="text-center font-bold text-money {{ in_array($mov->tipo_operacion, ['Salida','Venta']) ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ in_array($mov->tipo_operacion, ['Salida','Venta']) ? '-' : '+' }}{{ $mov->cantidad }}
                        </td>
                        <td class="text-right text-money">${{ number_format($mov->costo_unitario ?? 0, 0, ',', '.') }}</td>
                        <td class="text-sm text-gray-600">{{ $mov->usuario->nombre }}</td>
                        <td class="text-sm text-gray-400">{{ $mov->observacion ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-6 text-gray-400">Sin movimientos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movimientos->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $movimientos->links() }}</div>
        @endif
    </div>

</div>
@endsection
