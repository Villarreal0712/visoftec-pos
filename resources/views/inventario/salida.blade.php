@extends('layouts.app')

@section('title', 'Registrar Salida')
@section('page-title', 'Registrar Salida de Inventario')

@section('content')
<div x-data="salidaCarrito(
        '{{ route('inventario.salida.buscar') }}',
        '{{ $bodegaDefecto }}',
        @js($carritoInicial),
        @js(old('tipo_operacion', 'Merma'))
    )" class="space-y-4 pb-24">

    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-700">
        <strong>Nota:</strong> Las ventas descuentan el inventario automáticamente. Usa esta pantalla solo para mermas, daños, ajustes, devoluciones o transferencias a locales externos.
    </div>

    @if($errors->any())
    <div class="alert-error">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    </div>
    @endif

    <form id="form-salida" method="POST" action="{{ route('inventario.salida.store') }}">
        @csrf

        {{-- ══════ CABECERA ══════ --}}
        <div class="card">
            <div class="card-body py-3 space-y-3">
                <div class="flex flex-wrap items-center gap-4">

                    {{-- Bodega --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Bodega:</span>
                        <select name="id_bodega" x-model="idBodega" @change="cambiarBodega()"
                            class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-amber-300 bg-white font-medium">
                            @foreach($bodegas as $bodega)
                            <option value="{{ $bodega->id }}">
                                {{ $bodega->nombre }}{{ $bodega->id_sucursal === null ? ' (Acopio)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>

                    {{-- Tipo de salida --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-gray-500">Tipo:</span>
                        <input type="hidden" name="tipo_operacion" :value="tipo">
                        <template x-for="t in tipos" :key="t.valor">
                            <button type="button" @click="tipo = t.valor"
                                class="px-3 py-1 text-xs font-medium rounded-full border-2 transition-colors"
                                :class="tipo === t.valor ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                                x-text="t.texto"></button>
                        </template>
                    </div>

                    {{-- Local externo (solo transferencia) --}}
                    <div x-show="tipo === 'Transferencia'" x-cloak class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Local:</span>
                        <select name="id_local_externo" class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-amber-300 bg-white">
                            <option value="">— Seleccionar —</option>
                            @foreach($localesExternos as $local)
                            <option value="{{ $local->id }}" {{ old('id_local_externo') == $local->id ? 'selected' : '' }}>{{ $local->nombre_local }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Observación --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 flex-shrink-0">Motivo *:</span>
                    <input type="text" name="observacion" x-ref="observacion" value="{{ old('observacion') }}" maxlength="200"
                        placeholder="Ej: frascos rotos en exhibición, conteo físico, devolución a proveedor..."
                        class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-amber-300 min-w-0">
                </div>
            </div>
        </div>

        {{-- ══════ BÚSQUEDA ══════ --}}
        <div class="card mt-4">
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
                        @keydown.enter.prevent="buscar()"
                        @keydown.escape="resultados = []"
                        class="form-input pl-10 pr-24"
                        placeholder="Nombre del producto o código de barras (solo muestra lo que hay en esta bodega)..."
                        autocomplete="off"
                    />
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                        <template x-if="buscando">
                            <svg class="animate-spin w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </template>
                        <span x-show="carrito.length > 0"
                            class="text-xs bg-amber-100 text-amber-700 font-bold px-2 py-0.5 rounded-full"
                            x-text="carrito.length + ' ítem' + (carrito.length !== 1 ? 's' : '')">
                        </span>
                    </div>
                </div>

                {{-- Resultados --}}
                <div x-show="resultados.length > 0"
                    class="mt-1 border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white z-20 relative">
                    <template x-for="p in resultados" :key="p.id">
                        <div @click="agregar(p); resultados = []; busqueda = ''; $refs.inputBusqueda.focus()"
                            class="flex items-center justify-between px-4 py-2.5 hover:bg-amber-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors group">
                            <div class="min-w-0 flex-1">
                                <span class="text-sm font-medium text-gray-900" x-text="p.nombre"></span>
                                <span x-show="p.talla" class="ml-2 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="p.talla"></span>
                            </div>
                            <div class="flex items-center gap-4 ml-3 flex-shrink-0">
                                <span class="text-xs font-mono text-gray-400" x-text="p.codigo_barras"></span>
                                <span class="text-xs text-gray-400">Stock: <span class="font-medium text-gray-700" x-text="p.stock_bodega"></span></span>
                                <span class="text-xs bg-amber-500 text-white px-2 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">+ Agregar</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ══════ TABLA ══════ --}}
        <div class="card mt-4">
            <template x-if="carrito.length === 0">
                <div class="text-center py-16 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="font-medium text-sm">Aún no hay productos</p>
                    <p class="text-xs text-gray-300 mt-1">Búscalos por nombre o escanea el código de barras</p>
                </div>
            </template>

            <template x-if="carrito.length > 0">
                <div>
                    <div class="card-header border-b border-gray-100">
                        <h3 class="card-title">Productos a sacar</h3>
                        <button type="button" @click="limpiar()" class="btn btn-secondary btn-sm">Limpiar todo</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="text-left px-4 py-2.5">#</th>
                                    <th class="text-left px-4 py-2.5">Producto</th>
                                    <th class="text-center px-3 py-2.5 w-28">Stock bodega</th>
                                    <th class="text-center px-3 py-2.5 w-28">Cantidad</th>
                                    <th class="w-10 px-2 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in carrito" :key="item.id">
                                    <tr class="border-b border-gray-50 hover:bg-gray-50/60 transition-colors">
                                        <td class="px-4 py-2.5 text-xs text-gray-400 font-mono" x-text="index + 1"></td>
                                        <td class="px-4 py-2.5">
                                            <p class="font-semibold text-gray-900 leading-tight" x-text="item.nombre"></p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span x-show="item.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium" x-text="item.talla"></span>
                                                <span class="text-xs text-gray-400 font-mono" x-text="item.codigo_barras"></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 text-center text-gray-500" x-text="item.stock_bodega"></td>
                                        <td class="px-3 py-2.5">
                                            <input type="number" x-model.number="item.cantidad" min="1" :max="item.stock_bodega"
                                                @change="ajustarCantidad(item)"
                                                class="w-full text-center px-1 py-1.5 text-sm font-bold border rounded-lg focus:ring-2 focus:ring-amber-300 bg-white"
                                                :class="item.cantidad > item.stock_bodega ? 'border-red-400 text-red-600' : 'border-gray-200'">
                                        </td>
                                        <td class="px-2 py-2.5 text-center">
                                            <button type="button" @click="quitar(index)"
                                                class="text-gray-300 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </td>
                                        <input type="hidden" :name="'items[' + index + '][id_detalle_producto]'" :value="item.id">
                                        <input type="hidden" :name="'items[' + index + '][cantidad]'" :value="item.cantidad">
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
            <div class="flex items-center gap-1 text-sm text-gray-500">
                <span class="font-semibold text-gray-900" x-text="carrito.length"></span>
                <span class="text-xs">ref. ·</span>
                <span class="font-semibold text-gray-900" x-text="totalUnidades"></span>
                <span class="text-xs">uds. a sacar</span>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <a href="{{ route('inventario.index') }}"
                    class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                    Cancelar
                </a>
                <button type="submit" form="form-salida" @click.prevent="confirmar()"
                    :disabled="carrito.length === 0 || enviando"
                    class="bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold px-6 py-2 rounded-xl text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Registrar Salida
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('salidaCarrito', (buscarUrl, bodegaDefecto, carritoInicial, tipoInicial) => ({
        buscarUrl,
        idBodega: String(bodegaDefecto),
        tipo: tipoInicial || 'Merma',
        tipos: [
            { valor: 'Merma',         texto: 'Merma / Daño' },
            { valor: 'Ajuste',        texto: 'Ajuste' },
            { valor: 'Salida',        texto: 'Salida / Devolución' },
            { valor: 'Transferencia', texto: 'Transferencia a externo' },
        ],
        busqueda: '',
        resultados: [],
        buscando: false,
        enviando: false,
        carrito: carritoInicial || [],

        get totalUnidades() {
            return this.carrito.reduce((s, i) => s + (parseInt(i.cantidad) || 0), 0);
        },

        cambiarBodega() {
            // El stock depende de la bodega: lo que ya estaba en el carrito deja de ser válido
            if (this.carrito.length > 0) showToast('Cambiaste de bodega: se vació el carrito', 'info');
            this.carrito = [];
            this.resultados = [];
        },

        async buscar() {
            const q = this.busqueda.trim();
            if (q.length < 2) { this.resultados = []; return; }
            this.buscando = true;
            try {
                const res = await fetch(`${this.buscarUrl}?q=${encodeURIComponent(q)}&id_bodega=${encodeURIComponent(this.idBodega)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error();
                const data = await res.json();

                // Lector de código de barras: coincidencia exacta se agrega sola
                const exacto = data.find(p => p.codigo_barras === q);
                if (exacto) {
                    this.agregar(exacto);
                    this.busqueda = '';
                    this.resultados = [];
                    return;
                }
                this.resultados = data;
                if (data.length === 0) showToast('No hay stock de ese producto en esta bodega', 'warning');
            } catch (e) {
                showToast('Error al buscar productos', 'error');
            } finally {
                this.buscando = false;
            }
        },

        agregar(p) {
            const existente = this.carrito.find(i => i.id === p.id);
            if (existente) {
                if (existente.cantidad >= existente.stock_bodega) {
                    showToast('No hay más stock de este producto en la bodega', 'warning');
                    return;
                }
                existente.cantidad++;
                showToast(`${p.nombre} — cantidad: ${existente.cantidad}`, 'info', 1500);
            } else {
                this.carrito.push({
                    id: p.id, nombre: p.nombre, talla: p.talla, codigo_barras: p.codigo_barras,
                    stock_bodega: p.stock_bodega, cantidad: 1,
                });
                showToast(`${p.nombre} agregado`, 'success', 1500);
            }
        },

        ajustarCantidad(item) {
            if (!item.cantidad || item.cantidad < 1) item.cantidad = 1;
            if (item.cantidad > item.stock_bodega) {
                item.cantidad = item.stock_bodega;
                showToast(`Solo hay ${item.stock_bodega} en esta bodega`, 'warning');
            }
        },

        quitar(index) { this.carrito.splice(index, 1); },

        limpiar() {
            if (confirm('¿Limpiar todos los productos?')) this.carrito = [];
        },

        confirmar() {
            if (this.carrito.length === 0) { showToast('Agrega al menos un producto', 'warning'); return; }
            if (!this.$refs.observacion.value.trim()) {
                showToast('Escribe el motivo de la salida', 'warning');
                this.$refs.observacion.focus();
                return;
            }
            const form = document.getElementById('form-salida');
            if (this.tipo === 'Transferencia' && !form.querySelector('[name="id_local_externo"]').value) {
                showToast('Selecciona el local externo', 'warning');
                return;
            }
            if (!confirm(`¿Confirmar salida de ${this.totalUnidades} unidad(es)? Esto reduce el stock de la bodega.`)) return;
            this.enviando = true;
            form.submit();
        },
    }));
});
</script>
@endpush
