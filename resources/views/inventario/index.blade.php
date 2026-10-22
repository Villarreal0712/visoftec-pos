@extends('layouts.app')

@section('title', 'Inventario')
@section('page-title', 'Inventario Actual')

@section('content')
<div class="space-y-5">

    {{-- Banner: etiquetas pendientes de imprimir --}}
    @if(session('etiquetas_url'))
    <div x-data="{ visible: true }" x-show="visible"
         class="flex items-center gap-4 bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-3">
        <svg class="w-6 h-6 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-emerald-800">Entrada registrada correctamente</p>
            <p class="text-xs text-emerald-600">Las etiquetas están listas para imprimir.</p>
        </div>
        <a href="{{ session('etiquetas_url') }}" target="_blank" rel="noopener"
           class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimir etiquetas
        </a>
        <button @click="visible = false" class="text-emerald-400 hover:text-emerald-600 transition-colors flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    @endif

    <div class="flex flex-wrap gap-3">
        @can('inventario.entradas')
        <a href="{{ route('inventario.entrada') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Registrar Entrada
        </a>
        @endcan
        @can('inventario.salidas')
        <a href="{{ route('inventario.salida') }}" class="btn-warning">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Registrar Salida
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card">
        <div class="card-body">
            <form id="form-filtros" method="GET" action="{{ route('inventario.index') }}" class="flex flex-wrap gap-3"
                  x-data="{ buscar: @js((string) request('buscar', '')) }">

                <div class="flex-1 min-w-48 relative">
                    <input
                        type="text"
                        name="buscar"
                        x-model="buscar"
                        @keydown.enter.prevent="$el.form.submit()"
                        @input.debounce.400ms="$el.form.submit()"
                        class="form-input pr-8"
                        placeholder="Buscar por producto o código de barras..."
                        autocomplete="off"
                        x-ref="inputBuscar"
                    >
                    <button x-show="buscar.length > 0" type="button"
                        @click="buscar = ''; $nextTick(() => $el.form.submit())"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                @if($bodegas->count() > 1)
                <select name="bodega" class="form-select w-auto" @change="$el.form.submit()">
                    <option value="">Todas las bodegas</option>
                    @foreach($bodegas as $bod)
                    <option value="{{ $bod->id }}" {{ $bodegaSeleccionada == $bod->id ? 'selected' : '' }}>
                        {{ $bod->nombre }}
                    </option>
                    @endforeach
                </select>
                @endif

                <select name="categoria" class="form-select w-auto" @change="$el.form.submit()">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" {{ request('categoria') == $cat->id ? 'selected' : '' }}>{{ $cat->nombre }}</option>
                    @endforeach
                </select>

                <select name="stock" class="form-select w-auto" @change="$el.form.submit()">
                    <option value="">Todo el inventario</option>
                    <option value="con_stock" {{ request('stock') === 'con_stock' ? 'selected' : '' }}>Con existencias</option>
                    <option value="bajo"      {{ request('stock') === 'bajo'      ? 'selected' : '' }}>Stock bajo / agotado</option>
                    <option value="agotado"   {{ request('stock') === 'agotado'   ? 'selected' : '' }}>Solo agotados</option>
                </select>

                <button type="submit" class="btn-primary">Buscar</button>
                @if(request()->hasAny(['buscar','categoria','stock','bodega']))
                <a href="{{ route('inventario.index') }}" class="btn-secondary">Limpiar</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
            <x-per-page-selector :current="request('per_page', 25)" />
            @can('inventario.ver')<x-export-btn />@endcan
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Talla/Var.</th>
                        <th>Cód. Barras</th>
                        <th>Bodega(s)</th>
                        <th class="text-center">Lotes</th>
                        <th class="text-center">
                            Stock
                            @if($bodegaSeleccionada)
                                <span class="text-xs font-normal text-blue-500">({{ $bodegas->firstWhere('id', $bodegaSeleccionada)?->nombre }})</span>
                            @else
                                <span class="text-xs font-normal text-gray-400">(total)</span>
                            @endif
                        </th>
                        <th class="text-right">Costo Prom.</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detalles as $detalle)
                    @php
                        $stock = $detalle->lotes->sum('cantidad_disponible');
                        $costo = $detalle->costo_promedio;
                        $stockMin = $detalle->producto->stock_minimo;
                        $bajo = $stock <= $stockMin;
                        $agotado = $stock === 0;
                    @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-gray-900">{{ $detalle->producto->nombre }}</p>
                        </td>
                        <td>
                            <span class="badge badge-gray">{{ $detalle->producto->categoria?->nombre ?? '—' }}</span>
                        </td>
                        <td class="text-gray-600">{{ $detalle->talla ?? 'Única' }}</td>
                        <td class="text-gray-500 text-xs font-mono">{{ $detalle->codigo_barras ?? '—' }}</td>
                        <td>
                            @php $porBodega = $detalle->lotes->groupBy('id_bodega') @endphp
                            @forelse($porBodega as $lotesGrupo)
                                <div class="flex items-center gap-1.5 text-xs leading-5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400 flex-shrink-0"></span>
                                    <span class="text-gray-700 truncate max-w-32">{{ $lotesGrupo->first()->bodega?->nombre ?? '—' }}</span>
                                    <span class="text-gray-400 flex-shrink-0">({{ $lotesGrupo->sum('cantidad_disponible') }})</span>
                                </div>
                            @empty
                                <span class="text-gray-300 text-xs">—</span>
                            @endforelse
                        </td>
                        <td class="text-center text-gray-600">{{ $detalle->lotes->count() }}</td>
                        <td class="text-center">
                            @if($agotado)
                                <span class="badge badge-danger">Agotado</span>
                            @elseif($bajo)
                                <span class="font-bold text-amber-600 text-money">{{ $stock }}</span>
                                <span class="badge badge-warning ml-1">Bajo</span>
                            @else
                                <span class="font-bold text-gray-900 text-money">{{ $stock }}</span>
                            @endif
                        </td>
                        <td class="text-right text-gray-600 text-money">${{ number_format($costo, 0, ',', '.') }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('inventario.historial', $detalle) }}" class="btn btn-secondary btn-sm">Historial</a>
                                @can('inventario.entradas')
                                <a href="{{ route('inventario.entrada') }}?detalle={{ $detalle->id }}" class="btn btn-success btn-sm">+ Entrada</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-8 text-gray-400">No se encontraron registros de inventario</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">{{ $detalles->links() }}</div>
    </div>

</div>
@endsection
