@extends('layouts.app')

@section('title', 'Generador de Etiquetas')

@section('content')
<div class="max-w-4xl mx-auto space-y-6"
     x-data="generadorEtiquetas()"
     x-init="init()">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Generador de Etiquetas</h1>
            <p class="text-sm text-gray-500 mt-1">Busca productos, define la cantidad y genera las etiquetas para impresión térmica (58 mm).</p>
        </div>
    </div>

    {{-- Buscador --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <label class="block text-sm font-semibold text-gray-700 mb-2">Buscar producto o código de barras</label>
        <div class="relative">
            <input
                type="text"
                x-model="busqueda"
                @input.debounce.300ms="buscar()"
                @keydown.escape="resultados = []"
                placeholder="Escribe el nombre o código de barras..."
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10"
                autocomplete="off"
            />
            <svg x-show="buscando" class="absolute right-3 top-2.5 w-5 h-5 text-gray-400 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        {{-- Resultados --}}
        <div x-show="resultados.length > 0" class="mt-2 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <div class="px-3 py-1.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <span class="text-xs text-gray-400">Haz clic en cada talla para agregarla</span>
                <button type="button" @click="resultados = []; busqueda = ''"
                    class="text-xs text-gray-400 hover:text-gray-600 transition-colors">✕ Cerrar</button>
            </div>
            <template x-for="item in resultados" :key="item.id">
                <button
                    type="button"
                    @click="agregar(item)"
                    :class="estaEnLista(item.id)
                        ? 'bg-emerald-50 hover:bg-emerald-100'
                        : 'hover:bg-blue-50'"
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-left border-b border-gray-100 last:border-0 transition-colors"
                >
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" x-text="item.producto"></p>
                        <p class="text-xs text-gray-500">
                            <span x-show="item.talla" x-text="'Talla: ' + item.talla + ' · '"></span>
                            <span x-text="item.codigo_barras"></span>
                        </p>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap" x-text="'Stock: ' + item.stock_actual"></span>
                    {{-- Icono dinámico: check si ya está agregado, + si no --}}
                    <template x-if="estaEnLista(item.id)">
                        <span class="text-xs font-semibold text-emerald-600 whitespace-nowrap flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-text="cantidadEnLista(item.id)"></span>
                        </span>
                    </template>
                    <template x-if="!estaEnLista(item.id)">
                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </template>
                </button>
            </template>
        </div>

        <p x-show="sinResultados" class="mt-2 text-sm text-gray-400 text-center py-3">Sin resultados para "<span x-text="busqueda"></span>"</p>
    </div>

    {{-- Lista de ítems agregados --}}
    <div x-show="lista.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">
                Etiquetas a generar
                <span class="ml-1 text-blue-600" x-text="'(' + totalEtiquetas + ' en total)'"></span>
            </h2>
            <button type="button" @click="lista = []" class="text-xs text-red-500 hover:text-red-700 transition-colors">
                Limpiar todo
            </button>
        </div>

        <div class="divide-y divide-gray-100">
            <template x-for="(item, index) in lista" :key="item.id">
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" x-text="item.producto"></p>
                        <p class="text-xs text-gray-500">
                            <span x-show="item.talla" x-text="'Talla: ' + item.talla + ' · '"></span>
                            <span x-text="item.codigo_barras"></span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="cambiarCantidad(index, -1)"
                            class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition-colors text-sm font-bold">−</button>
                        <input type="number"
                            x-model.number="item.cantidad"
                            min="1" max="200"
                            class="w-16 text-center text-sm border border-gray-300 rounded-md py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        <button type="button" @click="cambiarCantidad(index, 1)"
                            class="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition-colors text-sm font-bold">+</button>
                    </div>
                    <button type="button" @click="lista.splice(index, 1)"
                        class="ml-2 text-gray-400 hover:text-red-500 transition-colors flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        {{-- Botón imprimir --}}
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100">
            <form method="POST" action="{{ route('codigos-barras.imprimir') }}" @submit="prepararForm($event)">
                @csrf
                <template x-for="(item, i) in lista" :key="item.id">
                    <div>
                        <input type="hidden" :name="'items[' + i + '][id]'" :value="item.id">
                        <input type="hidden" :name="'items[' + i + '][cantidad]'" :value="item.cantidad">
                    </div>
                </template>
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Generar e Imprimir
                    <span class="text-blue-200" x-text="'(' + totalEtiquetas + ' etiquetas)'"></span>
                </button>
            </form>
        </div>
    </div>

    {{-- Estado vacío --}}
    <div x-show="lista.length === 0" class="text-center py-12 text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
        </svg>
        <p class="text-sm font-medium">Busca productos para agregar etiquetas</p>
    </div>
</div>

<script>
function generadorEtiquetas() {
    return {
        busqueda: '',
        resultados: [],
        buscando: false,
        sinResultados: false,
        lista: [],

        buscarUrl: '{{ route('inventario.buscar') }}',

        init() {},

        get totalEtiquetas() {
            return this.lista.reduce((sum, i) => sum + (parseInt(i.cantidad) || 0), 0);
        },

        async buscar() {
            this.sinResultados = false;
            if (this.busqueda.length < 2) { this.resultados = []; return; }
            this.buscando = true;
            try {
                const res = await fetch(`${this.buscarUrl}?q=${encodeURIComponent(this.busqueda)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.resultados = data;
                this.sinResultados = data.length === 0;
            } catch (e) {
                console.error(e);
            } finally {
                this.buscando = false;
            }
        },

        agregar(item) {
            const existe = this.lista.find(i => i.id === item.id);
            if (existe) {
                existe.cantidad = Math.min(200, existe.cantidad + 1);
            } else {
                this.lista.push({ ...item, cantidad: 1 });
            }
            // No cerramos la lista para poder seguir agregando tallas
        },

        estaEnLista(id) {
            return this.lista.some(i => i.id === id);
        },

        cantidadEnLista(id) {
            const item = this.lista.find(i => i.id === id);
            return item ? item.cantidad + ' ud.' : '';
        },

        cambiarCantidad(index, delta) {
            const nueva = (parseInt(this.lista[index].cantidad) || 1) + delta;
            if (nueva < 1) {
                this.lista.splice(index, 1);
            } else {
                this.lista[index].cantidad = Math.min(200, nueva);
            }
        },

        prepararForm(e) {
            if (this.lista.length === 0) {
                e.preventDefault();
                return;
            }
            // Abrir en nueva pestaña para no perder el generador
            e.target.target = '_blank';
        },
    };
}
</script>
@endsection
