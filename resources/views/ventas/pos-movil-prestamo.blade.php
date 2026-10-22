@extends('layouts.mobile')

@section('title', 'Préstamo a Crédito')

@section('content')
<div
    x-data="posMovilPrestamo(
        '{{ route('productos.buscar-pos') }}',
        '{{ route('clientes.rapido') }}',
        @js((string) ($preloadBarcode ?? '')),
        {{ $localesExternos->map(fn($l) => ['id' => $l->id, 'nombre' => $l->nombre_local])->values()->toJson() }}
    )"
    class="flex flex-col min-h-screen pb-40"
>

    {{-- ══════ BARRA SUPERIOR ══════ --}}
    <header class="sticky top-0 z-30 bg-amber-600 text-white px-4 py-3 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('ventas.movil') }}"
               class="text-amber-200 hover:text-white p-1.5 rounded-lg hover:bg-amber-700 transition-colors mr-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <span class="text-2xl">🤝</span>
            <div>
                <p class="text-sm font-bold leading-tight">Préstamo a Crédito</p>
                <p class="text-xs text-amber-200 leading-tight">{{ auth()->user()->nombre }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span x-show="carrito.length > 0"
                class="bg-white text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full"
                x-text="carrito.reduce((s,i) => s+i.cantidad, 0) + ' uds.'">
            </span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-amber-200 hover:text-white p-1.5 rounded-lg hover:bg-amber-700 transition-colors" title="Cerrar sesión">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </header>

    {{-- ══════ FORMULARIO PRINCIPAL ══════ --}}
    <form id="form-prestamo" method="POST" action="{{ route('ventas.store') }}" class="flex flex-col flex-1 px-3 pt-3 gap-3">
        @csrf
        {{-- Crédito forzado --}}
        <input type="hidden" name="es_credito" value="1">
        <input type="hidden" name="metodo_pago" value="Crédito">

        {{-- ── CLIENTE ──────────────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-3 space-y-2.5">

            {{-- Aviso crédito obligatorio --}}
            <div class="flex items-center gap-2 bg-amber-50 rounded-xl px-3 py-2 border border-amber-200">
                <span class="text-amber-500 text-lg">🔒</span>
                <div>
                    <p class="text-xs font-bold text-amber-700">Modo Préstamo — Crédito activado</p>
                    <p class="text-xs text-amber-500">Esta venta siempre quedará como crédito</p>
                </div>
            </div>

            {{-- Cliente (REQUERIDO) --}}
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-gray-500 flex-shrink-0 w-16">Cliente *</label>
                <x-selector-cliente :clientes="$clientes" placeholder="— Selecciona un cliente —" movil requerido />
                @can('clientes.crear')
                <button type="button" @click="abrirModalCliente()"
                    class="flex-shrink-0 w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-sm active:bg-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </button>
                @endcan
            </div>

            {{-- Días crédito --}}
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-gray-500 flex-shrink-0 w-16">Días</label>
                <input type="number" name="dias_credito" x-model="diasCredito" min="1" max="365"
                    class="w-24 text-sm border border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-amber-300 text-center">
                <span class="text-xs text-gray-400">días de plazo</span>
            </div>

            {{-- Observaciones --}}
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-gray-500 flex-shrink-0 w-16">Notas</label>
                <input type="text" name="observaciones" placeholder="Observaciones..."
                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-amber-300 min-w-0">
            </div>
        </div>

        {{-- ── BOTÓN ESCANEAR + BÚSQUEDA ────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 space-y-2">

            <button type="button" @click="abrirScanner()"
                class="w-full py-5 rounded-2xl bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-bold text-lg flex items-center justify-center gap-3 shadow-md transition-colors select-none">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.24M16.57 9H14m2.86-4.93A9 9 0 105.4 18.6M15 15l3 3"/>
                    <rect x="3" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="3" y="16" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="16" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                </svg>
                Escanear con cámara
            </button>

            <div class="flex gap-2">
                <input type="text" x-model="busqueda"
                    @input.debounce.400ms="buscarManual()"
                    @keydown.escape="resultados = []"
                    placeholder="O escribe nombre / código..."
                    autocomplete="off"
                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-300">
                <button type="button" @click="buscarManual()"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl text-gray-600 active:bg-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>

            <div x-show="resultados.length > 0" class="border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white">
                <div class="flex items-center justify-between px-3 py-1.5 bg-gray-50 border-b border-gray-100">
                    <span class="text-xs text-gray-500 font-medium" x-text="resultados.length + ' resultado(s)'"></span>
                    <button type="button" @click="resultados = []; busqueda = ''" class="text-xs text-gray-400 hover:text-gray-600">Cerrar</button>
                </div>
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="border-b border-gray-50 last:border-0 px-3 py-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 leading-tight" x-text="producto.producto"></p>
                                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                    <span x-show="producto.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium" x-text="'T ' + producto.talla"></span>
                                    <span class="text-xs font-bold text-gray-700">$<span x-text="producto.precio.toLocaleString('es-CO')"></span></span>
                                    <span x-show="producto.stock > 0"
                                        class="text-xs"
                                        :class="producto.stock <= 3 ? 'text-amber-600 font-semibold' : 'text-gray-400'"
                                        x-text="'Stock: ' + producto.stock"></span>
                                    <span x-show="producto.stock === 0" class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-medium">Sin stock</span>
                                </div>
                            </div>
                            <template x-if="producto.stock > 0">
                                <button type="button"
                                    @click="agregarProducto(producto); resultados = []; busqueda = ''"
                                    class="px-3 py-1.5 bg-amber-600 text-white text-xs font-bold rounded-xl active:bg-amber-700 flex-shrink-0">
                                    + Agregar
                                </button>
                            </template>
                            <template x-if="!(producto.stock > 0) && localesExternos.length > 0">
                                <button type="button"
                                    @click="agregarDirecto(producto); resultados = []; busqueda = ''"
                                    class="px-3 py-1.5 bg-orange-500 text-white text-xs font-bold rounded-xl active:bg-orange-600 flex-shrink-0">
                                    Directo
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── CARRITO ──────────────────────────────────────────────────────────── --}}

        <template x-if="carrito.length === 0">
            <div class="bg-white rounded-2xl border border-dashed border-amber-200 py-10 text-center shadow-sm">
                <span class="text-5xl block mb-2">🤝</span>
                <p class="text-sm font-medium text-gray-400">Carrito vacío</p>
                <p class="text-xs text-gray-300 mt-1">Escanea o busca un producto</p>
            </div>
        </template>

        <template x-if="carrito.length > 0">
            <div class="space-y-2">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-sm font-bold text-gray-700">
                        Carrito (<span x-text="carrito.length"></span> ítem<span x-show="carrito.length !== 1">s</span>)
                    </h2>
                    <button type="button" @click="limpiarCarrito()" class="text-xs text-red-400 hover:text-red-600 font-medium">Limpiar</button>
                </div>

                <template x-for="(item, index) in carrito" :key="item.id + '-' + index">
                    <div class="bg-white rounded-2xl shadow-sm border p-3 space-y-2.5"
                         :class="item.esDirecto ? 'border-orange-200 bg-orange-50/30' : 'border-gray-100'">
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <p class="text-sm font-bold text-gray-900 leading-tight" x-text="item.producto"></p>
                                    <span x-show="item.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="'T' + item.talla"></span>
                                    <span x-show="item.esDirecto" class="text-xs bg-orange-200 text-orange-800 px-1.5 py-0.5 rounded font-semibold">Directo</span>
                                </div>
                            </div>
                            <button type="button" @click="quitarProducto(index)"
                                class="flex-shrink-0 w-8 h-8 rounded-xl bg-red-50 hover:bg-red-100 text-red-400 flex items-center justify-center active:bg-red-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="flex items-center gap-1 flex-1 min-w-28">
                                <span class="text-xs text-gray-400 flex-shrink-0">$</span>
                                <input type="text" inputmode="numeric"
                                    :value="fmtMiles(item.precio)"
                                    @input="applyMiles($event, v => item.precio = Math.max(0, v))"
                                    class="flex-1 text-sm font-bold border border-gray-200 rounded-xl px-2 py-1.5 focus:ring-2 focus:ring-amber-300 text-center min-w-0 bg-white text-gray-900">
                            </div>

                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button" @click="cambiarCantidad(index, -1)"
                                    class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 active:bg-gray-300 flex items-center justify-center text-gray-600 font-bold text-lg select-none">−</button>
                                <span class="w-8 text-center text-sm font-extrabold text-gray-900" x-text="item.cantidad"></span>
                                <button type="button" @click="cambiarCantidad(index, 1)"
                                    class="w-9 h-9 rounded-full bg-amber-100 hover:bg-amber-200 active:bg-amber-300 flex items-center justify-center text-amber-700 font-bold text-lg select-none">+</button>
                            </div>

                            <div class="flex-shrink-0 text-right">
                                <p class="text-xs text-gray-400">Subtotal</p>
                                <p class="text-sm font-extrabold text-gray-900">$<span x-text="(item.cantidad * item.precio).toLocaleString('es-CO')"></span></p>
                            </div>
                        </div>

                        {{-- Sección directo: costo + local externo --}}
                        <template x-if="item.esDirecto">
                            <div class="border-t border-orange-200 pt-2 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-orange-700 font-semibold flex-shrink-0">Costo:</span>
                                    <div class="flex items-center gap-1 flex-1">
                                        <span class="text-xs text-gray-400">$</span>
                                        <input type="text" inputmode="numeric"
                                            :value="fmtMiles(item.costoDirecto)"
                                            @input="applyMiles($event, v => item.costoDirecto = v)"
                                            placeholder="Costo (opcional)"
                                            class="flex-1 text-sm border border-orange-200 bg-orange-50 rounded-xl px-2 py-1.5 focus:ring-2 focus:ring-orange-300 min-w-0">
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-orange-700 font-semibold flex-shrink-0">Local:</span>
                                    <select x-model="item.localExternoId"
                                        class="flex-1 text-sm border rounded-xl px-2 py-1.5 focus:ring-2 focus:ring-orange-300 min-w-0"
                                        :class="!item.localExternoId ? 'border-red-300 bg-red-50 text-red-600' : 'border-orange-300 bg-orange-50'">
                                        <option value="">— Selecciona local * —</option>
                                        <template x-for="local in localesExternos" :key="local.id">
                                            <option :value="local.id" x-text="local.nombre"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>

                        <input type="hidden" :name="'items[' + index + '][id_detalle_producto]'" :value="item.id">
                        <input type="hidden" :name="'items[' + index + '][precio_venta]'"        :value="item.precio">
                        <input type="hidden" :name="'items[' + index + '][cantidad]'"            :value="item.cantidad">
                        <input type="hidden" :name="'items[' + index + '][es_directo]'"          :value="item.esDirecto ? '1' : '0'">
                        <input type="hidden" :name="'items[' + index + '][costo_directo]'"       :value="item.costoDirecto || ''">
                        <input type="hidden" :name="'items[' + index + '][id_local_externo]'"    :value="item.localExternoId || ''">
                    </div>
                </template>
            </div>
        </template>

    </form>

    {{-- ══════ BARRA FIJA INFERIOR ══════ --}}
    <div class="fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-gray-200 shadow-2xl safe-area-bottom">
        <div class="px-3 py-2 space-y-2">

            <template x-if="!clienteId">
                <p class="text-xs text-red-500 font-medium text-center">⚠ Selecciona un cliente para el préstamo</p>
            </template>
            <template x-if="carrito.some(i => i.esDirecto && !i.localExternoId)">
                <p class="text-xs text-orange-600 font-medium text-center">⚠ Falta seleccionar el local del ítem directo</p>
            </template>

            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <p class="text-xs text-gray-400">Total préstamo</p>
                    <p class="text-2xl font-extrabold text-gray-900 leading-tight">
                        $<span x-text="total.toLocaleString('es-CO')"></span>
                    </p>
                </div>
                <button type="submit" form="form-prestamo"
                    @click.prevent="if(prepararFormulario()) document.getElementById('form-prestamo').submit()"
                    :disabled="carrito.length === 0 || !clienteId || carrito.some(i => i.esDirecto && !i.localExternoId)"
                    class="flex-shrink-0 px-6 py-4 rounded-2xl font-bold text-base text-white shadow-lg transition-colors active:scale-95
                        bg-amber-600 hover:bg-amber-500 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100
                        flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Registrar Préstamo
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
            <div id="qr-prestamo-reader" class="w-full max-w-sm"></div>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="border-2 border-amber-400 rounded-xl w-72 h-28 relative">
                    <div class="absolute -top-0.5 -left-0.5 w-6 h-6 border-t-4 border-l-4 border-amber-400 rounded-tl-xl"></div>
                    <div class="absolute -top-0.5 -right-0.5 w-6 h-6 border-t-4 border-r-4 border-amber-400 rounded-tr-xl"></div>
                    <div class="absolute -bottom-0.5 -left-0.5 w-6 h-6 border-b-4 border-l-4 border-amber-400 rounded-bl-xl"></div>
                    <div class="absolute -bottom-0.5 -right-0.5 w-6 h-6 border-b-4 border-r-4 border-amber-400 rounded-br-xl"></div>
                </div>
            </div>
        </div>

        <div class="bg-black/90 px-4 py-3 flex-shrink-0 space-y-2">
            <div class="flex gap-2">
                <input type="text" x-model="busqueda"
                    placeholder="O escribe el código manualmente..."
                    @keydown.enter.prevent="buscarPorCodigo(busqueda)"
                    class="flex-1 text-sm bg-white/10 border border-white/20 text-white placeholder-gray-500 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-400 focus:bg-white/20">
                <button type="button" @click="buscarPorCodigo(busqueda)"
                    class="px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xl font-bold text-sm active:bg-amber-700">
                    Buscar
                </button>
            </div>

            <div x-show="resultados.length > 0" class="space-y-1.5 max-h-60 overflow-y-auto">
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="bg-white/10 rounded-xl px-3 py-2.5 flex items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white leading-tight" x-text="producto.producto"></p>
                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                <span x-show="producto.talla" class="text-xs text-gray-400" x-text="'T' + producto.talla"></span>
                                <span class="text-xs font-bold text-amber-300">$<span x-text="producto.precio.toLocaleString('es-CO')"></span></span>
                                <span x-show="producto.stock > 0" class="text-xs text-gray-400" x-text="'Stock: ' + producto.stock"></span>
                                <span x-show="producto.stock === 0" class="text-xs text-red-400 font-semibold">Sin stock</span>
                            </div>
                        </div>
                        <div class="flex gap-1.5 flex-shrink-0">
                            <template x-if="producto.stock > 0">
                                <button type="button"
                                    @click="agregarProducto(producto); cerrarScanner()"
                                    class="px-3 py-2 bg-amber-600 text-white text-xs font-bold rounded-xl active:bg-amber-700">
                                    + Agregar
                                </button>
                            </template>
                            <template x-if="!(producto.stock > 0) && localesExternos.length > 0">
                                <button type="button"
                                    @click="agregarDirecto(producto); cerrarScanner()"
                                    class="px-3 py-2 bg-orange-500 text-white text-xs font-bold rounded-xl active:bg-orange-600">
                                    Directo
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
                <button type="button" @click="reanudarScanner()"
                    class="w-full py-2 text-xs text-amber-300 hover:text-amber-200 font-medium">
                    ↺ Escanear otro código
                </button>
            </div>

            <div x-show="buscando" class="flex items-center justify-center gap-2 py-2">
                <svg class="animate-spin w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-xs text-gray-400">Buscando...</span>
            </div>
        </div>
    </div>

    {{-- ══════ MODAL CLIENTE RÁPIDO ══════ --}}
    <div x-show="modalCliente"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
         style="display:none"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">

        <div class="absolute inset-0 bg-black/50" @click="cerrarModalCliente()"></div>

        <div class="relative bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl shadow-2xl pb-safe"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-y-full sm:translate-y-4 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100">

            <div class="flex justify-center pt-3 pb-1 sm:hidden">
                <div class="w-10 h-1 bg-gray-200 rounded-full"></div>
            </div>

            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900">Nuevo Cliente</h2>
                <button @click="cerrarModalCliente()" class="text-gray-400 p-1.5 rounded-xl hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-5 py-4 space-y-3">
                {{-- Documento OPCIONAL --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 block mb-1">
                        Documento
                        <span class="font-normal text-gray-400">(opcional — si no tiene, se asignará uno automático)</span>
                    </label>
                    <input type="text" x-model="nuevoCliente.documento"
                        class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-300"
                        :class="erroresCliente.documento ? 'border-red-400' : ''"
                        placeholder="Cédula / NIT (dejar vacío si no tiene)">
                    <p x-show="erroresCliente.documento" class="text-xs text-red-500 mt-1" x-text="erroresCliente.documento"></p>
                </div>
                {{-- Nombre REQUERIDO --}}
                <div>
                    <label class="text-xs font-semibold text-gray-500 block mb-1">Nombre *</label>
                    <input type="text" x-model="nuevoCliente.nombre"
                        x-ref="inputNombrePrestamo"
                        class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-300"
                        :class="erroresCliente.nombre ? 'border-red-400' : ''"
                        placeholder="Nombre completo">
                    <p x-show="erroresCliente.nombre" class="text-xs text-red-500 mt-1" x-text="erroresCliente.nombre"></p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 block mb-1">Teléfono</label>
                        <input type="tel" x-model="nuevoCliente.telefono"
                            class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-300"
                            placeholder="3001234567">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 block mb-1">Límite crédito</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                            <input type="text" inputmode="numeric"
                                :value="fmtMiles(nuevoCliente.limite_credito)"
                                @input="applyMiles($event, v => nuevoCliente.limite_credito = v)"
                                class="w-full text-sm border border-gray-200 rounded-xl pl-6 pr-2 py-2.5 focus:ring-2 focus:ring-amber-300"
                                placeholder="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-5 pb-5 flex gap-2">
                <button @click="cerrarModalCliente()" type="button"
                    class="flex-1 py-3 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-600 hover:bg-gray-50 active:bg-gray-100">
                    Cancelar
                </button>
                <button @click="guardarCliente()" type="button"
                    :disabled="guardandoCliente"
                    class="flex-1 py-3 bg-amber-600 rounded-2xl text-sm font-bold text-white disabled:opacity-50 flex items-center justify-center gap-2 active:bg-amber-700">
                    <svg x-show="!guardandoCliente" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="guardandoCliente ? 'Guardando...' : 'Crear Cliente'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

@push('styles')
<style>
    #qr-prestamo-reader { width: 100% !important; border: none !important; }
    #qr-prestamo-reader video { width: 100% !important; height: 100% !important; object-fit: cover !important; }
    #qr-prestamo-reader__scan_region { min-height: 250px; }
    #qr-prestamo-reader__dashboard { display: none !important; }
    .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom, 8px); }
    .pb-safe { padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 1.25rem); }
</style>
@endpush
@endsection
