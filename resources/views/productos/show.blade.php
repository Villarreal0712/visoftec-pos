@extends('layouts.app')

@section('title', $producto->nombre)
@section('page-title', $producto->nombre)

@section('content')
@php
    $stockTotal = $producto->detalles->sum(fn($d) => $d->lotes->sum('cantidad_disponible'));
@endphp

<div class="space-y-5">

    {{-- Acciones --}}
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('productos.index') }}" class="btn btn-secondary btn-sm">← Volver</a>
        @can('productos.editar')
        <a href="{{ route('productos.edit', $producto) }}" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Editar
        </a>
        @endcan
        @can('inventario.entradas')
        <a href="{{ route('inventario.entrada') }}" class="btn btn-success btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Registrar Entrada
        </a>
        @endcan
    </div>

    {{-- Info del producto + stats --}}
    <div class="grid lg:grid-cols-3 gap-5">

        {{-- Tarjeta del producto --}}
        <div class="card lg:col-span-1">
            <div class="card-body flex flex-col items-center text-center gap-4">
                @if($producto->imagen)
                    <img src="{{ asset('storage/' . $producto->imagen) }}"
                         class="w-28 h-28 rounded-2xl object-cover shadow-sm">
                @else
                    <div class="w-28 h-28 rounded-2xl bg-gray-100 flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif

                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $producto->nombre }}</h2>
                    @if($producto->categoria)
                    <span class="badge badge-gray mt-1">{{ $producto->categoria->nombre }}</span>
                    @endif
                    @if($producto->descripcion)
                    <p class="text-sm text-gray-500 mt-2">{{ $producto->descripcion }}</p>
                    @endif
                </div>

                <div class="w-full border-t border-gray-100 pt-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Precio base</span>
                        <span class="font-bold text-money text-gray-900">${{ number_format($producto->precio_venta, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Stock mínimo</span>
                        <span class="font-semibold text-gray-700">{{ $producto->stock_minimo }} uds.</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Estado</span>
                        @if($producto->activo)
                        <span class="badge badge-success">Activo</span>
                        @else
                        <span class="badge badge-danger">Inactivo</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">SKUs (tallas)</span>
                        <span class="font-semibold text-gray-700">{{ $producto->detalles->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="lg:col-span-2 grid sm:grid-cols-2 gap-4 content-start">
            <div class="stat-card">
                <div class="stat-icon bg-blue-100">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <p class="stat-label">Stock Total</p>
                    <p class="stat-value text-money {{ $stockTotal <= $producto->stock_minimo ? 'text-amber-600' : 'text-blue-700' }}">
                        {{ $stockTotal }}
                    </p>
                    @if($stockTotal <= $producto->stock_minimo)
                    <span class="badge badge-warning mt-1">Bajo stock</span>
                    @else
                    <p class="text-xs text-gray-400">unidades disponibles</p>
                    @endif
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-emerald-100">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <p class="stat-label">Total Movimientos</p>
                    <p class="stat-value text-money text-emerald-700">
                        {{ $producto->detalles->sum(fn($d) => $d->historialMovimientos->count()) }}
                    </p>
                    <p class="text-xs text-gray-400">en todos los SKUs</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-purple-100">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                </div>
                <div>
                    <p class="stat-label">Lotes Activos</p>
                    <p class="stat-value text-money text-purple-700">
                        {{ $producto->detalles->sum(fn($d) => $d->lotes->where('cantidad_disponible', '>', 0)->count()) }}
                    </p>
                    <p class="text-xs text-gray-400">con stock disponible</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon bg-amber-100">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    @php
                        $lotesConCosto = $producto->detalles->flatMap(fn($d) => $d->lotes)->where('costo_unitario', '>', 0);
                        $costoPromedio = $lotesConCosto->count() ? $lotesConCosto->avg('costo_unitario') : 0;
                    @endphp
                    <p class="stat-label">Costo Promedio</p>
                    <p class="stat-value text-money text-amber-700">${{ number_format($costoPromedio, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">por unidad</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de SKUs / Detalles --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">SKUs y Stock por Talla</h3>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Talla</th>
                        <th>Código de Barras</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Lotes</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($producto->detalles as $detalle)
                    @php
                        $stockDetalle = $detalle->lotes->sum('cantidad_disponible');
                        $lotesActivos = $detalle->lotes->where('cantidad_disponible', '>', 0)->count();
                    @endphp
                    <tr>
                        <td class="font-semibold text-gray-900">
                            {{ $detalle->talla ?? 'Única' }}
                        </td>
                        <td class="font-mono text-sm text-gray-500">
                            {{ $detalle->codigo_barras ?? '—' }}
                        </td>
                        <td class="text-center">
                            <span class="font-bold text-money {{ $stockDetalle === 0 ? 'text-red-500' : 'text-gray-900' }}">
                                {{ $stockDetalle }}
                            </span>
                        </td>
                        <td class="text-center text-sm text-gray-500">{{ $lotesActivos }} activo(s)</td>
                        <td class="text-center">
                            @if($stockDetalle === 0)
                                <span class="badge badge-danger">Sin stock</span>
                            @elseif($stockDetalle <= $producto->stock_minimo)
                                <span class="badge badge-warning">Bajo</span>
                            @else
                                <span class="badge badge-success">OK</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @can('inventario.ver')
                            <a href="{{ route('inventario.historial', $detalle) }}" class="btn btn-secondary btn-sm">
                                Ver historial
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Últimos movimientos --}}
    @php
        $ultimosMovimientos = $producto->detalles
            ->flatMap(fn($d) => $d->historialMovimientos->each(fn($m) => $m->talla = $d->talla))
            ->sortByDesc('created_at')
            ->take(15);
    @endphp
    @if($ultimosMovimientos->count())
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Últimos Movimientos</h3>
            <span class="text-xs text-gray-400">Todos los SKUs · últimos 15</span>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Talla</th>
                        <th>Tipo</th>
                        <th class="text-center">Cantidad</th>
                        <th>Usuario</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ultimosMovimientos as $mov)
                    <tr>
                        <td class="text-sm text-gray-500">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-sm font-medium text-gray-700">{{ $mov->talla ?? 'Única' }}</td>
                        <td>
                            <span class="badge {{ $mov->tipo_operacion === 'Entrada' ? 'badge-success' : ($mov->tipo_operacion === 'Venta' ? 'badge-info' : 'badge-danger') }}">
                                {{ $mov->tipo_operacion }}
                            </span>
                        </td>
                        <td class="text-center font-bold text-money {{ in_array($mov->tipo_operacion, ['Salida','Venta']) ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ in_array($mov->tipo_operacion, ['Salida','Venta']) ? '−' : '+' }}{{ $mov->cantidad }}
                        </td>
                        <td class="text-sm text-gray-600">{{ $mov->usuario->nombre ?? '—' }}</td>
                        <td class="text-sm text-gray-400">{{ $mov->observacion ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
