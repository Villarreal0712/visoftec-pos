@extends('layouts.app')

@section('title', 'Productos')
@section('page-title', 'Gestión de Productos')

@section('content')
<div class="space-y-5">

    {{-- Header con acciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-sm text-gray-500">Gestión del catálogo de productos</p>
        </div>
        @can('productos.crear')
        <a href="{{ route('productos.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nuevo Producto
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('productos.index') }}" class="flex flex-wrap gap-3"
                  x-data="{ buscar: @js((string) request('buscar', '')) }">
                <div class="flex-1 min-w-48 relative">
                    <input type="text" name="buscar" x-model="buscar"
                        @keydown.enter.prevent="$el.form.submit()"
                        @input.debounce.400ms="$el.form.submit()"
                        class="form-input pr-8" placeholder="Buscar producto..." autocomplete="off">
                    <button x-show="buscar.length > 0" type="button"
                        @click="buscar = ''; $nextTick(() => $el.form.submit())"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="min-w-40">
                    <select name="categoria" class="form-select" @change="$el.form.submit()">
                        <option value="">Todas las categorías</option>
                        @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" {{ request('categoria') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nombre }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="stock_bajo" value="1" {{ request('stock_bajo') ? 'checked' : '' }}
                        class="rounded border-gray-300 text-amber-500" @change="$el.form.submit()">
                    Solo stock bajo
                </label>
                @if(request()->hasAny(['buscar', 'categoria', 'stock_bajo']))
                <a href="{{ route('productos.index') }}" class="btn-secondary">Limpiar</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Tabla de productos --}}
    <div class="card">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
            <x-per-page-selector :current="request('per_page', 25)" />
            @can('productos.ver')<x-export-btn />@endcan
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="text-right">Precio Venta</th>
                        <th class="text-center">Detalles</th>
                        <th class="text-center">Stock Total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                    @php $stockTotal = $producto->stock_total; @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if($producto->imagen)
                                <img src="{{ asset('storage/' . $producto->imagen) }}" class="w-10 h-10 rounded-lg object-cover">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-900">{{ $producto->nombre }}</p>
                                    @if($producto->descripcion)
                                    <p class="text-xs text-gray-400 truncate max-w-40">{{ $producto->descripcion }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($producto->categoria)
                            <span class="badge badge-gray">{{ $producto->categoria->nombre }}</span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="text-right font-semibold text-money">${{ number_format($producto->precio_venta, 0, ',', '.') }}</td>
                        <td class="text-center text-sm text-gray-600">{{ $producto->detalles->count() }} SKUs</td>
                        <td class="text-center">
                            <span class="font-bold {{ $stockTotal <= $producto->stock_minimo ? 'text-amber-600' : 'text-gray-900' }} text-money">
                                {{ $stockTotal }}
                            </span>
                            @if($stockTotal <= $producto->stock_minimo)
                            <span class="badge badge-warning ml-1">Bajo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($producto->activo)
                            <span class="badge badge-success">Activo</span>
                            @else
                            <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('productos.show', $producto) }}" class="btn btn-secondary btn-sm">Ver</a>
                                @can('productos.editar')
                                <a href="{{ route('productos.edit', $producto) }}" class="btn btn-secondary btn-sm">Editar</a>
                                @endcan
                                @can('productos.eliminar')
                                <form id="del-prod-{{ $producto->id }}" method="POST" action="{{ route('productos.destroy', $producto) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="confirmarEliminar('del-prod-{{ $producto->id }}')" class="btn btn-danger btn-sm">
                                        Eliminar
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-400">
                            @if(request()->hasAny(['buscar', 'categoria', 'stock_bajo']))
                                No se encontraron productos con esos filtros
                            @else
                                No hay productos registrados aún
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-gray-100">
            {{ $productos->links() }}
        </div>
    </div>

</div>
@endsection
