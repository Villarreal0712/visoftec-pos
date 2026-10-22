@extends('layouts.mobile')

@section('title', 'POS Móvil')

@section('content')
<div
    x-data="posMovil(
        '{{ route('productos.buscar-pos') }}',
        '{{ route('clientes.rapido') }}',
        {{ $localesExternos->map(fn($l) => ['id' => $l->id, 'nombre' => $l->nombre_local])->values()->toJson() }},
        {{ $puedeVenderPrestamo ? 'true' : 'false' }},
        {{ $cuentas->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'tipo' => $c->tipo])->values()->toJson() }}
    )"
    class="flex flex-col min-h-screen pb-40"
>

    {{-- ══════ BARRA SUPERIOR ══════ --}}
    <header class="sticky top-0 z-30 bg-blue-700 text-white px-4 py-3 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2.5">
            <svg class="w-6 h-6 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <div>
                <p class="text-sm font-bold leading-tight">POS Móvil</p>
                <p class="text-xs text-blue-200 leading-tight">{{ auth()->user()->nombre }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{-- Contador carrito --}}
            <span x-show="carrito.length > 0"
                class="bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-0.5 rounded-full"
                x-text="carrito.reduce((s,i) => s+i.cantidad, 0) + ' uds.'">
            </span>
            {{-- Ir a escritorio --}}
            @can('ventas.crear')
            <a href="{{ route('ventas.create') }}"
               class="text-blue-200 hover:text-white text-xs underline hidden sm:block">Escritorio</a>
            @endcan
            {{-- Cerrar sesión --}}
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-blue-200 hover:text-white p-1.5 rounded-lg hover:bg-blue-600 transition-colors" title="Cerrar sesión">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </header>

    @if($bodegaEsAcopio)
    <div class="mx-3 mt-3 flex items-start gap-2 bg-red-50 border border-red-300 rounded-xl px-3 py-2.5">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-red-800">Tu bodega asignada es de acopio</p>
            <p class="text-xs text-red-600 mt-0.5">No se puede vender desde ahí. Pide al administrador que te asigne la bodega de tu sucursal.</p>
        </div>
    </div>
    @endif

    {{-- ══════ FORMULARIO PRINCIPAL ══════ --}}
    <form id="form-movil" method="POST" action="{{ route('ventas.store') }}" class="flex flex-col flex-1 px-3 pt-3 gap-3">
        @csrf

        {{-- ── CLIENTE + CRÉDITO ────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 space-y-2.5">

            {{-- Cliente --}}
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-gray-500 flex-shrink-0 w-16">Cliente</label>
                <x-selector-cliente :clientes="$clientes" placeholder="— Público general —" movil />
                @can('clientes.crear')
                <button type="button" @click="abrirModalCliente()"
                    class="flex-shrink-0 w-9 h-9 rounded-xl bg-blue-500 text-white flex items-center justify-center shadow-sm active:bg-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </button>
                @endcan
            </div>

            {{-- Método pago (no aplica si la venta es a crédito) --}}
            <div class="flex items-center gap-2 flex-wrap">
                <label class="text-xs font-semibold text-gray-500 flex-shrink-0 w-16">Pago</label>
                <div class="flex gap-1.5 flex-wrap" :class="esCredito ? 'opacity-40 pointer-events-none' : ''">
                    @foreach($cuentas as $cuenta)
                    <label class="cursor-pointer">
                        <input type="radio" name="pago_selector" value="{{ $cuenta->id }}" x-model="pagoSeleccionado" :disabled="esCredito" class="sr-only peer" {{ $loop->first ? 'checked' : '' }}>
                        <span class="inline-block px-3 py-1.5 text-xs font-semibold rounded-full border-2 transition-colors
                            peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700
                            border-gray-200 text-gray-500">{{ $cuenta->tipo === 'Efectivo' ? '💵' : '📦' }} {{ $cuenta->nombre }}</span>
                    </label>
                    @endforeach
                    <label class="cursor-pointer">
                        <input type="radio" name="pago_selector" value="Mixto" x-model="pagoSeleccionado" :disabled="esCredito" class="sr-only peer">
                        <span class="inline-block px-3 py-1.5 text-xs font-semibold rounded-full border-2 transition-colors
                            peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700
                            border-gray-200 text-gray-500">Mixto</span>
                    </label>
                </div>
                <span class="text-xs text-amber-600 font-medium" x-show="esCredito">(no aplica a crédito)</span>
                <input type="hidden" name="id_cuenta_bancaria" :value="idCuentaBancariaSeleccionada">
                <input type="hidden" name="metodo_pago" :value="pagoSeleccionado === 'Mixto' ? 'Mixto' : ''">
            </div>

            {{-- Crédito --}}
            <div class="flex items-center gap-3 flex-wrap">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="es_credito" value="1" x-model="esCredito"
                        class="w-5 h-5 rounded border-gray-300 text-blue-600">
                    <span class="text-sm font-medium text-gray-700">A crédito</span>
                </label>
                <div x-show="esCredito" class="flex items-center gap-1.5">
                    <span class="text-xs text-gray-500">Días:</span>
                    <input type="number" name="dias_credito" x-model="diasCredito" min="1" max="365"
                        class="w-20 text-sm border border-gray-200 rounded-xl px-2 py-1.5 focus:ring-2 focus:ring-blue-300 text-center">
                </div>
            </div>

            {{-- Observaciones --}}
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-gray-500 flex-shrink-0 w-16">Notas</label>
                <input type="text" name="observaciones" placeholder="Observaciones..."
                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-300 min-w-0">
            </div>
        </div>

        {{-- ── BOTÓN ESCANEAR + BÚSQUEDA ────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 space-y-2">

            {{-- Botón principal de escaneo --}}
            <button type="button" @click="abrirScanner()"
                class="w-full py-5 rounded-2xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-lg flex items-center justify-center gap-3 shadow-md transition-colors select-none">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.24M16.57 9H14m2.86-4.93A9 9 0 105.4 18.6M15 15l3 3"/>
                    <rect x="3" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="3" y="16" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="16" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                </svg>
                Escanear con cámara
            </button>

            {{-- Búsqueda manual --}}
            <div class="flex gap-2">
                <input type="text" x-model="busqueda"
                    @input.debounce.400ms="buscarManual()"
                    @keydown.escape="resultados = []"
                    placeholder="O escribe nombre / código..."
                    autocomplete="off"
                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-300">
                <button type="button" @click="buscarManual()"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl text-gray-600 active:bg-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>

            {{-- Resultados búsqueda --}}
            <div x-show="resultados.length > 0"
                class="border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white">
                <div class="flex items-center justify-between px-3 py-1.5 bg-gray-50 border-b border-gray-100">
                    <span class="text-xs text-gray-500 font-medium" x-text="resultados.length + ' resultado(s)'"></span>
                    <button type="button" @click="resultados = []; busqueda = ''"
                        class="text-xs text-gray-400 hover:text-gray-600">Cerrar</button>
                </div>
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="border-b border-gray-50 last:border-0 px-3 py-2.5"
                         :class="producto.stock === 0 ? 'bg-amber-50' : ''">
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
                                    <span x-show="producto.stock === 0" class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">Sin stock</span>
                                </div>
                            </div>
                            <div class="flex gap-1.5 flex-shrink-0">
                                {{-- Agregar normal --}}
                                <button type="button" x-show="producto.stock > 0"
                                    @click="agregarProducto(producto); resultados = []; busqueda = ''"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-xl active:bg-blue-700">
                                    + Agregar
                                </button>
                                {{-- Préstamo --}}
                                <button type="button"
                                    x-show="puedeVenderPrestamo && localesExternos.length > 0"
                                    @click="agregarDirecto(producto); resultados = []; busqueda = ''"
                                    class="px-3 py-1.5 bg-amber-500 text-white text-xs font-bold rounded-xl active:bg-amber-600">
                                    Préstamo
                                </button>
                                {{-- Sin permisos de préstamo y sin stock --}}
                                <span x-show="producto.stock === 0 && !puedeVenderPrestamo"
                                    class="text-xs text-gray-400 italic self-center">No disponible</span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── CARRITO ──────────────────────────────────────────────────────────── --}}

        {{-- Vacío --}}
        <template x-if="carrito.length === 0">
            <div class="bg-white rounded-2xl border border-dashed border-gray-200 py-10 text-center text-gray-300 shadow-sm">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-sm font-medium text-gray-400">Carrito vacío</p>
                <p class="text-xs text-gray-300 mt-1">Escanea un código o busca un producto</p>
            </div>
        </template>

        {{-- Items del carrito --}}
        <template x-if="carrito.length > 0">
            <div class="space-y-2">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-sm font-bold text-gray-700">
                        Carrito (<span x-text="carrito.length"></span> ítem<span x-show="carrito.length !== 1">s</span>)
                    </h2>
                    <button type="button" @click="limpiarCarrito()"
                        class="text-xs text-red-400 hover:text-red-600 font-medium">Limpiar</button>
                </div>

                <template x-for="(item, index) in carrito" :key="item.id + '-' + index">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 space-y-2.5"
                         :class="item.esDirecto ? 'border-amber-200 bg-amber-50/30' : ''">

                        {{-- Nombre + Badge + Eliminar --}}
                        <div class="flex items-start gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <p class="text-sm font-bold text-gray-900 leading-tight" x-text="item.producto"></p>
                                    <span x-show="item.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="'T' + item.talla"></span>
                                    <span x-show="item.esDirecto" class="text-xs bg-amber-200 text-amber-800 px-1.5 py-0.5 rounded font-semibold">Préstamo</span>
                                </div>
                            </div>
                            <button type="button" @click="quitarProducto(index)"
                                class="flex-shrink-0 w-8 h-8 rounded-xl bg-red-50 hover:bg-red-100 text-red-400 flex items-center justify-center active:bg-red-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Precio + Cantidad + Subtotal --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            {{-- Precio unit --}}
                            <div class="flex items-center gap-1 flex-1 min-w-28">
                                <span class="text-xs text-gray-400 flex-shrink-0">$</span>
                                <input type="text" inputmode="numeric"
                                    :value="fmtMiles(item.precio)"
                                    @input="applyMiles($event, v => item.precio = Math.max(0, v))"
                                    class="flex-1 text-sm font-bold border border-gray-200 rounded-xl px-2 py-1.5 focus:ring-2 focus:ring-blue-300 text-center min-w-0"
                                    :class="item.costo > 0 && item.precio < item.costo ? 'border-amber-400 bg-amber-50 text-amber-700' : 'bg-white text-gray-900'">
                            </div>

                            {{-- Cantidad --}}
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button" @click="cambiarCantidad(index, -1)"
                                    class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 active:bg-gray-300 flex items-center justify-center text-gray-600 font-bold text-lg select-none">−</button>
                                <span class="w-8 text-center text-sm font-extrabold text-gray-900" x-text="item.cantidad"></span>
                                <button type="button" @click="cambiarCantidad(index, 1)"
                                    class="w-9 h-9 rounded-full bg-blue-100 hover:bg-blue-200 active:bg-blue-300 flex items-center justify-center text-blue-700 font-bold text-lg select-none">+</button>
                            </div>

                            {{-- Subtotal --}}
                            <div class="flex-shrink-0 text-right">
                                <p class="text-xs text-gray-400">Subtotal</p>
                                <p class="text-sm font-extrabold text-gray-900">$<span x-text="(item.cantidad * item.precio).toLocaleString('es-CO')"></span></p>
                            </div>
                        </div>

                        {{-- Préstamo: costo + local externo --}}
                        <template x-if="item.esDirecto">
                            <div class="border-t border-amber-200 pt-2 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-amber-700 font-semibold flex-shrink-0">Costo:</span>
                                    <div class="flex items-center gap-1 flex-1">
                                        <span class="text-xs text-gray-400">$</span>
                                        <input type="text" inputmode="numeric"
                                            :value="fmtMiles(item.costoDirecto)"
                                            @input="applyMiles($event, v => item.costoDirecto = v)"
                                            placeholder="Costo (opcional)"
                                            class="flex-1 text-sm border border-amber-200 bg-amber-50 rounded-xl px-2 py-1.5 focus:ring-2 focus:ring-amber-300 min-w-0">
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-amber-700 font-semibold flex-shrink-0">Local:</span>
                                    <select x-model="item.localExternoId"
                                        class="flex-1 text-sm border rounded-xl px-2 py-1.5 focus:ring-2 focus:ring-amber-300 min-w-0"
                                        :class="!item.localExternoId ? 'border-red-300 bg-red-50 text-red-600' : 'border-amber-300 bg-amber-50'">
                                        <option value="">— Selecciona local * —</option>
                                        <template x-for="local in localesExternos" :key="local.id">
                                            <option :value="local.id" x-text="local.nombre"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </template>

                        {{-- Hidden inputs --}}
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

    </form>{{-- /form-movil --}}

    {{-- ══════ BARRA FIJA INFERIOR ══════ --}}
    <div class="fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-gray-200 shadow-2xl safe-area-bottom">
        <div class="px-3 py-2 space-y-2">

            {{-- Efectivo recibido --}}
            <template x-if="esEfectivoSeleccionado && !esCredito && carrito.length > 0">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 flex-shrink-0">Recibido:</span>
                    <div class="relative flex-1">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                        <input type="text" inputmode="numeric"
                            :value="fmtMiles(efectivoPagado)"
                            @input="applyMiles($event, v => efectivoPagado = v)"
                            class="w-full pl-7 pr-3 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300">
                    </div>
                    <div x-show="efectivoPagado >= total && total > 0" class="flex items-center gap-1 flex-shrink-0">
                        <span class="text-xs text-gray-500">Cambio:</span>
                        <span class="text-sm font-bold text-emerald-600">$<span x-text="cambio.toLocaleString('es-CO')"></span></span>
                    </div>
                </div>
            </template>

            {{-- Avisos --}}
            <template x-if="esCredito && !clienteId">
                <p class="text-xs text-amber-600 font-medium text-center">⚠ Selecciona un cliente para venta a crédito</p>
            </template>
            <template x-if="carrito.some(i => i.esDirecto && !i.localExternoId)">
                <p class="text-xs text-red-500 font-medium text-center">⚠ Falta seleccionar el local del préstamo</p>
            </template>

            {{-- Total + Registrar --}}
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <p class="text-xs text-gray-400">Total</p>
                    <p class="text-2xl font-extrabold text-gray-900 leading-tight">
                        $<span x-text="total.toLocaleString('es-CO')"></span>
                    </p>
                </div>
                <button type="submit" form="form-movil"
                    @click.prevent="if(prepararFormulario()) document.getElementById('form-movil').submit()"
                    :disabled="carrito.length === 0 || (esCredito && !clienteId) || carrito.some(i => i.esDirecto && !i.localExternoId)"
                    class="flex-shrink-0 px-6 py-4 rounded-2xl font-bold text-base text-white shadow-lg transition-colors active:scale-95
                        bg-emerald-500 hover:bg-emerald-400 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100
                        flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-show="!esCredito">Registrar Venta</span>
                    <span x-show="esCredito">Registrar Crédito</span>
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

        {{-- Header scanner --}}
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

        {{-- Visor cámara --}}
        <div class="relative flex-1 flex items-center justify-center overflow-hidden bg-black">
            <div id="qr-reader" class="w-full max-w-sm"></div>

            {{-- Overlay indicador --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="border-2 border-blue-400 rounded-xl w-72 h-28 relative">
                    <div class="absolute -top-0.5 -left-0.5 w-6 h-6 border-t-4 border-l-4 border-blue-400 rounded-tl-xl"></div>
                    <div class="absolute -top-0.5 -right-0.5 w-6 h-6 border-t-4 border-r-4 border-blue-400 rounded-tr-xl"></div>
                    <div class="absolute -bottom-0.5 -left-0.5 w-6 h-6 border-b-4 border-l-4 border-blue-400 rounded-bl-xl"></div>
                    <div class="absolute -bottom-0.5 -right-0.5 w-6 h-6 border-b-4 border-r-4 border-blue-400 rounded-br-xl"></div>
                </div>
            </div>
        </div>

        {{-- Búsqueda manual desde scanner --}}
        <div class="bg-black/90 px-4 py-3 flex-shrink-0 space-y-2">
            <div class="flex gap-2">
                <input type="text" x-model="busqueda"
                    placeholder="O escribe el código manualmente..."
                    @keydown.enter.prevent="buscarPorCodigo(busqueda)"
                    class="flex-1 text-sm bg-white/10 border border-white/20 text-white placeholder-gray-500 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-400 focus:bg-white/20">
                <button type="button" @click="buscarPorCodigo(busqueda)"
                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold text-sm active:bg-blue-700">
                    Buscar
                </button>
            </div>

            {{-- Resultados dentro del scanner --}}
            <div x-show="resultados.length > 0" class="space-y-1.5 max-h-60 overflow-y-auto">
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="bg-white/10 rounded-xl px-3 py-2.5 flex items-center justify-between gap-2"
                         :class="producto.stock === 0 ? 'border border-amber-500/40' : ''">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white leading-tight" x-text="producto.producto"></p>
                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                <span x-show="producto.talla" class="text-xs text-gray-400" x-text="'T' + producto.talla"></span>
                                <span class="text-xs font-bold text-blue-300">$<span x-text="producto.precio.toLocaleString('es-CO')"></span></span>
                                <span x-show="producto.stock > 0" class="text-xs text-gray-400" x-text="'Stock: ' + producto.stock"></span>
                                <span x-show="producto.stock === 0" class="text-xs text-amber-400 font-semibold">Sin stock</span>
                            </div>
                        </div>
                        <div class="flex gap-1.5 flex-shrink-0">
                            <button type="button" x-show="producto.stock > 0"
                                @click="agregarProducto(producto); cerrarScanner()"
                                class="px-3 py-2 bg-emerald-500 text-white text-xs font-bold rounded-xl active:bg-emerald-600">
                                + Agregar
                            </button>
                            <button type="button" x-show="puedeVenderPrestamo && localesExternos.length > 0"
                                @click="agregarDirecto(producto)"
                                class="px-3 py-2 bg-amber-500 text-white text-xs font-bold rounded-xl active:bg-amber-600">
                                Préstamo
                            </button>
                        </div>
                    </div>
                </template>
                <button type="button" @click="reanudarScanner()"
                    class="w-full py-2 text-xs text-blue-300 hover:text-blue-200 font-medium">
                    ↺ Escanear otro código
                </button>
            </div>

            {{-- Spinner búsqueda --}}
            <div x-show="buscando" class="flex items-center justify-center gap-2 py-2">
                <svg class="animate-spin w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span class="text-xs text-gray-400">Buscando...</span>
            </div>
        </div>
    </div>{{-- /modal scanner --}}

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

            {{-- Handle --}}
            <div class="flex justify-center pt-3 pb-1 sm:hidden">
                <div class="w-10 h-1 bg-gray-200 rounded-full"></div>
            </div>

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-900">Nuevo Cliente</h2>
                <button @click="cerrarModalCliente()" class="text-gray-400 p-1.5 rounded-xl hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Campos --}}
            <div class="px-5 py-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold text-gray-500 block mb-1">Documento *</label>
                    <input type="text" x-model="nuevoCliente.documento"
                        x-ref="inputDocMovil"
                        class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-300"
                        :class="erroresCliente.documento ? 'border-red-400' : ''"
                        placeholder="Cédula / NIT">
                    <p x-show="erroresCliente.documento" class="text-xs text-red-500 mt-1" x-text="erroresCliente.documento"></p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-500 block mb-1">Nombre *</label>
                    <input type="text" x-model="nuevoCliente.nombre"
                        class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-300"
                        :class="erroresCliente.nombre ? 'border-red-400' : ''"
                        placeholder="Nombre completo">
                    <p x-show="erroresCliente.nombre" class="text-xs text-red-500 mt-1" x-text="erroresCliente.nombre"></p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 block mb-1">Teléfono</label>
                        <input type="tel" x-model="nuevoCliente.telefono"
                            class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-300"
                            placeholder="3001234567">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 block mb-1">Límite crédito</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                            <input type="text" inputmode="numeric"
                                :value="fmtMiles(nuevoCliente.limite_credito)"
                                @input="applyMiles($event, v => nuevoCliente.limite_credito = v)"
                                class="w-full text-sm border border-gray-200 rounded-xl pl-6 pr-2 py-2.5 focus:ring-2 focus:ring-blue-300"
                                placeholder="0">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-5 pb-5 flex gap-2">
                <button @click="cerrarModalCliente()" type="button"
                    class="flex-1 py-3 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-600 hover:bg-gray-50 active:bg-gray-100">
                    Cancelar
                </button>
                <button @click="guardarCliente()" type="button"
                    :disabled="guardandoCliente"
                    class="flex-1 py-3 bg-blue-600 rounded-2xl text-sm font-bold text-white disabled:opacity-50 flex items-center justify-center gap-2 active:bg-blue-700">
                    <svg x-show="!guardandoCliente" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="guardandoCliente ? 'Guardando...' : 'Crear Cliente'"></span>
                </button>
            </div>
        </div>
    </div>{{-- /modal cliente --}}

</div>{{-- /x-data --}}

@push('styles')
<style>
    /* Fuerza el visor de la cámara a ocupar bien el espacio */
    #qr-reader {
        width: 100% !important;
        border: none !important;
    }
    #qr-reader video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }
    #qr-reader__scan_region {
        min-height: 250px;
    }
    #qr-reader__dashboard {
        display: none !important;
    }
    /* Espacio safe area para notch/home indicator */
    .safe-area-bottom {
        padding-bottom: env(safe-area-inset-bottom, 8px);
    }
    .pb-safe {
        padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 1.25rem);
    }
</style>
@endpush
@endsection
