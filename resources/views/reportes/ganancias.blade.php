@extends('layouts.app')

@section('title', 'Reporte de Ganancias')
@section('page-title', 'Reporte de Ganancias por Producto')

@section('content')
<div class="space-y-5">

    {{-- Filtros --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.ganancias') }}" class="flex flex-wrap gap-3 items-end">
                <div class="form-group mb-0">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}" class="form-input w-auto">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_fin" value="{{ $fechaFin }}" class="form-input w-auto">
                </div>
                <button type="submit" class="btn-primary">Aplicar</button>
                <a href="{{ route('reportes.ganancias') }}" class="btn-secondary">Mes actual</a>
            </form>
        </div>
    </div>

    {{-- Resumen --}}
    <div class="grid sm:grid-cols-3 gap-5">
        <div class="card">
            <div class="card-body text-center py-5">
                <p class="text-2xl font-extrabold text-blue-700 text-money">${{ number_format($totalIngresos, 0, ',', '.') }}</p>
                <p class="text-sm text-gray-500 mt-1">Total Ingresos</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body text-center py-5">
                <p class="text-2xl font-extrabold text-red-600 text-money">${{ number_format($totalCostos, 0, ',', '.') }}</p>
                <p class="text-sm text-gray-500 mt-1">Total Costos</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body text-center py-5">
                @php $margen = $totalIngresos > 0 ? ($totalGanancia / $totalIngresos) * 100 : 0; @endphp
                <p class="text-2xl font-extrabold text-emerald-700 text-money">${{ number_format($totalGanancia, 0, ',', '.') }}</p>
                <p class="text-sm text-gray-500 mt-1">Ganancia Neta <span class="text-emerald-600 font-bold">({{ number_format($margen, 1) }}%)</span></p>
            </div>
        </div>
    </div>

    {{-- Tabla de productos --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ganancia por Producto</h3>
            <span class="text-sm text-gray-400">{{ $detalles->count() }} productos vendidos</span>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Talla</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">P. Promedio</th>
                        <th class="text-right">Costo Prom.</th>
                        <th class="text-right">Ingresos</th>
                        <th class="text-right">Costos</th>
                        <th class="text-right">Ganancia</th>
                        <th class="text-right">Margen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detalles as $d)
                    @php
                        $margenProd = $d->total_ingresos > 0 ? ($d->ganancia_total / $d->total_ingresos) * 100 : 0;
                    @endphp
                    <tr>
                        <td class="font-medium text-gray-900">{{ $d->producto }}</td>
                        <td class="text-center text-gray-600">{{ $d->talla ?? '—' }}</td>
                        <td class="text-center text-gray-600">{{ $d->cantidad_vendida }}</td>
                        <td class="text-right text-money">${{ number_format($d->precio_promedio, 0, ',', '.') }}</td>
                        <td class="text-right text-money text-gray-500">${{ number_format($d->costo_promedio, 0, ',', '.') }}</td>
                        <td class="text-right text-money text-blue-700">${{ number_format($d->total_ingresos, 0, ',', '.') }}</td>
                        <td class="text-right text-money text-red-500">${{ number_format($d->total_costos, 0, ',', '.') }}</td>
                        <td class="text-right font-bold text-money text-emerald-700">${{ number_format($d->ganancia_total, 0, ',', '.') }}</td>
                        <td class="text-right">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $margenProd >= 30 ? 'bg-emerald-100 text-emerald-700' : ($margenProd >= 15 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                {{ number_format($margenProd, 1) }}%
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-400">No hay ventas en el período seleccionado</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($detalles->count())
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="5" class="text-right text-sm text-gray-600">TOTALES</td>
                        <td class="text-right text-money text-blue-700">${{ number_format($totalIngresos, 0, ',', '.') }}</td>
                        <td class="text-right text-money text-red-500">${{ number_format($totalCostos, 0, ',', '.') }}</td>
                        <td class="text-right text-money text-emerald-700">${{ number_format($totalGanancia, 0, ',', '.') }}</td>
                        <td class="text-right">
                            <span class="text-xs font-bold text-emerald-700">{{ number_format($margen, 1) }}%</span>
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
