@extends('layouts.app')

@section('title', 'Nuevo Traslado')
@section('page-title', 'Nuevo Traslado de Inventario')

@section('content')
<div x-data="trasladoForm()" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Columna izquierda: configuración + búsqueda --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Errores de validación --}}
        @if($errors->any())
        <div class="alert-error">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
        </div>
        @endif

        {{-- Bodegas --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Bodegas</h3></div>
            <div class="card-body">
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Bodega Origen *</label>
                        <select x-model="idBodegaOrigen" @change="limpiarProductos()"
                                name="id_bodega_origen_display" class="form-input" required>
                            <option value="">— Seleccionar —</option>
                            @foreach($bodegas as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}{{ $b->sucursal ? ' ('.$b->sucursal->nombre.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bodega Destino *</label>
                        <select x-model="idBodegaDestino" name="id_bodega_destino_display" class="form-input" required>
                            <option value="">— Seleccionar —</option>
                            @foreach($bodegas as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}{{ $b->sucursal ? ' ('.$b->sucursal->nombre.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p x-show="idBodegaOrigen && idBodegaOrigen === idBodegaDestino"
                   class="text-sm text-red-600 mt-2" style="display:none">
                   Las bodegas de origen y destino deben ser diferentes.
                </p>
            </div>
        </div>

        {{-- Buscador de productos --}}
        <div class="card" style="overflow:visible">
            <div class="card-header"><h3 class="card-title">Buscar Producto</h3></div>
            <div class="card-body space-y-3" style="overflow:visible">
                <div x-show="!idBodegaOrigen" class="text-sm text-amber-600 bg-amber-50 rounded-lg px-3 py-2">
                    Selecciona la bodega origen primero para buscar productos con stock.
                </div>
                <div x-show="idBodegaOrigen" class="relative">
                    <input type="text" x-model="busqueda" @input.debounce.300ms="buscarProductos()"
                           @keydown.escape="resultados = []"
                           @keydown.enter.prevent="resultados.length === 1 ? agregarProducto(resultados[0]) : buscarProductos()"
                           placeholder="Nombre o código de barras..."
                           class="form-input w-full pl-9">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span x-show="cargando" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </span>

                    {{-- Dropdown resultados --}}
                    <div x-show="resultados.length > 0" @click.outside="resultados = []"
                         class="absolute z-20 left-0 right-0 mt-1 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden"
                         style="display:none">
                        <template x-for="r in resultados" :key="r.id">
                            <button type="button" @click="agregarProducto(r)"
                                    class="w-full text-left px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0 transition-colors">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900" x-text="r.nombre"></p>
                                        <p class="text-xs text-gray-400">
                                            <span x-show="r.talla" x-text="'Talla: ' + r.talla + ' · '"></span>
                                            <span x-text="r.codigo_barras" class="font-mono"></span>
                                        </p>
                                    </div>
                                    <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded-full flex-shrink-0"
                                          x-text="'Stock: ' + r.stock_bodega"></span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista de productos agregados --}}
        <div class="card" x-show="items.length > 0" style="display:none">
            <div class="card-header">
                <h3 class="card-title">Productos a trasladar</h3>
                <span class="text-xs text-gray-400" x-text="items.length + ' producto(s)'"></span>
            </div>
            <div class="card-body p-0">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Producto</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">Disponible</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500 w-32">Cantidad</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, idx) in items" :key="item.id">
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-900" x-text="item.nombre"></p>
                                    <p class="text-xs text-gray-400 font-mono" x-text="item.codigo_barras"></p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded-full"
                                          x-text="item.stock_bodega"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" x-model.number="item.cantidad"
                                           :max="item.stock_bodega" min="1"
                                           @change="validarCantidad(item)"
                                           class="form-input text-center w-full">
                                    <p x-show="item.cantidad > item.stock_bodega"
                                       class="text-xs text-red-500 mt-0.5 text-center" style="display:none">
                                        Máx: <span x-text="item.stock_bodega"></span>
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" @click="quitarItem(idx)"
                                            class="text-red-400 hover:text-red-600 transition-colors p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Columna derecha: resumen + submit --}}
    <div class="space-y-5">
        <div class="card sticky top-4">
            <div class="card-header"><h3 class="card-title">Resumen</h3></div>
            <div class="card-body space-y-4">

                {{-- Flecha origen → destino --}}
                <div class="text-sm space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-400 text-xs uppercase tracking-wider w-12">Origen</span>
                        <span class="font-medium text-gray-900 truncate"
                              x-text="idBodegaOrigen ? nombreBodega(idBodegaOrigen) : '—'"></span>
                    </div>
                    <div class="flex items-center gap-2 pl-12">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-400 text-xs uppercase tracking-wider w-12">Destino</span>
                        <span class="font-medium text-gray-900 truncate"
                              x-text="idBodegaDestino ? nombreBodega(idBodegaDestino) : '—'"></span>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-3 space-y-1">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Productos</span>
                        <span class="font-semibold" x-text="items.length"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Unidades totales</span>
                        <span class="font-semibold" x-text="items.reduce((s,i) => s + (i.cantidad||0), 0)"></span>
                    </div>
                </div>

                {{-- Observación --}}
                <div class="form-group">
                    <label class="form-label">Observación</label>
                    <textarea x-model="observacion" name="observacion_display" rows="2"
                              placeholder="Motivo del traslado..."
                              class="form-input resize-none"></textarea>
                </div>

                {{-- Formulario oculto que se envía --}}
                <form id="form-traslado" method="POST" action="{{ route('traslados.store') }}" @submit.prevent="enviar()">
                    @csrf
                    <input type="hidden" name="id_bodega_origen" :value="idBodegaOrigen">
                    <input type="hidden" name="id_bodega_destino" :value="idBodegaDestino">
                    <input type="hidden" name="observacion" :value="observacion">
                    <template x-for="(item, idx) in items" :key="item.id">
                        <span>
                            <input type="hidden" :name="'items[' + idx + '][id_detalle_producto]'" :value="item.id">
                            <input type="hidden" :name="'items[' + idx + '][cantidad]'" :value="item.cantidad">
                        </span>
                    </template>

                    <button type="submit"
                            :disabled="!puedeEnviar()"
                            :class="puedeEnviar() ? 'btn-primary w-full' : 'btn-primary w-full opacity-40 cursor-not-allowed'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        Confirmar Traslado
                    </button>
                </form>

                <a href="{{ route('traslados.index') }}" class="btn-secondary w-full text-center block">Cancelar</a>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function trasladoForm() {
    return {
        idBodegaOrigen: '',
        idBodegaDestino: '',
        observacion: '',
        busqueda: '',
        resultados: [],
        cargando: false,
        items: [],
        bodegas: @json($bodegas->map(fn($b) => ['id' => $b->id, 'nombre' => $b->nombre])->values()),

        nombreBodega(id) {
            const b = this.bodegas.find(b => b.id == id);
            return b ? b.nombre : '—';
        },

        async buscarProductos() {
            if (!this.idBodegaOrigen || this.busqueda.trim().length < 2) {
                this.resultados = [];
                return;
            }
            this.cargando = true;
            try {
                const url = `{{ route('traslados.buscar-producto') }}?id_bodega=${this.idBodegaOrigen}&q=${encodeURIComponent(this.busqueda)}`;
                const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                this.resultados = await resp.json();
            } catch (e) {
                this.resultados = [];
            } finally {
                this.cargando = false;
            }
        },

        agregarProducto(r) {
            const existe = this.items.find(i => i.id === r.id);
            if (existe) {
                existe.cantidad = Math.min(existe.cantidad + 1, existe.stock_bodega);
            } else {
                this.items.push({ ...r, cantidad: 1 });
            }
            this.resultados = [];
            this.busqueda = '';
        },

        quitarItem(idx) {
            this.items.splice(idx, 1);
        },

        limpiarProductos() {
            this.items = [];
            this.busqueda = '';
            this.resultados = [];
        },

        validarCantidad(item) {
            if (item.cantidad < 1) item.cantidad = 1;
            if (item.cantidad > item.stock_bodega) item.cantidad = item.stock_bodega;
        },

        puedeEnviar() {
            return this.idBodegaOrigen &&
                   this.idBodegaDestino &&
                   this.idBodegaOrigen !== this.idBodegaDestino &&
                   this.items.length > 0 &&
                   this.items.every(i => i.cantidad >= 1 && i.cantidad <= i.stock_bodega);
        },

        enviar() {
            if (!this.puedeEnviar()) return;
            document.getElementById('form-traslado').submit();
        }
    };
}
</script>
@endpush
