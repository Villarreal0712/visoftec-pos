@extends('layouts.app')

@section('title', 'Gastos')
@section('page-title', 'Gestión de Gastos')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-500">Control de gastos externos y de caja chica</p>
        @can('gastos.crear')
        <a href="{{ route('gastos.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Registrar Gasto
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('gastos.index') }}" class="flex flex-wrap gap-3">
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="form-input w-auto">
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="form-input w-auto">
                <select name="categoria" class="form-select w-auto">
                    <option value="">Todas las categorías</option>
                    @foreach(\App\Models\GastoExterno::CATEGORIAS as $cat)
                    <option value="{{ $cat }}" {{ request('categoria') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
                <select name="tipo" class="form-select w-auto">
                    <option value="">Todos los tipos</option>
                    <option value="Externo" {{ request('tipo') === 'Externo' ? 'selected' : '' }}>Externo</option>
                    <option value="Local" {{ request('tipo') === 'Local' ? 'selected' : '' }}>Local (Caja chica)</option>
                </select>
                <button type="submit" class="btn-primary">Filtrar</button>
                @if(request()->hasAny(['fecha_inicio','fecha_fin','categoria','tipo']))
                <a href="{{ route('gastos.index') }}" class="btn-secondary">Limpiar</a>
                @endif
            </form>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-5">
        {{-- Resumen por categoría --}}
        <div class="card lg:col-span-1">
            <div class="card-header"><h3 class="card-title">Por Categoría</h3></div>
            <div class="card-body p-0">
                @forelse($porCategoria as $item)
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0">
                    <span class="text-sm text-gray-700">{{ $item->categoria }}</span>
                    <span class="font-bold text-sm text-money">${{ number_format($item->total, 0, ',', '.') }}</span>
                </div>
                @empty
                <div class="text-center py-6 text-gray-400 text-sm">Sin datos</div>
                @endforelse
                @if($porCategoria->count())
                <div class="flex items-center justify-between px-5 py-3 bg-gray-50 font-bold">
                    <span class="text-sm">TOTAL</span>
                    <span class="text-money text-red-600">${{ number_format($totalFiltrado, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Tabla de gastos --}}
        <div class="card lg:col-span-2">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                <x-per-page-selector :current="request('per_page', 25)" />
                @can('gastos.ver')<x-export-btn />@endcan
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Tipo</th>
                            <th class="text-right">Valor</th>
                            <th>Fecha</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gastos as $gasto)
                        <tr>
                            <td>
                                <p class="font-medium text-gray-900">{{ $gasto->descripcion }}</p>
                                @if($gasto->observacion)
                                <p class="text-xs text-gray-400">{{ Str::limit($gasto->observacion, 50) }}</p>
                                @endif
                            </td>
                            <td><span class="badge badge-gray">{{ $gasto->categoria }}</span></td>
                            <td><span class="badge {{ $gasto->tipo === 'Externo' ? 'badge-info' : 'badge-warning' }}">{{ $gasto->tipo }}</span></td>
                            <td class="text-right font-bold text-red-600 text-money">${{ number_format($gasto->valor, 0, ',', '.') }}</td>
                            <td class="text-sm text-gray-500">{{ $gasto->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    @if($gasto->comprobante)
                                    <a href="{{ asset('storage/' . $gasto->comprobante) }}" target="_blank" class="btn btn-secondary btn-sm">Comp.</a>
                                    @endif
                                    @can('gastos.editar')
                                    <a href="{{ route('gastos.edit', $gasto) }}" class="btn btn-secondary btn-sm">Editar</a>
                                    @endcan
                                    @can('gastos.eliminar')
                                    <form id="del-gasto-{{ $gasto->id }}" method="POST" action="{{ route('gastos.destroy', $gasto) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="button" onclick="confirmarEliminar('del-gasto-{{ $gasto->id }}')" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-8 text-gray-400">No hay gastos registrados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-gray-100">{{ $gastos->links() }}</div>
        </div>
    </div>

</div>
@endsection
