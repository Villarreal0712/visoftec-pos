@extends('layouts.app')

@section('title', 'Movimientos de Inventario')
@section('page-title', 'Reporte de Movimientos de Inventario')

@section('content')
@php
    $colores = [
        'Entrada'       => ['badge' => 'bg-emerald-100 text-emerald-700', 'icono' => '↑'],
        'Venta'         => ['badge' => 'bg-blue-100 text-blue-700',       'icono' => '🛒'],
        'Salida'        => ['badge' => 'bg-amber-100 text-amber-700',     'icono' => '↓'],
        'Ajuste'        => ['badge' => 'bg-gray-100 text-gray-600',       'icono' => '↺'],
        'Transferencia' => ['badge' => 'bg-purple-100 text-purple-700',   'icono' => '⇄'],
        'Merma'         => ['badge' => 'bg-red-100 text-red-700',         'icono' => '✕'],
    ];
@endphp
<div class="space-y-5">

    {{-- Filtros --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.movimientos') }}" class="flex flex-wrap gap-3 items-end">
                <div class="form-group mb-0">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}" class="form-input w-auto">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_fin" value="{{ $fechaFin }}" class="form-input w-auto">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select w-auto">
                        <option value="">Todos</option>
                        @foreach(array_keys($colores) as $t)
                        <option value="{{ $t }}" {{ request('tipo') === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Bodega</label>
                    <select name="bodega" class="form-select w-auto">
                        <option value="">Todas</option>
                        @foreach($bodegas as $b)
                        <option value="{{ $b->id }}" {{ (string) request('bodega') === (string) $b->id ? 'selected' : '' }}>
                            {{ $b->nombre }}{{ $b->activo ? '' : ' (inactiva)' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary">Aplicar</button>
                <a href="{{ route('reportes.movimientos') }}" class="btn-secondary">Hoy</a>
            </form>
        </div>
    </div>

    {{-- Tabla de movimientos --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Movimientos</h3>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-400">{{ $movimientos->total() }} registros</span>
                <x-per-page-selector :current="request('per_page', 50)" />
                <x-export-btn />
            </div>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th class="text-center">Talla</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Cantidad</th>
                        <th>Desde</th>
                        <th>Hacia</th>
                        <th>Observación</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $mov)
                    @php
                        $tipo   = $mov->tipo_operacion;
                        $config = $colores[$tipo] ?? ['badge' => 'bg-gray-100 text-gray-600', 'icono' => '·'];
                        $direccion = $mov->direccion();
                        $desde = $mov->desde();
                        $hacia = $mov->hacia();
                    @endphp
                    <tr>
                        <td class="text-sm text-gray-600 whitespace-nowrap">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <p class="font-medium text-gray-900">{{ $mov->detalleProducto?->producto?->nombre ?? '—' }}</p>
                        </td>
                        <td class="text-center text-gray-600">{{ $mov->detalleProducto?->talla ?? '—' }}</td>
                        <td class="text-center">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $config['badge'] }}">
                                <span>{{ $config['icono'] }}</span>
                                {{ $tipo }}
                            </span>
                        </td>
                        <td class="text-center font-bold tabular-nums">
                            @if($direccion === 'entra')
                                <span class="text-emerald-600">+{{ abs($mov->cantidad) }}</span>
                            @elseif($direccion === 'sale')
                                <span class="text-red-600">−{{ abs($mov->cantidad) }}</span>
                            @else
                                <span class="text-gray-400">{{ abs($mov->cantidad) }}</span>
                            @endif
                        </td>
                        <td class="text-sm whitespace-nowrap {{ $direccion === 'sale' ? 'font-medium text-gray-800' : 'text-gray-500' }}">{{ $desde }}</td>
                        <td class="text-sm whitespace-nowrap {{ $direccion === 'entra' ? 'font-medium text-gray-800' : 'text-gray-500' }}">
                            <span class="text-gray-300">→</span> {{ $hacia }}
                        </td>
                        <td class="text-sm text-gray-500 max-w-xs truncate" title="{{ $mov->observacion }}">{{ $mov->observacion ?? '—' }}</td>
                        <td class="text-sm text-gray-600">{{ $mov->usuario?->nombre ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-400">No hay movimientos en el período seleccionado</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $movimientos->links() }}
        </div>
    </div>

</div>
@endsection
