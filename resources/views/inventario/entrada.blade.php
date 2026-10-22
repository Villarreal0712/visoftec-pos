@extends('layouts.app')

@section('title', 'Registrar Entrada')
@section('page-title', 'Registrar Entrada de Mercancía')

@section('content')
<div x-data="entradaCarrito('{{ route('inventario.buscar') }}')" class="space-y-4 pb-24">

    <form id="form-entrada" method="POST" action="{{ route('inventario.entrada.store') }}">
        @csrf

        {{-- ══════ CABECERA COMPACTA ══════ --}}
        <div class="card">
            <div class="card-body py-3">
                <div class="flex flex-wrap items-center gap-4">

                    {{-- Número --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Entrada</span>
                        <span class="font-mono font-extrabold text-blue-600 text-lg">{{ $proximoNumero }}</span>
                    </div>

                    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>

                    {{-- Tipo origen --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Origen:</span>
                        <div class="flex gap-1">
                            <label class="cursor-pointer">
                                <input type="radio" name="tipo_origen" value="Propio" x-model="tipoOrigen" class="sr-only peer" checked>
                                <span class="inline-block px-3 py-1 text-xs font-medium rounded-full border-2 transition-colors cursor-pointer
                                    peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700
                                    border-gray-200 text-gray-500 hover:border-gray-300">Propio</span>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="tipo_origen" value="Prestado" x-model="tipoOrigen" class="sr-only peer">
                                <span class="inline-block px-3 py-1 text-xs font-medium rounded-full border-2 transition-colors cursor-pointer
                                    peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700
                                    border-gray-200 text-gray-500 hover:border-gray-300">Prestado</span>
                            </label>
                        </div>
                    </div>

                    {{-- Local externo --}}
                    <div x-show="tipoOrigen === 'Prestado'" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Local:</span>
                        <select name="id_local_externo" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 bg-white">
                            <option value="">— Seleccionar —</option>
                            @foreach($localesExternos as $local)
                            <option value="{{ $local->id }}">{{ $local->nombre_local }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>

                    {{-- Bodega destino --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Bodega:</span>
                        <select name="id_bodega" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 bg-white">
                            @foreach($bodegas as $bodega)
                            <option value="{{ $bodega->id }}" {{ old('id_bodega', $bodegaDefecto) == $bodega->id ? 'selected' : '' }}>
                                {{ $bodega->nombre }}{{ $bodega->id_sucursal === null ? ' (Acopio)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>

                    {{-- Observación --}}
                    <div class="flex items-center gap-2 flex-1 min-w-48">
                        <span class="text-xs text-gray-500 flex-shrink-0">Obs.:</span>
                        <input type="text" name="observacion" placeholder="Proveedor, # factura, notas..."
                            class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 min-w-0">
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════ BÚSQUEDA ══════ --}}
        <div class="card">
            <div class="card-body py-3">
                <div class="relative">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        type="text"
                        x-ref="inputBusqueda"
                        x-model="busqueda"
                        @input.debounce.250ms="buscar()"
                        @keydown.escape="resultados = []"
                        class="form-input pl-10 pr-24"
                        placeholder="Nombre del producto o código de barras (Enter para agregar automáticamente)..."
                        autocomplete="off"
                    />
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                        <template x-if="buscando">
                            <svg class="animate-spin w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </template>
                        <span x-show="carrito.length > 0"
                            class="text-xs bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full"
                            x-text="carrito.length + ' ítem' + (carrito.length !== 1 ? 's' : '')">
                        </span>
                    </div>
                </div>

                {{-- Dropdown resultados --}}
                <div x-show="resultados.length > 0"
                    class="mt-1 border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white z-20 relative">
                    <template x-for="p in resultados" :key="p.id">
                        <div @click="agregar(p); $refs.inputBusqueda.focus()"
                            class="flex items-center justify-between px-4 py-2.5 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors group">
                            <div class="min-w-0 flex-1">
                                <span class="text-sm font-medium text-gray-900" x-text="p.producto"></span>
                                <span x-show="p.talla" class="ml-2 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="p.talla"></span>
                            </div>
                            <div class="flex items-center gap-4 ml-3 flex-shrink-0">
                                <span class="text-xs font-mono text-gray-400" x-text="p.codigo_barras"></span>
                                <span class="text-xs text-gray-400">
                                    Stock: <span class="font-medium" :class="p.stock_actual > 0 ? 'text-gray-700' : 'text-amber-500'" x-text="p.stock_actual"></span>
                                </span>
                                <span class="text-xs bg-blue-500 text-white px-2 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">+ Agregar</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ══════ TABLA DE PRODUCTOS ══════ --}}
        <div class="card">

            {{-- Vacío --}}
            <template x-if="carrito.length === 0">
                <div class="text-center py-16 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="font-medium text-sm">Aún no hay productos</p>
                    <p class="text-xs text-gray-300 mt-1">Búscalos por nombre o escanea el código de barras</p>
                </div>
            </template>

            {{-- Tabla --}}
            <template x-if="carrito.length > 0">
                <div>
                    <div class="card-header border-b border-gray-100">
                        <h3 class="card-title">Productos a ingresar</h3>
                        <button type="button" @click="limpiar()" class="btn btn-secondary btn-sm">Limpiar todo</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="text-left px-4 py-2.5">#</th>
                                    <th class="text-left px-4 py-2.5">Producto</th>
                                    <th class="text-center px-3 py-2.5 w-24">Cantidad</th>
                                    <th class="text-center px-3 py-2.5 w-36">Costo unit. *</th>
                                    <th class="text-center px-3 py-2.5 w-36">Precio venta</th>
                                    <th class="w-10 px-2 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in carrito" :key="item.id">
                                    <tr class="border-b border-gray-50 hover:bg-gray-50/60 transition-colors">

                                        {{-- # --}}
                                        <td class="px-4 py-2.5 text-xs text-gray-400 font-mono" x-text="index + 1"></td>

                                        {{-- Producto --}}
                                        <td class="px-4 py-2.5">
                                            <p class="font-semibold text-gray-900 leading-tight" x-text="item.producto"></p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span x-show="item.talla"
                                                    class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium"
                                                    x-text="item.talla"></span>
                                                <span class="text-xs text-gray-400 font-mono" x-text="item.codigo_barras"></span>
                                            </div>
                                        </td>

                                        {{-- Cantidad --}}
                                        <td class="px-3 py-2.5">
                                            <input type="number" x-model.number="item.cantidad" min="1"
                                                class="w-full text-center px-1 py-1.5 text-sm font-bold border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-blue-400 bg-white">
                                        </td>

                                        {{-- Costo --}}
                                        <td class="px-3 py-2.5">
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-medium">$</span>
                                                <input type="text" inputmode="numeric"
                                                    :value="fmtMiles(item.costo_unitario)"
                                                    @input="applyMiles($event, v => item.costo_unitario = v)"
                                                    class="w-full pl-6 pr-1 py-1.5 text-sm border rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-blue-400 bg-white"
                                                    :class="!item.costo_unitario ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-gray-200'">
                                            </div>
                                        </td>

                                        {{-- Precio venta --}}
                                        <td class="px-3 py-2.5">
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-medium">$</span>
                                                <input type="text" inputmode="numeric"
                                                    :value="fmtMiles(item.precio_venta)"
                                                    @input="applyMiles($event, v => item.precio_venta = v)"
                                                    placeholder="—"
                                                    class="w-full pl-6 pr-1 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-blue-400 bg-white">
                                            </div>
                                        </td>

                                        {{-- Quitar --}}
                                        <td class="px-2 py-2.5 text-center">
                                            <button type="button" @click="quitar(index)"
                                                class="text-gray-300 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </td>

                                        {{-- Hidden --}}
                                        <input type="hidden" :name="'items[' + index + '][id_detalle_producto]'" :value="item.id">
                                        <input type="hidden" :name="'items[' + index + '][cantidad]'"            :value="item.cantidad">
                                        <input type="hidden" :name="'items[' + index + '][costo_unitario]'"      :value="item.costo_unitario">
                                        <input type="hidden" :name="'items[' + index + '][precio_venta]'"        :value="item.precio_venta || ''">
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>

    </form>

    {{-- ══════ BARRA FIJA INFERIOR ══════ --}}
<div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-lg">
    <div class="max-w-screen-xl mx-auto px-4 py-3 flex items-center justify-between gap-4">

        {{-- Totales --}}
        <div class="flex items-center gap-6 text-sm min-w-0">
            <div class="flex items-center gap-1.5">
                <span class="text-gray-400 text-xs">Entrada</span>
                <span class="font-mono font-bold text-blue-600">{{ $proximoNumero }}</span>
            </div>
            <div class="hidden sm:flex items-center gap-1 text-gray-500">
                <span class="font-semibold text-gray-900" x-text="carrito.length"></span>
                <span class="text-xs">ref. ·</span>
                <span class="font-semibold text-gray-900" x-text="totalUnidades"></span>
                <span class="text-xs">uds.</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="text-xs text-gray-400">Costo total:</span>
                <span class="font-extrabold text-gray-900">$<span x-text="costoTotal.toLocaleString('es-CO')"></span></span>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center gap-3 flex-shrink-0 flex-wrap justify-end">
            {{-- Checkbox imprimir etiquetas --}}
            <label class="flex items-center gap-2 cursor-pointer select-none border border-dashed border-blue-300 rounded-lg px-3 py-1.5 hover:bg-blue-50 transition-colors"
                   x-show="carrito.length > 0">
                <input type="checkbox" id="chk-etiquetas" x-model="imprimirEtiquetas"
                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                <span class="text-xs font-medium" :class="imprimirEtiquetas ? 'text-blue-700' : 'text-gray-500'">
                    Generar etiquetas al guardar
                </span>
            </label>
            <a href="{{ route('inventario.index') }}"
                class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                Cancelar
            </a>
            <button type="submit" form="form-entrada" @click.prevent="confirmar()"
                :disabled="carrito.length === 0"
                class="bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold px-6 py-2 rounded-xl text-sm transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Registrar Entrada
            </button>
        </div>
    </div>
</div>{{-- /barra fija --}}
</div>{{-- /x-data entradaCarrito --}}
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('entradaCarrito', (buscarUrl) => ({
        buscarUrl,
        busqueda: '',
        resultados: [],
        buscando: false,
        carrito: [],
        tipoOrigen: 'Propio',
        imprimirEtiquetas: false,

        get totalUnidades() {
            return this.carrito.reduce((s, i) => s + (parseInt(i.cantidad) || 0), 0);
        },

        get costoTotal() {
            return this.carrito.reduce((s, i) => s + ((parseInt(i.cantidad) || 0) * (parseFloat(i.costo_unitario) || 0)), 0);
        },

        async buscar() {
            if (this.busqueda.length < 2) { this.resultados = []; return; }
            this.buscando = true;
            try {
                const res = await fetch(`${this.buscarUrl}?q=${encodeURIComponent(this.busqueda)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();

                // Auto-agregar si el término coincide exactamente con un código de barras
                const exacto = data.find(p => p.codigo_barras === this.busqueda.trim());
                if (exacto) {
                    this.agregar(exacto);
                    return;
                }

                this.resultados = data;
            } catch (e) {
                showToast('Error al buscar productos', 'error');
            } finally {
                this.buscando = false;
            }
        },

        agregar(producto) {
            const existente = this.carrito.find(i => i.id === producto.id);
            if (existente) {
                existente.cantidad++;
                showToast(`${producto.producto} — cantidad: ${existente.cantidad}`, 'info', 1500);
            } else {
                this.carrito.push({
                    id:             producto.id,
                    producto:       producto.producto,
                    talla:          producto.talla,
                    codigo_barras:  producto.codigo_barras,
                    cantidad:       1,
                    costo_unitario: 0,
                    precio_venta:   0,
                });
                showToast(`${producto.producto} agregado`, 'success', 1500);
            }
        },

        quitar(index) {
            this.carrito.splice(index, 1);
        },

        limpiar() {
            if (confirm('¿Limpiar todos los productos?')) {
                this.carrito = [];
            }
        },

        confirmar() {
            if (this.carrito.length === 0) {
                showToast('Agrega al menos un producto', 'warning');
                return;
            }
            const sinCosto = this.carrito.some(i => !i.costo_unitario || i.costo_unitario <= 0);
            if (sinCosto) {
                showToast('Todos los productos deben tener un costo unitario', 'warning');
                return;
            }
            // Inyectar campo imprimir_etiquetas antes de enviar
            const form = document.getElementById('form-entrada');
            let hiddenEtiq = form.querySelector('[name="imprimir_etiquetas"]');
            if (!hiddenEtiq) {
                hiddenEtiq = document.createElement('input');
                hiddenEtiq.type = 'hidden';
                hiddenEtiq.name = 'imprimir_etiquetas';
                form.appendChild(hiddenEtiq);
            }
            hiddenEtiq.value = this.imprimirEtiquetas ? '1' : '0';
            form.submit();
        },
    }));
});
</script>
@endpush
