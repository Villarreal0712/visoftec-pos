@extends('layouts.app')

@section('title', 'Nueva Venta - POS')
@section('page-title', 'Punto de Venta')

@section('content')
<div x-data="pos('{{ route('productos.buscar-pos') }}', '{{ route('clientes.rapido') }}', {{ $localesExternos->map(fn($l) => ['id' => $l->id, 'nombre' => $l->nombre_local])->values()->toJson() }}, {{ $puedeVenderPrestamo ? 'true' : 'false' }}, {{ $cuentas->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'tipo' => $c->tipo])->values()->toJson() }})" class="space-y-4 pb-24">

    @if(!$cajaAbierta)
    <div class="flex items-start gap-3 bg-amber-50 border border-amber-300 rounded-xl px-4 py-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-amber-800">No hay caja abierta para este local</p>
            <p class="text-xs text-amber-600 mt-0.5">Las ventas se registrarán pero no quedarán asociadas a un cuadre de caja activo.</p>
        </div>
        @can('caja.abrir')
        <a href="{{ route('cuadre-caja.create') }}"
           class="flex-shrink-0 text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-lg transition-colors">
            Abrir caja
        </a>
        @endcan
    </div>
    @endif

    @if($bodegaEsAcopio)
    <div class="flex items-start gap-3 bg-red-50 border border-red-300 rounded-xl px-4 py-3">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-red-800">Tu bodega asignada es de acopio</p>
            <p class="text-xs text-red-600 mt-0.5">No se puede vender desde una bodega de acopio. Pide al administrador que te asigne la bodega de tu sucursal.</p>
        </div>
    </div>
    @endif

    <form id="form-venta" method="POST" action="{{ route('ventas.store') }}">
        @csrf

        {{-- ══════ CABECERA COMPACTA ══════ --}}
        {{-- overflow visible: .card recorta, y la lista del buscador de clientes debe salir de la tarjeta --}}
        <div class="card" style="overflow: visible">
            <div class="card-body py-3">
                <div class="flex flex-wrap items-center gap-4">

                    {{-- Cliente --}}
                    <div class="flex items-center gap-2 flex-1 min-w-48">
                        <span class="text-xs text-gray-500 flex-shrink-0">Cliente:</span>
                        <x-selector-cliente :clientes="$clientes" placeholder="— Sin cliente (venta directa) —" />
                        @can('clientes.crear')
                        <button type="button" @click="abrirModalCliente()"
                            title="Crear cliente rápido"
                            class="flex-shrink-0 w-7 h-7 rounded-lg bg-blue-500 hover:bg-blue-600 text-white flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </button>
                        @endcan
                    </div>

                    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>

                    {{-- Método de pago (no aplica si la venta es a crédito) --}}
                    <div class="flex items-center gap-2" :class="esCredito ? 'opacity-40 pointer-events-none' : ''">
                        <span class="text-xs text-gray-500 flex-shrink-0">Pago:</span>
                        <div class="flex gap-1 flex-wrap">
                            @foreach($cuentas as $cuenta)
                            <label class="cursor-pointer">
                                <input type="radio" name="pago_selector" value="{{ $cuenta->id }}" x-model="pagoSeleccionado" :disabled="esCredito" class="sr-only peer" {{ $loop->first ? 'checked' : '' }}>
                                <span class="inline-block px-3 py-1 text-xs font-medium rounded-full border-2 transition-colors cursor-pointer
                                    peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700
                                    border-gray-200 text-gray-500 hover:border-gray-300">{{ $cuenta->tipo === 'Efectivo' ? '💵' : '📦' }} {{ $cuenta->nombre }}</span>
                            </label>
                            @endforeach
                            <label class="cursor-pointer">
                                <input type="radio" name="pago_selector" value="Mixto" x-model="pagoSeleccionado" :disabled="esCredito" class="sr-only peer">
                                <span class="inline-block px-3 py-1 text-xs font-medium rounded-full border-2 transition-colors cursor-pointer
                                    peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700
                                    border-gray-200 text-gray-500 hover:border-gray-300">Mixto</span>
                            </label>
                        </div>
                        <input type="hidden" name="id_cuenta_bancaria" :value="idCuentaBancariaSeleccionada">
                        <input type="hidden" name="metodo_pago" :value="pagoSeleccionado === 'Mixto' ? 'Mixto' : ''">
                    </div>
                    <span class="text-xs text-amber-600 font-medium" x-show="esCredito">(no aplica a crédito)</span>

                    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>

                    {{-- Crédito --}}
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="es_credito" value="1" x-model="esCredito" class="rounded border-gray-300 text-blue-600">
                        <span class="text-xs text-gray-600 font-medium">A crédito</span>
                    </label>

                    <div x-show="esCredito" class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">Días:</span>
                        <input type="number" name="dias_credito" x-model="diasCredito" min="1" max="365" step="1"
                            class="w-20 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300">
                    </div>

                    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>

                    {{-- Observaciones --}}
                    <div class="flex items-center gap-2 flex-1 min-w-48">
                        <span class="text-xs text-gray-500 flex-shrink-0">Obs.:</span>
                        <input type="text" name="observaciones" placeholder="Notas adicionales..."
                            class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-blue-300 min-w-0">
                    </div>

                    {{-- Bodega activa del usuario (asignada en perfil) --}}
                    @if(auth()->user()->bodega)
                    <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 flex-shrink-0">Bodega:</span>
                        <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-lg">
                            {{ auth()->user()->bodega->nombre }}
                        </span>
                    </div>
                    @endif
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
                        x-init="$nextTick(() => $refs.inputBusqueda.focus())"
                        @input.debounce.300ms="buscarProducto()"
                        @keydown.escape="resultados = []"
                        class="form-input pl-10 pr-24"
                        placeholder="Nombre del producto o código de barras..."
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
                    <template x-for="producto in resultados" :key="producto.id">
                        <div class="border-b border-gray-50 last:border-0"
                             :class="producto.stock === 0 ? 'bg-amber-50' : ''">

                            {{-- Con stock: clic directo + opción préstamo --}}
                            <div x-show="producto.stock > 0"
                                class="flex items-center justify-between px-4 py-2.5 hover:bg-blue-50 transition-colors group">
                                <div class="min-w-0 flex-1 cursor-pointer"
                                    @click="agregarProducto(producto); $refs.inputBusqueda.focus()">
                                    <span class="text-sm font-medium text-gray-900" x-text="producto.producto"></span>
                                    <span x-show="producto.talla" class="ml-2 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="producto.talla"></span>
                                </div>
                                <div class="flex items-center gap-3 ml-3 flex-shrink-0">
                                    <span class="text-xs font-mono text-gray-400" x-text="producto.codigo_barras"></span>
                                    <span class="text-xs font-bold text-gray-700 text-money">$<span x-text="producto.precio.toLocaleString('es-CO')"></span></span>
                                    <span class="text-xs" :class="producto.stock <= 3 ? 'text-amber-600 font-medium' : 'text-gray-400'">
                                        Stock: <span x-text="producto.stock"></span>
                                    </span>
                                    <span class="text-xs bg-blue-500 text-white px-2 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
                                        @click.stop="agregarProducto(producto); $refs.inputBusqueda.focus()">+ Agregar</span>
                                    {{-- Botón préstamo también disponible con stock propio --}}
                                    <template x-if="puedeVenderPrestamo && localesExternos.length > 0">
                                        <button type="button"
                                            @click.stop="agregarDirecto(producto); $refs.inputBusqueda.focus()"
                                            class="text-xs bg-amber-500 hover:bg-amber-600 text-white px-2 py-0.5 rounded-full font-semibold transition-colors flex items-center gap-1 opacity-0 group-hover:opacity-100">
                                            Préstamo
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Sin stock: botón venta directa --}}
                            <div x-show="producto.stock === 0"
                                class="flex items-center justify-between px-4 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <span class="text-sm font-medium text-gray-700" x-text="producto.producto"></span>
                                    <span x-show="producto.talla" class="ml-2 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="producto.talla"></span>
                                    <span class="ml-2 text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">Sin stock</span>
                                </div>
                                <div class="flex items-center gap-3 ml-3 flex-shrink-0">
                                    <span class="text-xs font-bold text-gray-700 text-money">$<span x-text="producto.precio.toLocaleString('es-CO')"></span></span>
                                    {{-- Sin permiso: no mostrar nada --}}
                                    <template x-if="!puedeVenderPrestamo">
                                        <span class="text-xs text-gray-400 italic">Sin stock</span>
                                    </template>
                                    {{-- Con permiso pero sin locales creados --}}
                                    <template x-if="puedeVenderPrestamo && localesExternos.length === 0">
                                        <a href="{{ route('locales-externos.index') }}"
                                           class="text-xs text-amber-700 bg-amber-100 border border-amber-300 px-3 py-1 rounded-full font-semibold flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                            Crea un local externo primero
                                        </a>
                                    </template>
                                    {{-- Con permiso y locales disponibles --}}
                                    <template x-if="puedeVenderPrestamo && localesExternos.length > 0">
                                        <button type="button"
                                            @click="agregarDirecto(producto); $refs.inputBusqueda.focus()"
                                            class="text-xs bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded-full font-semibold transition-colors flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Préstamo
                                        </button>
                                    </template>
                                </div>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="font-medium text-sm">El carrito está vacío</p>
                    <p class="text-xs text-gray-300 mt-1">Busca productos por nombre o escanea el código de barras</p>
                </div>
            </template>

            {{-- Tabla --}}
            <template x-if="carrito.length > 0">
                <div>
                    <div class="card-header border-b border-gray-100">
                        <h3 class="card-title">Productos en venta</h3>
                        <button type="button" @click="limpiarCarrito()" class="btn btn-secondary btn-sm">Limpiar todo</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="text-left px-4 py-2.5">#</th>
                                    <th class="text-left px-4 py-2.5">Producto</th>
                                    <th class="text-center px-3 py-2.5 w-36">Precio unit.</th>
                                    <th class="text-center px-3 py-2.5 w-44">Préstamo *</th>
                                    <th class="text-center px-3 py-2.5 w-32">Cantidad</th>
                                    <th class="text-right px-4 py-2.5 w-28">Subtotal</th>
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
                                            <div class="flex items-center gap-2">
                                                <p class="font-semibold text-gray-900 leading-tight" x-text="item.producto"></p>
                                                <span x-show="item.esDirecto"
                                                    class="text-xs bg-amber-100 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded font-semibold flex-shrink-0">
                                                    Préstamo
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span x-show="item.talla"
                                                    class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-medium"
                                                    x-text="'Talla: ' + item.talla"></span>
                                                <span x-show="!item.esDirecto && item.costo > 0 && item.precio < item.costo"
                                                    class="text-xs text-amber-600 font-medium">
                                                    ⚠ Bajo costo
                                                </span>
                                            </div>
                                        </td>

                                        {{-- Precio editable --}}
                                        <td class="px-3 py-2.5">
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-medium">$</span>
                                                <input type="text" inputmode="numeric"
                                                    :value="fmtMiles(item.precio)"
                                                    @input="applyMiles($event, v => item.precio = Math.max(0, v))"
                                                    class="w-full pl-6 pr-1 py-1.5 text-sm font-bold border rounded-lg focus:ring-2 focus:ring-blue-300 focus:border-blue-400 text-money"
                                                    :class="item.costo > 0 && item.precio < item.costo
                                                        ? 'border-amber-400 bg-amber-50 text-amber-700'
                                                        : 'border-gray-200 bg-white text-gray-900'">
                                            </div>
                                            <p x-show="item.precio !== item.precioBase"
                                               class="text-xs text-blue-500 mt-0.5 cursor-pointer hover:underline text-center"
                                               @click="item.precio = item.precioBase">
                                                Base: $<span x-text="item.precioBase.toLocaleString('es-CO')"></span>
                                            </p>
                                        </td>

                                        {{-- Costo + Local externo (solo préstamos) --}}
                                        <td class="px-3 py-2.5">
                                            <template x-if="item.esDirecto">
                                                <div class="space-y-1.5">
                                                    {{-- Costo (opcional, se puede cuadrar después) --}}
                                                    <div class="relative">
                                                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-medium">$</span>
                                                        <input type="text" inputmode="numeric"
                                                            :value="fmtMiles(item.costoDirecto)"
                                                            @input="applyMiles($event, v => item.costoDirecto = v)"
                                                            placeholder="Costo (opcional)"
                                                            class="w-full pl-6 pr-1 py-1.5 text-xs border border-amber-200 bg-amber-50 rounded-lg focus:ring-2 focus:ring-amber-300 focus:border-amber-400">
                                                    </div>
                                                    {{-- Local externo --}}
                                                    <select
                                                        x-model="item.localExternoId"
                                                        class="w-full py-1.5 px-2 text-xs border rounded-lg focus:ring-2 focus:ring-amber-300"
                                                        :class="!item.localExternoId ? 'border-red-300 bg-red-50 text-red-600' : 'border-amber-300 bg-amber-50'">
                                                        <option value="">— Selecciona local * —</option>
                                                        <template x-for="local in localesExternos" :key="local.id">
                                                            <option :value="local.id" x-text="local.nombre"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </template>
                                            <template x-if="!item.esDirecto">
                                                <span class="text-xs text-gray-300 block text-center">—</span>
                                            </template>
                                        </td>

                                        {{-- Cantidad --}}
                                        <td class="px-3 py-2.5">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" @click="cambiarCantidad(index, -1)"
                                                    class="w-7 h-7 rounded-full bg-gray-200 hover:bg-gray-300 flex items-center justify-center text-gray-600 font-bold transition-colors text-sm">−</button>
                                                <span class="w-8 text-center text-sm font-bold text-gray-900 text-money" x-text="item.cantidad"></span>
                                                <button type="button" @click="cambiarCantidad(index, 1)"
                                                    class="w-7 h-7 rounded-full bg-blue-100 hover:bg-blue-200 flex items-center justify-center text-blue-700 font-bold transition-colors text-sm">+</button>
                                            </div>
                                        </td>

                                        {{-- Subtotal --}}
                                        <td class="px-4 py-2.5 text-right font-extrabold text-gray-900 text-money">
                                            $<span x-text="(item.cantidad * item.precio).toLocaleString('es-CO')"></span>
                                        </td>

                                        {{-- Quitar --}}
                                        <td class="px-2 py-2.5 text-center">
                                            <button type="button" @click="quitarProducto(index)"
                                                class="text-gray-300 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </td>

                                        {{-- Inputs hidden --}}
                                        <input type="hidden" :name="'items[' + index + '][id_detalle_producto]'" :value="item.id">
                                        <input type="hidden" :name="'items[' + index + '][precio_venta]'"        :value="item.precio">
                                        <input type="hidden" :name="'items[' + index + '][cantidad]'"            :value="item.cantidad">
                                        <input type="hidden" :name="'items[' + index + '][es_directo]'"          :value="item.esDirecto ? '1' : '0'">
                                        <input type="hidden" :name="'items[' + index + '][costo_directo]'"       :value="item.costoDirecto || ''">
                                        <input type="hidden" :name="'items[' + index + '][id_local_externo]'"    :value="item.localExternoId || ''">
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
        <div class="max-w-screen-xl mx-auto px-4 py-3 flex items-center justify-between gap-4 flex-wrap">

            {{-- Totales --}}
            <div class="flex items-center gap-6 text-sm min-w-0">
                <div class="hidden sm:flex items-center gap-1 text-gray-500">
                    <span class="font-semibold text-gray-900" x-text="carrito.length"></span>
                    <span class="text-xs">productos ·</span>
                    <span class="font-semibold text-gray-900" x-text="carrito.reduce((s,i) => s + i.cantidad, 0)"></span>
                    <span class="text-xs">uds.</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-gray-400">Total:</span>
                    <span class="font-extrabold text-gray-900 text-money text-lg">$<span x-text="total.toLocaleString('es-CO')"></span></span>
                </div>

                {{-- Efectivo recibido y cambio --}}
                <template x-if="esEfectivoSeleccionado && !esCredito">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-400">Recibido:</span>
                            <div class="relative">
                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">$</span>
                                <input type="text" inputmode="numeric"
                                    :value="fmtMiles(efectivoPagado)"
                                    @input="applyMiles($event, v => efectivoPagado = v)"
                                    class="w-32 pl-5 pr-2 py-1 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-300 text-money">
                            </div>
                        </div>
                        <div x-show="efectivoPagado >= total && total > 0" class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-400">Cambio:</span>
                            <span class="font-bold text-emerald-600 text-money">$<span x-text="cambio.toLocaleString('es-CO')"></span></span>
                        </div>
                    </div>
                </template>

                {{-- Aviso crédito sin cliente --}}
                <template x-if="esCredito && !clienteId">
                    <span class="text-xs text-amber-600 font-medium">⚠ Selecciona un cliente para venta a crédito</span>
                </template>
                {{-- Aviso préstamo sin local --}}
                <template x-if="carrito.some(i => i.esDirecto && !i.localExternoId)">
                    <span class="text-xs text-red-500 font-medium">⚠ Selecciona el local externo del préstamo</span>
                </template>
            </div>

            {{-- Acciones --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('dashboard') }}"
                    class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                    Cancelar
                </a>
                <button type="submit" form="form-venta"
                    @click.prevent="if(prepararFormulario()) document.getElementById('form-venta').submit()"
                    :disabled="carrito.length === 0 || (esCredito && !clienteId) || carrito.some(i => i.esDirecto && !i.localExternoId)"
                    class="bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold px-6 py-2 rounded-xl text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-show="!esCredito">Registrar Venta</span>
                    <span x-show="esCredito">Registrar Crédito</span>
                </button>
            </div>
        </div>
    </div>{{-- /barra fija --}}

    {{-- ══════ MODAL CLIENTE RÁPIDO ══════ --}}
    <div x-show="modalCliente"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0"
         style="display:none">

        {{-- Fondo --}}
        <div class="absolute inset-0 bg-black/50" @click="cerrarModalCliente()"></div>

        {{-- Panel --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Nuevo Cliente</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Registro rápido desde el POS</p>
                </div>
                <button @click="cerrarModalCliente()"
                    class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Formulario --}}
            <div class="px-6 py-5 space-y-4">

                {{-- Documento --}}
                <div class="form-group">
                    <label class="form-label">Documento (Cédula / NIT) *</label>
                    <input type="text" x-model="nuevoCliente.documento"
                        x-ref="inputDocumento"
                        @keydown.enter.prevent="$refs.inputNombre.focus()"
                        class="form-input"
                        :class="erroresCliente.documento ? 'border-red-400 ring-1 ring-red-400' : ''"
                        placeholder="1234567890">
                    <p x-show="erroresCliente.documento" class="form-error" x-text="erroresCliente.documento"></p>
                </div>

                {{-- Nombre --}}
                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" x-model="nuevoCliente.nombre"
                        x-ref="inputNombre"
                        @keydown.enter.prevent="$refs.inputTelefono.focus()"
                        class="form-input"
                        :class="erroresCliente.nombre ? 'border-red-400 ring-1 ring-red-400' : ''"
                        placeholder="Juan Pérez">
                    <p x-show="erroresCliente.nombre" class="form-error" x-text="erroresCliente.nombre"></p>
                </div>

                {{-- Teléfono + Correo en fila --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" x-model="nuevoCliente.telefono"
                            x-ref="inputTelefono"
                            @keydown.enter.prevent="$refs.inputCorreo.focus()"
                            class="form-input" placeholder="3001234567">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo</label>
                        <input type="email" x-model="nuevoCliente.correo"
                            x-ref="inputCorreo"
                            @keydown.enter.prevent="$refs.inputLimite.focus()"
                            class="form-input" placeholder="correo@ejemplo.com">
                    </div>
                </div>

                {{-- Límite de crédito --}}
                <div class="form-group">
                    <label class="form-label">Límite de Crédito</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">$</span>
                        <input type="text" inputmode="numeric"
                            x-ref="inputLimite"
                            :value="fmtMiles(nuevoCliente.limite_credito)"
                            @input="applyMiles($event, v => nuevoCliente.limite_credito = v)"
                            @keydown.enter.prevent="guardarCliente()"
                            class="form-input pl-6" placeholder="0">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">0 o vacío = sin límite asignado</p>
                </div>

                {{-- Error general de validación --}}
                <template x-if="erroresCliente.documento_tomado">
                    <div class="alert alert-error text-sm">
                        <span x-text="erroresCliente.documento_tomado"></span>
                    </div>
                </template>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3">
                <button @click="cerrarModalCliente()" type="button" class="btn btn-secondary">
                    Cancelar
                </button>
                <button @click="guardarCliente()" type="button"
                    :disabled="guardandoCliente"
                    class="btn-primary px-5 py-2 rounded-xl font-bold text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <template x-if="guardandoCliente">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </template>
                    <svg x-show="!guardandoCliente" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="guardandoCliente ? 'Guardando...' : 'Crear y Seleccionar'"></span>
                </button>
            </div>

        </div>{{-- /panel --}}
    </div>{{-- /modal --}}

</div>{{-- /x-data pos --}}

@endsection
