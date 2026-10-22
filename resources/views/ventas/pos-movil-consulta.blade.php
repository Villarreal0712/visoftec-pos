@extends('layouts.mobile')
@section('title', 'Consultar Tallas')

@section('content')
<div
    x-data="consultaMovil(
        '{{ route('inventario.buscar-tallas') }}',
        {{ $bodegaActivaId ?? 'null' }},
        '{{ route('ventas.movil.venta') }}',
        '{{ route('ventas.movil.prestamo') }}',
        '{{ route('ventas.movil.traslado') }}'
    )"
    class="flex flex-col min-h-screen bg-teal-700"
>

    {{-- Header --}}
    <header class="px-4 pt-8 pb-4 flex items-center gap-3">
        <a href="{{ route('ventas.movil') }}"
           class="text-teal-200 hover:text-white p-2 rounded-xl hover:bg-teal-600/60 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <p class="text-teal-300 text-xs font-semibold uppercase tracking-widest">Consulta</p>
            <h1 class="text-2xl font-extrabold text-white leading-tight">Stock por talla</h1>
        </div>
    </header>

    {{-- Área principal --}}
    <div class="flex-1 flex flex-col px-4 pb-8 gap-4">

        {{-- Scanner --}}
        <div x-show="!resultado" class="flex flex-col gap-3">

            {{-- Visor de cámara --}}
            <div class="rounded-3xl overflow-hidden bg-black shadow-2xl relative">
                <div id="qr-consulta-reader" class="w-full" style="min-height: 220px;"></div>
                <div x-show="buscando" class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-3xl">
                    <div class="w-8 h-8 border-4 border-teal-400 border-t-transparent rounded-full animate-spin"></div>
                </div>
            </div>

            <p class="text-center text-teal-200 text-sm">
                Apunta al código del producto en exhibición
            </p>

            {{-- Búsqueda manual --}}
            <div class="flex gap-2">
                <input
                    type="text"
                    x-model="busqueda"
                    @keydown.enter.prevent="buscarManual()"
                    placeholder="O escribe el nombre del producto..."
                    class="flex-1 bg-white/10 text-white placeholder-teal-300 rounded-2xl px-4 py-3 text-sm border border-teal-500/40 focus:outline-none focus:ring-2 focus:ring-teal-300"
                >
                <button
                    @click="buscarManual()"
                    :disabled="busqueda.length < 2 || buscando"
                    class="bg-teal-500 hover:bg-teal-400 disabled:opacity-40 text-white rounded-2xl px-4 py-3 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>

            {{-- Sin resultado --}}
            <div x-show="sinResultado" class="bg-white/10 rounded-2xl px-4 py-6 text-center text-teal-200 text-sm">
                Producto no encontrado. Intenta con otro código o nombre.
            </div>
        </div>

        {{-- Resultado --}}
        <div x-show="resultado" x-cloak class="flex flex-col gap-4">

            {{-- Nombre del producto --}}
            <div class="bg-white/10 rounded-2xl px-4 py-3">
                <p class="text-teal-300 text-xs font-semibold uppercase tracking-wider mb-0.5">Producto</p>
                <p class="text-white text-xl font-extrabold" x-text="resultado?.producto?.nombre"></p>
            </div>

            {{-- Grid de tallas --}}
            <div>
                <p class="text-teal-300 text-xs font-semibold uppercase tracking-wider mb-3 px-1">
                    Tallas disponibles (bodega activa)
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <template x-for="t in resultado?.tallas" :key="t.id">
                        <div
                            :class="t.stock > 0
                                ? 'bg-white border-2 border-teal-400 shadow-lg'
                                : 'bg-white/15 border border-amber-400/50'"
                            class="rounded-2xl px-4 py-4 flex flex-col gap-2"
                        >
                            {{-- Talla + stock --}}
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-extrabold"
                                      :class="t.stock > 0 ? 'text-gray-900' : 'text-white'"
                                      x-text="'T' + t.talla"></span>
                                <span
                                    :class="t.stock > 0 ? 'bg-teal-100 text-teal-800' : 'bg-amber-400/20 text-amber-200'"
                                    class="text-xs font-bold px-2 py-0.5 rounded-full"
                                    x-text="t.stock > 0 ? t.stock + ' disp.' : 'Sin stock aquí'"
                                ></span>
                            </div>

                            {{-- Precio --}}
                            <p class="text-xs font-semibold"
                               :class="t.stock > 0 ? 'text-gray-500' : 'text-white/60'"
                               x-text="'$ ' + t.precio.toLocaleString('es-CO')"></p>

                            {{-- Disponibilidad en otras bodegas (solo si aquí no hay) --}}
                            <template x-if="t.stock === 0 && t.otras_bodegas.length > 0">
                                <p class="text-xs text-amber-200 leading-snug">
                                    Hay <span class="font-bold" x-text="t.otras_bodegas[0].cantidad"></span>
                                    en <span class="font-bold" x-text="t.otras_bodegas[0].nombre"></span>
                                    <template x-if="t.otras_bodegas.length > 1">
                                        <span> y otra(s) bodega(s)</span>
                                    </template>
                                </p>
                            </template>

                            {{-- Botón acción (siempre visible) --}}
                            <button
                                @click="t.stock > 0
                                    ? irAVender(t)
                                    : (t.otras_bodegas.length > 0 ? abrirOpcionesSinStock(t) : (tallaSeleccionada = t, irCredito()))"
                                :class="t.stock > 0
                                    ? 'bg-teal-600 hover:bg-teal-700'
                                    : 'bg-amber-500 hover:bg-amber-400'"
                                class="mt-1 w-full text-white text-sm font-bold rounded-xl py-2 transition-colors flex items-center justify-center gap-1.5">
                                <span x-text="t.stock > 0 ? 'Vender' : (t.otras_bodegas.length > 0 ? 'Ver opciones' : 'Préstamo')"></span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Escanear otro --}}
            <button
                @click="resetear()"
                class="w-full bg-white/10 hover:bg-white/20 text-white font-semibold rounded-2xl py-4 transition-colors flex items-center justify-center gap-2 border border-white/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Escanear otro producto
            </button>

        </div>
    </div>

    {{-- Modal: ¿Contado o crédito? --}}
    <div
        x-show="modalTipo"
        x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center"
        style="background: rgba(0,0,0,0.55);"
        @click.self="modalTipo = false"
    >
        <div class="w-full max-w-md bg-white rounded-t-3xl px-5 pt-6 pb-10 shadow-2xl"
             @click.stop>

            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-5"></div>

            <p class="text-center text-gray-500 text-sm mb-1">Talla seleccionada</p>
            <p class="text-center text-2xl font-extrabold text-gray-900 mb-6"
               x-text="'T' + (tallaSeleccionada?.talla ?? '')"></p>

            <p class="text-center font-semibold text-gray-700 mb-4">¿Cómo va a ser la venta?</p>

            <div class="flex flex-col gap-3">
                <button
                    @click="irContado()"
                    class="w-full bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold rounded-2xl py-4 transition-all flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Contado
                </button>

                <button
                    @click="irCredito()"
                    class="w-full bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold rounded-2xl py-4 transition-all flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Crédito / Préstamo
                </button>

                @can('inventario.entradas')
                <button
                    @click="irTraslado()"
                    class="w-full bg-violet-600 hover:bg-violet-700 active:scale-95 text-white font-bold rounded-2xl py-4 transition-all flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Traslado
                </button>
                @endcan

                <button
                    @click="modalTipo = false"
                    class="w-full text-gray-400 py-2 text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: sin stock aquí, pero hay en otra bodega --}}
    <div
        x-show="modalOtraBodega"
        x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center"
        style="background: rgba(0,0,0,0.55);"
        @click.self="modalOtraBodega = false"
    >
        <div class="w-full max-w-md bg-white rounded-t-3xl px-5 pt-6 pb-10 shadow-2xl"
             @click.stop>

            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-5"></div>

            <p class="text-center text-gray-500 text-sm mb-1">Talla seleccionada</p>
            <p class="text-center text-2xl font-extrabold text-gray-900 mb-2"
               x-text="'T' + (tallaSeleccionada?.talla ?? '')"></p>

            <p class="text-center text-sm text-gray-500 mb-6">
                Sin stock en tu bodega, pero hay
                <span class="font-bold text-gray-700" x-text="tallaSeleccionada?.otras_bodegas?.[0]?.cantidad"></span>
                en <span class="font-bold text-gray-700" x-text="tallaSeleccionada?.otras_bodegas?.[0]?.nombre"></span>.
            </p>

            <div class="flex flex-col gap-3">
                <button
                    @click="irTraslado()"
                    class="w-full bg-violet-600 hover:bg-violet-700 active:scale-95 text-white font-bold rounded-2xl py-4 transition-all flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Traer por traslado
                </button>

                <button
                    @click="irCredito()"
                    class="w-full bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold rounded-2xl py-4 transition-all flex items-center justify-center gap-3 text-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Pedir préstamo en su lugar
                </button>

                <button
                    @click="modalOtraBodega = false"
                    class="w-full text-gray-400 py-2 text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
