@extends('layouts.app')

@section('title', $cliente->nombre)
@section('page-title', 'Cliente: ' . $cliente->nombre)

@section('content')
<div class="space-y-5 max-w-5xl mx-auto" x-data="modalAbono({{ $cuentas->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'tipo' => $c->tipo])->values()->toJson() }})">

    <div class="flex flex-wrap gap-3">
        @can('clientes.editar')
        <a href="{{ route('clientes.edit', $cliente) }}" class="btn-secondary">Editar</a>
        @endcan
        <a href="{{ route('clientes.estado-cuenta', $cliente) }}" target="_blank" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Estado de Cuenta
        </a>
        <a href="{{ route('clientes.index') }}" class="btn-secondary">← Volver</a>
    </div>

    <div class="grid md:grid-cols-3 gap-5">
        {{-- Datos del cliente --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Datos Personales</h3></div>
            <div class="card-body space-y-3 text-sm">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-blue-500 flex items-center justify-center text-white text-2xl font-bold">
                        {{ strtoupper(substr($cliente->nombre, 0, 1)) }}
                    </div>
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Documento</span><span class="font-mono">{{ $cliente->documento }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Nombre</span><span class="font-medium">{{ $cliente->nombre }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Teléfono</span><span>{{ $cliente->telefono ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Correo</span><span class="text-xs">{{ $cliente->correo ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Dirección</span><span class="text-xs text-right max-w-32">{{ $cliente->direccion ?? '—' }}</span></div>
                <div class="flex justify-between border-t pt-3"><span class="text-gray-500">Registrado</span><span>{{ $cliente->created_at->format('d/m/Y') }}</span></div>
            </div>
        </div>

        {{-- Resumen de crédito --}}
        <div class="card md:col-span-2">
            <div class="card-header"><h3 class="card-title">Resumen Financiero</h3></div>
            <div class="card-body">
                <div class="grid grid-cols-3 gap-4 mb-5">
                    <div class="text-center p-3 bg-blue-50 rounded-xl">
                        <p class="text-2xl font-extrabold text-blue-700 text-money">{{ $cliente->ventas->count() }}</p>
                        <p class="text-xs text-gray-500 mt-1">Compras totales</p>
                    </div>
                    <div class="text-center p-3 bg-emerald-50 rounded-xl">
                        <p class="text-lg font-extrabold text-emerald-700 text-money">${{ number_format($cliente->ventas->sum('total'), 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total comprado</p>
                    </div>
                    <div class="text-center p-3 {{ $saldoPendiente > 0 ? 'bg-red-50' : 'bg-gray-50' }} rounded-xl">
                        <p class="text-lg font-extrabold {{ $saldoPendiente > 0 ? 'text-red-600' : 'text-gray-500' }} text-money">${{ number_format($saldoPendiente, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 mt-1">Saldo pendiente</p>
                    </div>
                </div>

                @if($cliente->limite_credito > 0)
                <div class="p-3 bg-amber-50 rounded-lg text-sm">
                    <div class="flex justify-between mb-1">
                        <span class="text-amber-700 font-medium">Límite de crédito</span>
                        <span class="text-money font-bold">${{ number_format($cliente->limite_credito, 0, ',', '.') }}</span>
                    </div>
                    @php $usadoPct = $cliente->limite_credito > 0 ? min(100, ($saldoPendiente / $cliente->limite_credito) * 100) : 0; @endphp
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $usadoPct >= 90 ? 'bg-red-500' : ($usadoPct >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $usadoPct }}%"></div>
                    </div>
                    <p class="text-xs text-amber-600 mt-1">{{ number_format($usadoPct, 0) }}% utilizado</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Ventas a crédito pendientes --}}
    @if($ventasCredito->count())
    <div class="card" id="creditos">
        <div class="card-header">
            <h3 class="card-title text-amber-700">Créditos Pendientes</h3>
            <span class="badge badge-warning">{{ $ventasCredito->count() }}</span>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Venta</th>
                        <th>Fecha</th>
                        <th>Vencimiento</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Saldo</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ventasCredito as $venta)
                    <tr>
                        <td><a href="{{ route('ventas.show', $venta) }}" class="text-blue-600 font-medium hover:underline">#{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}</a></td>
                        <td class="text-sm text-gray-600">{{ $venta->created_at->format('d/m/Y') }}</td>
                        <td class="text-sm {{ $venta->fecha_vencimiento < now() && $venta->saldo_pendiente > 0 ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                            {{ $venta->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="text-right text-money">${{ number_format($venta->total, 0, ',', '.') }}</td>
                        <td class="text-right font-bold text-red-600 text-money">${{ number_format($venta->saldo_pendiente, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge {{ $venta->estado_credito === 'Mora' ? 'badge-danger' : 'badge-warning' }}">{{ $venta->estado_credito }}</span>
                        </td>
                        <td>
                            @can('creditos.gestionar')
                            <button type="button"
                                @click="abrir('{{ route('clientes.creditos.json', $cliente) }}', '{{ addslashes($cliente->nombre) }}', '{{ route('clientes.abonar-mixto', $cliente) }}')"
                                class="btn btn-success btn-sm">
                                Abonar
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Historial de compras --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Historial de Compras</h3>
            <span class="text-sm text-gray-400">{{ $cliente->ventas->count() }} transacciones</span>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $venta)
                    <tr>
                        <td><a href="{{ route('ventas.show', $venta) }}" class="text-blue-600 font-medium hover:underline">#{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}</a></td>
                        <td class="text-sm text-gray-600">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                        <td><span class="badge badge-info">{{ $venta->metodo_pago }}</span></td>
                        <td class="text-right font-bold text-money">${{ number_format($venta->total, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if(!$venta->es_credito || $venta->estado_credito === 'Pagado')
                                <span class="badge badge-success">Pagado</span>
                            @elseif($venta->estado_credito === 'Mora')
                                <span class="badge badge-danger">Mora</span>
                            @else
                                <span class="badge badge-warning">Parcial</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-1 justify-end">
                                <a href="{{ route('ventas.show', $venta) }}" class="btn btn-secondary btn-sm">Ver</a>
                                <a href="{{ route('ventas.ticket', $venta) }}" class="btn btn-secondary btn-sm">Ticket</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-6 text-gray-400">Sin compras registradas</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ventas instanceof \Illuminate\Pagination\AbstractPaginator && $ventas->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $ventas->links() }}</div>
        @endif
    </div>


    {{-- ══════ MODAL TICKET DE ABONO ══════ --}}
    <div x-show="ticketAbierto"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0"
         style="display:none">
        <div class="absolute inset-0 bg-black/60" @click="ticketAbierto = false; ticketUrl = ''"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl flex flex-col w-full max-w-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-end="opacity-0 scale-95">
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 flex-shrink-0">
                <h3 class="font-bold text-gray-800">Comprobante de Abono</h3>
                <div class="flex items-center gap-2">
                    <button @click="$refs.ticketFrame.contentWindow.print()"
                        class="btn-primary flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Imprimir
                    </button>
                    <button @click="ticketAbierto = false; ticketUrl = ''"
                        class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="overflow-hidden rounded-b-2xl">
                <iframe x-ref="ticketFrame" :src="ticketUrl || 'about:blank'"
                    class="w-full border-0" style="height:520px;"></iframe>
            </div>
        </div>
    </div>

    {{-- ══════ MODAL DE ABONO ══════ --}}
    <div x-show="abierto"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0"
         style="display:none">
        <div class="absolute inset-0 bg-black/50" @click="cerrar()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Registrar Abono</h2>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="clienteNombre"></p>
                </div>
                <button @click="cerrar()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                {{-- Cargando --}}
                <template x-if="cargando">
                    <div class="flex flex-col items-center justify-center py-12 gap-3 text-gray-400">
                        <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span class="text-sm">Cargando créditos...</span>
                    </div>
                </template>

                <template x-if="!cargando && error">
                    <div class="alert alert-error"><span x-text="error"></span></div>
                </template>

                <template x-if="!cargando && !error && creditos.length === 0">
                    <div class="text-center py-8 text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm font-medium">Este cliente no tiene créditos pendientes</p>
                    </div>
                </template>

                <template x-if="!cargando && !error && creditos.length > 0">
                    <div class="space-y-5">

                        {{-- Deuda total --}}
                        <div class="flex items-center justify-between bg-red-50 rounded-xl px-4 py-3">
                            <span class="text-sm font-medium text-red-700">Deuda total pendiente</span>
                            <span class="text-lg font-extrabold text-red-600 text-money" x-text="'$' + totalDeuda.toLocaleString('es-CO')"></span>
                        </div>

                        {{-- Toggle modo (solo si hay más de 1 crédito) --}}
                        <template x-if="creditos.length > 1">
                            <div class="flex rounded-xl border border-gray-200 overflow-hidden text-sm font-semibold">
                                <button type="button" @click="modo = 'venta'"
                                    class="flex-1 py-2 transition-colors"
                                    :class="modo === 'venta' ? 'bg-blue-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                                    Por venta
                                </button>
                                <button type="button" @click="modo = 'global'"
                                    class="flex-1 py-2 transition-colors"
                                    :class="modo === 'global' ? 'bg-blue-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                                    Abono global
                                </button>
                            </div>
                        </template>

                        {{-- ── MODO: POR VENTA ── --}}
                        <template x-if="modo === 'venta'">
                            <div class="space-y-4">
                                <template x-if="creditos.length > 1">
                                    <div class="space-y-2">
                                        <label class="form-label">Seleccionar crédito</label>
                                        <template x-for="(c, i) in creditos" :key="c.id">
                                            <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors"
                                                   :class="creditoSeleccionado === i ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                                                <input type="radio" :value="i" x-model.number="creditoSeleccionado" class="sr-only">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="text-sm font-bold text-gray-900" x-text="c.numero"></span>
                                                        <span class="text-xs text-gray-400" x-text="c.fecha"></span>
                                                        <span class="badge text-xs" :class="c.estado === 'Mora' ? 'badge-danger' : 'badge-warning'" x-text="c.estado"></span>
                                                    </div>
                                                    <span class="text-xs text-gray-500">Vence: <span x-text="c.vencimiento" :class="c.vencido ? 'text-red-600 font-medium' : ''"></span></span>
                                                </div>
                                                <div class="text-right flex-shrink-0">
                                                    <p class="text-sm font-extrabold text-red-600 text-money" x-text="'$' + c.saldo.toLocaleString('es-CO')"></p>
                                                    <p class="text-xs text-gray-400">pendiente</p>
                                                </div>
                                            </label>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="creditos.length === 1">
                                    <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between text-sm">
                                        <div>
                                            <span class="font-bold text-gray-900" x-text="creditos[0].numero"></span>
                                            <span class="text-gray-400 ml-2" x-text="'· ' + creditos[0].fecha"></span>
                                        </div>
                                        <span class="font-extrabold text-red-600 text-money" x-text="'$' + creditos[0].saldo.toLocaleString('es-CO')"></span>
                                    </div>
                                </template>

                                {{-- Monto --}}
                                <div class="form-group">
                                    <label class="form-label">Monto *</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                        <input type="text" inputmode="numeric"
                                            :value="fmtMiles(monto)"
                                            @input="applyMiles($event, v => monto = v)"
                                            class="form-input pl-6 text-lg font-bold text-money" placeholder="0">
                                    </div>
                                    <template x-if="creditoActual && monto > 0">
                                        <p class="text-xs mt-1" :class="monto >= creditoActual.saldo ? 'text-emerald-600 font-medium' : 'text-gray-400'">
                                            <template x-if="monto >= creditoActual.saldo"><span>✓ Cubre la deuda completa</span></template>
                                            <template x-if="monto < creditoActual.saldo"><span>Quedan $<span x-text="(creditoActual.saldo - monto).toLocaleString('es-CO')"></span></span></template>
                                        </p>
                                    </template>
                                </div>
                                <template x-if="creditoActual">
                                    <div class="flex gap-2 flex-wrap">
                                        <button type="button" @click="monto = creditoActual.saldo"
                                            class="px-3 py-1 text-xs font-medium rounded-full border border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                                            Pagar todo ($<span x-text="creditoActual.saldo.toLocaleString('es-CO')"></span>)
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- ── MODO: ABONO GLOBAL ── --}}
                        <template x-if="modo === 'global'">
                            <div class="space-y-4">
                                <div class="form-group">
                                    <label class="form-label">Monto total a pagar *</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                        <input type="text" inputmode="numeric"
                                            :value="fmtMiles(montoGlobal)"
                                            @input="applyMiles($event, v => montoGlobal = v)"
                                            class="form-input pl-6 text-lg font-bold text-money" placeholder="0">
                                    </div>
                                    <div class="flex gap-2 mt-2 flex-wrap">
                                        <button type="button" @click="montoGlobal = totalDeuda"
                                            class="px-3 py-1 text-xs font-medium rounded-full border border-emerald-300 text-emerald-700 hover:bg-emerald-50">
                                            Pagar todo ($<span x-text="totalDeuda.toLocaleString('es-CO')"></span>)
                                        </button>
                                        <button type="button" @click="montoGlobal = Math.round(totalDeuda / 2)"
                                            class="px-3 py-1 text-xs font-medium rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50">
                                            50% ($<span x-text="Math.round(totalDeuda / 2).toLocaleString('es-CO')"></span>)
                                        </button>
                                    </div>
                                </div>

                                {{-- Vista previa de distribución --}}
                                <template x-if="montoGlobal > 0">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500 mb-2">Distribución (más antiguo primero)</p>
                                        <div class="space-y-1.5">
                                            <template x-for="d in distribucion" :key="d.id">
                                                <div class="flex items-center justify-between px-3 py-2 rounded-lg text-xs"
                                                     :class="d.pagado ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200'">
                                                    <div class="flex items-center gap-2">
                                                        <span x-show="d.pagado" class="text-emerald-600 font-bold">✓</span>
                                                        <span x-show="!d.pagado" class="text-amber-600 font-bold">~</span>
                                                        <span class="font-semibold text-gray-700" x-text="d.numero"></span>
                                                        <span class="text-gray-400" x-text="d.fecha"></span>
                                                    </div>
                                                    <div class="text-right">
                                                        <span class="font-bold" :class="d.pagado ? 'text-emerald-600' : 'text-amber-700'"
                                                              x-text="'- $' + d.abono.toLocaleString('es-CO')"></span>
                                                        <span x-show="!d.pagado" class="text-gray-400 ml-1"
                                                              x-text="'(queda $' + d.saldoFinal.toLocaleString('es-CO') + ')'"></span>
                                                        <span x-show="d.pagado" class="text-emerald-500 ml-1">Saldada</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <template x-if="montoGlobal > totalDeuda">
                                            <p class="text-xs text-red-500 mt-2 font-medium">⚠ El monto supera la deuda total</p>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Método y observación (comunes) --}}
                        <div class="space-y-3 border-t border-gray-100 pt-4">
                            <div class="form-group">
                                <label class="form-label">¿A qué cuenta entra? *</label>
                                <div class="flex gap-2 flex-wrap">
                                    @foreach($cuentas as $cuenta)
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" value="{{ $cuenta->id }}" x-model="idCuentaBancaria" class="sr-only peer">
                                        <span class="block text-center text-xs font-medium py-2 rounded-lg border-2 transition-colors cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 text-gray-500 hover:border-gray-300">{{ $cuenta->tipo === 'Efectivo' ? '💵' : '📦' }} {{ $cuenta->nombre }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Observación <span class="text-gray-400 font-normal">(opcional)</span></label>
                                <input type="text" x-model="observacion" class="form-input" placeholder="Ej: transferencia Nequi, pago parcial...">
                            </div>
                        </div>

                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <template x-if="!cargando && !error && creditos.length > 0">
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3">
                    <button @click="cerrar()" type="button" class="btn btn-secondary">Cancelar</button>
                    <button @click="enviar()" type="button"
                        :disabled="enviando || (modo === 'venta' ? monto <= 0 : montoGlobal <= 0 || montoGlobal > totalDeuda) || !idCuentaBancaria"
                        class="btn-success px-6 py-2 rounded-xl font-bold text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <template x-if="enviando">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </template>
                        <span x-text="enviando ? 'Registrando...' : 'Registrar Abono'"></span>
                    </button>
                </div>
            </template>

        </div>
    </div>

</div>

@push('scripts')
<script>
function modalAbono(cuentas) {
    return {
        abierto: false, cargando: false, enviando: false, error: null,
        clienteNombre: '', urlCreditos: '', urlAbonoMixto: '',
        totalDeuda: 0, creditos: [],
        creditoSeleccionado: 0, monto: 0, montoGlobal: 0,
        cuentas: cuentas || [],
        idCuentaBancaria: cuentas?.[0] ? String(cuentas[0].id) : '',
        observacion: '',
        modo: 'venta',
        necesitaRecargar: false, ticketAbierto: false, ticketUrl: '',

        get creditoActual() { return this.creditos[this.creditoSeleccionado] ?? null; },

        get distribucion() {
            if (this.montoGlobal <= 0) return [];
            let restante = this.montoGlobal;
            const result = [];
            for (const c of this.creditos) {
                if (restante <= 0) break;
                const abono = Math.min(restante, c.saldo);
                result.push({ ...c, abono, saldoFinal: Math.round(c.saldo - abono), pagado: abono >= c.saldo });
                restante -= abono;
            }
            return result;
        },

        async abrir(url, nombre, urlMixto) {
            this.abierto = true; this.cargando = true; this.error = null;
            this.urlCreditos = url; this.clienteNombre = nombre;
            this.urlAbonoMixto = urlMixto; this.creditos = [];
            this.creditoSeleccionado = 0; this.monto = 0; this.montoGlobal = 0;
            this.idCuentaBancaria = this.cuentas?.[0] ? String(this.cuentas[0].id) : '';
            this.observacion = '';
            this.necesitaRecargar = false; this.modo = 'venta';
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.totalDeuda = data.total_deuda; this.creditos = data.creditos;
            } catch { this.error = 'No se pudieron cargar los créditos. Intenta de nuevo.'; }
            finally { this.cargando = false; }
        },

        cerrar() {
            this.abierto = false;
            if (this.necesitaRecargar) window.location.reload();
        },

        async enviar() {
            if (this.modo === 'global') {
                await this.enviarMixto();
            } else {
                await this.enviarPorVenta();
            }
        },

        async enviarPorVenta() {
            const credito = this.creditoActual;
            if (!credito || this.monto <= 0 || !this.idCuentaBancaria) return;
            this.enviando = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const body = new FormData();
                body.append('_token', token); body.append('monto', this.monto);
                body.append('id_cuenta_bancaria', this.idCuentaBancaria);
                if (this.observacion) body.append('observacion', this.observacion);
                const res = await fetch(credito.url_abonar, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    showToast(err.message || err.errors?.monto?.[0] || 'Error al registrar el abono.', 'error'); return;
                }
                const data = await res.json();
                if (data.ticket_url) { this.ticketUrl = data.ticket_url + '?embed=1'; this.ticketAbierto = true; }
                showToast('Abono registrado correctamente.', 'success');
                await this.refrescarCreditos();
                this.monto = 0;
            } catch { showToast('Error de conexión. Intenta de nuevo.', 'error'); }
            finally { this.enviando = false; }
        },

        async enviarMixto() {
            if (this.montoGlobal <= 0 || this.montoGlobal > this.totalDeuda || !this.idCuentaBancaria) return;
            this.enviando = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const body = new FormData();
                body.append('_token', token); body.append('monto_total', this.montoGlobal);
                body.append('id_cuenta_bancaria', this.idCuentaBancaria);
                if (this.observacion) body.append('observacion', this.observacion);
                const res = await fetch(this.urlAbonoMixto, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    showToast(err.message || 'Error al registrar el abono.', 'error'); return;
                }
                const data = await res.json();
                if (data.ticket_url) {
                    this.ticketUrl = data.ticket_url + '&embed=1';
                    this.ticketAbierto = true;
                }
                showToast(data.message, 'success');
                await this.refrescarCreditos();
                this.montoGlobal = 0;
            } catch { showToast('Error de conexión. Intenta de nuevo.', 'error'); }
            finally { this.enviando = false; }
        },

        async refrescarCreditos() {
            this.necesitaRecargar = true;
            this.observacion = ''; this.cargando = true;
            try {
                const r = await fetch(this.urlCreditos, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!r.ok) throw new Error();
                const d = await r.json();
                this.totalDeuda = d.total_deuda; this.creditos = d.creditos; this.creditoSeleccionado = 0;
                if (this.creditos.length === 0) setTimeout(() => this.cerrar(), 1200);
            } catch { setTimeout(() => window.location.reload(), 500); }
            finally { this.cargando = false; }
        },
    };
}
</script>
@endpush

@endsection
