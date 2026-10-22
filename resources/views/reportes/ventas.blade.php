@extends('layouts.app')

@section('title', 'Reporte de Ventas')
@section('page-title', 'Reporte de Ventas')

@section('content')
<div class="space-y-5">

    {{-- Filtros --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.ventas') }}" class="flex flex-wrap gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 whitespace-nowrap">Desde:</label>
                    <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}" class="form-input w-auto">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600 whitespace-nowrap">Hasta:</label>
                    <input type="date" name="fecha_fin" value="{{ $fechaFin }}" class="form-input w-auto">
                </div>
                <button type="submit" class="btn-primary">Generar</button>
                @can('reportes.exportar')
                <a href="{{ route('reportes.ventas.pdf') }}?fecha_inicio={{ $fechaInicio }}&fecha_fin={{ $fechaFin }}" class="btn-danger">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    PDF
                </a>
                @endcan
            </form>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid sm:grid-cols-3 gap-4">
        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <p class="stat-label">Total Ventas</p>
                <p class="stat-value text-money">{{ $ventas->count() }}</p>
                <p class="text-xs text-gray-400">transacciones</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-emerald-100">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="stat-label">Total Ingresos</p>
                <p class="stat-value text-money text-emerald-600">${{ number_format($totalVentas, 0, ',', '.') }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-purple-100">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <p class="stat-label">Ganancia Estimada</p>
                <p class="stat-value text-money text-purple-600">${{ number_format($totalGanancia, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Por método de pago --}}
    @if($porMetodoPago->count())
    <div class="card">
        <div class="card-header"><h3 class="card-title">Por Método de Pago</h3></div>
        <div class="card-body">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($porMetodoPago as $metodo => $datos)
                <div class="p-4 bg-gray-50 rounded-xl">
                    <p class="text-sm font-medium text-gray-600">{{ $metodo }}</p>
                    <p class="text-lg font-bold text-money mt-1">${{ number_format($datos['total'], 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">{{ $datos['cantidad'] }} ventas</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Tabla de ventas --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detalle de Ventas</h3>
            <span class="text-sm text-gray-500">{{ $fechaInicio }} al {{ $fechaFin }}</span>
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
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $venta)
                    <tr>
                        <td><a href="{{ route('ventas.show', $venta) }}" class="text-blue-600 font-medium hover:underline">#{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}</a></td>
                        <td class="text-sm text-gray-600">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $venta->cliente?->nombre ?? 'Venta directa' }}</td>
                        <td>{{ $venta->usuario->nombre }}</td>
                        <td><span class="badge badge-info">{{ $venta->metodo_pago }}</span></td>
                        <td class="text-right font-bold text-money">${{ number_format($venta->total, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if(!$venta->es_credito || $venta->estado_credito === 'Pagado')
                                <span class="badge badge-success">Pagado</span>
                            @elseif($venta->estado_credito === 'Mora')
                                <span class="badge badge-danger">Mora</span>
                            @else
                                <span class="badge badge-warning">Parcial</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-6 text-gray-400">No hay ventas en este período</td></tr>
                    @endforelse
                </tbody>
                @if($ventas->count())
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-sm">TOTAL:</td>
                        <td class="px-4 py-3 text-right text-money">${{ number_format($totalVentas, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
