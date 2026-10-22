@extends('layouts.mobile')

@section('title', 'Traslado Móvil')

@section('content')
<div
    x-data="trasladoMovil(
        '{{ route('traslados.buscar-producto') }}',
        {{ $bodegas->map(fn($b) => ['id' => $b->id, 'nombre' => $b->nombre . ' — ' . ($b->sucursal->nombre ?? '')])->values()->toJson() }},
        {{ $bodegaActivaId ?? 'null' }},
        @js((string) ($preloadBarcode ?? ''))
    )"
    class="flex flex-col min-h-screen pb-36"
>

    {{-- ══════ BARRA SUPERIOR ══════ --}}
    <header class="sticky top-0 z-30 bg-violet-700 text-white px-4 py-3 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('ventas.movil') }}"
               class="text-violet-200 hover:text-white p-1.5 rounded-lg hover:bg-violet-800 transition-colors mr-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <span class="text-2xl">📦</span>
            <div>
                <p class="text-sm font-bold leading-tight">Traslado de Mercancía</p>
                <p class="text-xs text-violet-200 leading-tight">{{ auth()->user()->nombre }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span x-show="items.length > 0"
                class="bg-white text-violet-700 text-xs font-bold px-2 py-0.5 rounded-full"
                x-text="totalItems + ' uds.'">
            </span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-violet-200 hover:text-white p-1.5 rounded-lg hover:bg-violet-800 transition-colors" title="Cerrar sesión">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </header>

    {{-- ══════ FORMULARIO ══════ --}}
    <form id="form-traslado" method="POST" action="{{ route('traslados.store') }}" class="flex flex-col flex-1 px-3 pt-3 gap-3">
        @csrf

        {{-- Flag móvil (para que el store() redirija al hub en vez de la lista de escritorio) --}}
        <input type="hidden" name="_from_movil" value="1">
        {{-- Hidden inputs para Alpine --}}
        <input type="hidden" name="id_bodega_origen" :value="idBodegaOrigen">
        <input type="hidden" name="id_bodega_destino" :value="idBodegaDestino">
        <input type="hidden" name="observacion" :value="observacion">

        {{-- Hidden inputs de items (generados por Alpine) --}}
        <template x-for="(item, idx) in items" :key="item.id">
            <span>
                <input type="hidden" :name="'items[' + idx + '][id_detalle_producto]'" :value="item.id">
                <input type="hidden" :name="'items[' + idx + '][cantidad]'" :value="item.cantidad">
            </span>
        </template>

        {{-- ── BODEGAS ──────────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-violet-100 p-4 space-y-3">

            {{-- Origen --}}
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1.5">
                    📤 Bodega de Origen
                </label>
                <select x-model="idBodegaOrigen" @change="cambiarOrigen()"
                    class="w-full text-sm border rounded-xl px-3 py-3 bg-white focus:ring-2 focus:ring-violet-300 font-medium"
                    :class="!idBodegaOrigen ? 'border-red-300 text-gray-400' : 'border-violet-300 text-gray-900'">
                    <option value="">— Selecciona bodega de origen —</option>
                    @foreach($bodegas as $bodega)
                    <option value="{{ $bodega->id }}">{{ $bodega->nombre }} — {{ $bodega->sucursal->nombre ?? '' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Flecha indicadora --}}
            <div class="flex items-center justify-center">
                <div class="flex items-center gap-2 text-violet-400">
                    <div class="h-px w-10 bg-violet-200"></div>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    <div class="h-px w-10 bg-violet-200"></div>
                </div>
            </div>

            {{-- Destino --}}
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1.5">
                    📥 Bodega de Destino
                </label>
                <select x-model="idBodegaDestino"
                    class="w-full text-sm border rounded-xl px-3 py-3 bg-white focus:ring-2 focus:ring-violet-300 font-medium"
                    :class="!idBodegaDestino ? 'border-gray-200 text-gray-400' : (idBodegaOrigen === idBodegaDestino ? 'border-red-400 text-red-600' : 'border-violet-300 text-gray-900')">
                    <option value="">— Selecciona bodega de destino —</option>
                    @foreach($bodegas as $bodega)
                    <option value="{{ $bodega->id }}">{{ $bodega->nombre }} — {{ $bodega->sucursal->nombre ?? '' }}</option>
                    @endforeach
                </select>
                <p x-show="idBodegaOrigen && idBodegaDestino && idBodegaOrigen === idBodegaDestino"
                   class="text-xs text-red-500 mt-1 font-medium">
                    ⚠ Origen y destino no pueden ser iguales
                </p>
            </div>

            {{-- Observación --}}
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1.5">Observación</label>
                <input type="text" x-model="observacion" placeholder="Opcional..."
                    class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-violet-300">
            </div>
        </div>

        {{-- ── ESCÁNER + BÚSQUEDA ───────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 space-y-2">

            <button type="button" @click="abrirScanner()"
                :disabled="!idBodegaOrigen"
                class="w-full py-5 rounded-2xl font-bold text-lg flex items-center justify-center gap-3 shadow-md transition-colors select-none text-white
                    bg-violet-600 hover:bg-violet-700 active:bg-violet-800 disabled:opacity-40 disabled:cursor-not-allowed">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.24M16.57 9H14m2.86-4.93A9 9 0 105.4 18.6M15 15l3 3"/>
                    <rect x="3" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="3" y="16" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="16" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                </svg>
                <span x-show="!idBodegaOrigen">Primero selecciona bodega origen</span>
                <span x-show="idBodegaOrigen">Escanear con cámara</span>
            </button>

            {{-- Búsqueda manual --}}
            <div x-show="idBodegaOrigen" class="flex gap-2">
                <input type="text" x-model="busqueda"
                    @input.debounce.400ms="buscarManual()"
                    @keydown.escape="resultados = []"
                    placeholder="O escribe nombre / código..."
                    autocomplete="off"
                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-violet-300">
                <button type="button" @click="buscarManual()"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl text-gray-600 active:bg-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>

            {{-- Resultados --}}
            <div x-show="resultados.length > 0" class="border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white">
                <div class="flex items-center justify-between px-3 py-1.5 bg-gray-50 border-b border-gray-100">
                    <span class="text-xs text-gray-500 font-medium" x-text="resultados.length + ' resultado(s)'"></span>
                    <button type="button" @click="resultados = []; busqueda = ''" class="text-xs text-gray-400 hover:text-gray-600">Cerrar</button>
                </div>
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="border-b border-gray-50 last:border-0 px-3 py-2.5 flex items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 leading-tight" x-text="producto.nombre"></p>
                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                <span x-show="producto.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="'T ' + producto.talla"></span>
                                <span class="text-xs font-semibold text-violet-600" x-text="'Stock: ' + producto.stock_bodega"></span>
                            </div>
                        </div>
                        <button type="button"
                            @click="agregarItem(producto); resultados = []; busqueda = ''"
                            class="px-3 py-1.5 bg-violet-600 text-white text-xs font-bold rounded-xl active:bg-violet-700 flex-shrink-0">
                            + Agregar
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── LISTA DE ÍTEMS ───────────────────────────────────────────────── --}}

        <template x-if="items.length === 0">
            <div class="bg-white rounded-2xl border border-dashed border-violet-200 py-10 text-center shadow-sm">
                <span class="text-5xl block mb-2">📦</span>
                <p class="text-sm font-medium text-gray-400">Sin productos aún</p>
                <p class="text-xs text-gray-300 mt-1">Escanea o busca un producto de la bodega origen</p>
            </div>
        </template>

        <template x-if="items.length > 0">
            <div class="space-y-2">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-sm font-bold text-gray-700">
                        Ítems a trasladar (<span x-text="items.length"></span>)
                    </h2>
                    <button type="button" @click="items = []" class="text-xs text-red-400 hover:text-red-600 font-medium">Limpiar</button>
                </div>

                <template x-for="(item, index) in items" :key="item.id">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
                        <div class="flex items-start gap-2 mb-2.5">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 leading-tight" x-text="item.nombre"></p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span x-show="item.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="'T' + item.talla"></span>
                                    <span class="text-xs text-violet-600 font-semibold" x-text="'Disponible: ' + item.stock_bodega"></span>
                                </div>
                            </div>
                            <button type="button" @click="quitarItem(index)"
                                class="flex-shrink-0 w-8 h-8 rounded-xl bg-red-50 hover:bg-red-100 text-red-400 flex items-center justify-center active:bg-red-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">Cantidad a trasladar:</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="cambiarCantidad(index, -1)"
                                    class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 active:bg-gray-300 flex items-center justify-center text-gray-600 font-bold text-lg select-none">−</button>
                                <span class="w-10 text-center text-base font-extrabold text-gray-900" x-text="item.cantidad"></span>
                                <button type="button" @click="cambiarCantidad(index, 1)"
                                    class="w-9 h-9 rounded-full bg-violet-100 hover:bg-violet-200 active:bg-violet-300 flex items-center justify-center text-violet-700 font-bold text-lg select-none">+</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

    </form>

    {{-- ══════ BARRA FIJA INFERIOR ══════ --}}
    <div class="fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-gray-200 shadow-2xl safe-area-bottom">
        <div class="px-3 py-2 space-y-2">

            <template x-if="idBodegaOrigen && idBodegaDestino && idBodegaOrigen !== idBodegaDestino && items.length > 0">
                <div class="flex items-center gap-2 bg-violet-50 rounded-xl px-3 py-2 border border-violet-100">
                    <svg class="w-4 h-4 text-violet-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-violet-600 font-medium leading-tight" x-text="items.length + ' producto(s) · ' + totalItems + ' unidades en total'"></p>
                </div>
            </template>

            <div class="flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-400 truncate" x-show="idBodegaOrigen && idBodegaDestino">
                        <span class="font-medium text-gray-600" x-text="bodegas.find(b => String(b.id) === idBodegaOrigen)?.nombre ?? ''"></span>
                        <span class="mx-1">→</span>
                        <span class="font-medium text-gray-600" x-text="bodegas.find(b => String(b.id) === idBodegaDestino)?.nombre ?? ''"></span>
                    </p>
                    <p class="text-xs text-gray-300" x-show="!idBodegaOrigen || !idBodegaDestino">Selecciona origen y destino</p>
                </div>
                <button type="submit" form="form-traslado"
                    @click.prevent="if(prepararFormulario()) document.getElementById('form-traslado').submit()"
                    :disabled="items.length === 0 || !idBodegaOrigen || !idBodegaDestino || idBodegaOrigen === idBodegaDestino"
                    class="flex-shrink-0 px-6 py-4 rounded-2xl font-bold text-base text-white shadow-lg transition-colors active:scale-95
                        bg-violet-600 hover:bg-violet-500 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100
                        flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Trasladar
                </button>
            </div>
        </div>
    </div>

    {{-- ══════ MODAL SCANNER ══════ --}}
    <div x-show="scannerModal"
         class="fixed inset-0 z-50 flex flex-col bg-black"
         style="display:none"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        <div class="flex items-center justify-between px-4 py-3 bg-black/80 text-white flex-shrink-0">
            <div>
                <p class="font-bold text-base">Escanear código</p>
                <p class="text-xs text-gray-400">Apunta la cámara al código de barras</p>
            </div>
            <button type="button" @click="cerrarScanner()"
                class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white active:bg-white/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="relative flex-1 flex items-center justify-center overflow-hidden bg-black">
            <div id="qr-traslado-reader" class="w-full max-w-sm"></div>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="border-2 border-violet-400 rounded-xl w-72 h-28 relative">
                    <div class="absolute -top-0.5 -left-0.5 w-6 h-6 border-t-4 border-l-4 border-violet-400 rounded-tl-xl"></div>
                    <div class="absolute -top-0.5 -right-0.5 w-6 h-6 border-t-4 border-r-4 border-violet-400 rounded-tr-xl"></div>
                    <div class="absolute -bottom-0.5 -left-0.5 w-6 h-6 border-b-4 border-l-4 border-violet-400 rounded-bl-xl"></div>
                    <div class="absolute -bottom-0.5 -right-0.5 w-6 h-6 border-b-4 border-r-4 border-violet-400 rounded-br-xl"></div>
                </div>
            </div>
        </div>

        <div class="bg-black/90 px-4 py-3 flex-shrink-0 space-y-2">
            <div class="flex gap-2">
                <input type="text" x-model="busqueda"
                    placeholder="O escribe el código manualmente..."
                    @keydown.enter.prevent="buscarPorCodigo(busqueda)"
                    class="flex-1 text-sm bg-white/10 border border-white/20 text-white placeholder-gray-500 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-violet-400 focus:bg-white/20">
                <button type="button" @click="buscarPorCodigo(busqueda)"
                    class="px-4 py-2.5 bg-violet-600 hover:bg-violet-500 text-white rounded-xl font-bold text-sm active:bg-violet-700">
                    Buscar
                </button>
            </div>

            <div x-show="resultados.length > 0" class="space-y-1.5 max-h-52 overflow-y-auto">
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="bg-white/10 rounded-xl px-3 py-2.5 flex items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white leading-tight" x-text="producto.nombre"></p>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span x-show="producto.talla" class="text-xs text-gray-400" x-text="'T' + producto.talla"></span>
                                <span class="text-xs text-violet-300 font-semibold" x-text="'Stock: ' + producto.stock_bodega"></span>
                            </div>
                        </div>
                        <button type="button"
                            @click="agregarItem(producto); cerrarScanner()"
                            class="px-3 py-2 bg-violet-600 text-white text-xs font-bold rounded-xl active:bg-violet-700 flex-shrink-0">
                            + Agregar
                        </button>
                    </div>
                </template>
                <button type="button" @click="reanudarScanner()"
                    class="w-full py-2 text-xs text-violet-300 hover:text-violet-200 font-medium">
                    ↺ Escanear otro código
                </button>
            </div>

            <div x-show="buscando" class="flex items-center justify-center gap-2 py-2">
                <svg class="animate-spin w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-xs text-gray-400">Buscando...</span>
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
    #qr-traslado-reader { width: 100% !important; border: none !important; }
    #qr-traslado-reader video { width: 100% !important; height: 100% !important; object-fit: cover !important; }
    #qr-traslado-reader__scan_region { min-height: 250px; }
    #qr-traslado-reader__dashboard { display: none !important; }
    .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom, 8px); }
</style>
@endpush
@endsection
