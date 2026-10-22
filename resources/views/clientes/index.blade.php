@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Gestión de Clientes')

@section('content')
<div class="space-y-5" x-data="modalAbono({{ $cuentas->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'tipo' => $c->tipo])->values()->toJson() }})">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-500">Registro y seguimiento de clientes</p>
        @can('clientes.crear')
        <a href="{{ route('clientes.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Nuevo Cliente
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('clientes.index') }}" class="flex flex-wrap gap-3"
                  x-data="{ buscar: @js((string) request('buscar', '')) }">
                <div class="flex-1 min-w-48 relative">
                    <input type="text" name="buscar" x-model="buscar"
                        @keydown.enter.prevent="$el.form.submit()"
                        @input.debounce.400ms="$el.form.submit()"
                        class="form-input pr-8" placeholder="Buscar por nombre, documento, teléfono..." autocomplete="off">
                    <button x-show="buscar.length > 0" type="button"
                        @click="buscar = ''; $nextTick(() => $el.form.submit())"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" name="con_deuda" value="1" {{ request('con_deuda') ? 'checked' : '' }}
                        class="rounded border-gray-300 text-red-500" @change="$el.form.submit()">
                    Solo con deuda pendiente
                </label>
                @if(request()->hasAny(['buscar', 'con_deuda']))
                <a href="{{ route('clientes.index') }}" class="btn-secondary">Limpiar</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
            <x-per-page-selector :current="request('per_page', 25)" />
            @can('clientes.ver')<x-export-btn />@endcan
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Teléfono</th>
                        <th class="text-center">Compras</th>
                        <th class="text-right">Deuda Pendiente</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                    @php $deuda = $cliente->saldo_pendiente_total; @endphp
                    <tr>
                        <td>
                            <div>
                                <p class="font-medium text-gray-900">{{ $cliente->nombre }}</p>
                                @if($cliente->correo)
                                <p class="text-xs text-gray-400">{{ $cliente->correo }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="text-gray-600">{{ $cliente->documento }}</td>
                        <td class="text-gray-600">{{ $cliente->telefono ?? '—' }}</td>
                        <td class="text-center text-gray-600">{{ $cliente->ventas_count }}</td>
                        <td class="text-right">
                            @if($deuda > 0)
                            <span class="font-bold text-red-600 text-money">${{ number_format($deuda, 0, ',', '.') }}</span>
                            @else
                            <span class="text-emerald-600 text-sm">Sin deuda</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-secondary btn-sm">Ver</a>
                                @can('clientes.editar')
                                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-secondary btn-sm">Editar</a>
                                @endcan
                                @can('creditos.gestionar')
                                @if($deuda > 0)
                                <button type="button"
                                    @click="abrir('{{ route('clientes.creditos.json', $cliente) }}', '{{ addslashes($cliente->nombre) }}')"
                                    class="btn btn-warning btn-sm">
                                    Abonar
                                </button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-400">No se encontraron clientes</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-gray-100">
            {{ $clientes->links() }}
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         MODAL DE ABONO
    ══════════════════════════════════════════════════ --}}
    <div x-show="abierto"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0"
         style="display:none">

        {{-- Fondo oscuro --}}
        <div class="absolute inset-0 bg-black/50" @click="cerrar()"></div>

        {{-- Panel del modal --}}
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

            {{-- Cuerpo --}}
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                {{-- Cargando --}}
                <template x-if="cargando">
                    <div class="flex flex-col items-center justify-center py-12 gap-3 text-gray-400">
                        <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span class="text-sm">Cargando créditos...</span>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="!cargando && error">
                    <div class="alert alert-error">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="error"></span>
                    </div>
                </template>

                {{-- Contenido --}}
                <template x-if="!cargando && !error">
                    <div class="space-y-5">

                        {{-- Resumen de deuda total --}}
                        <div class="flex items-center justify-between bg-red-50 rounded-xl px-4 py-3">
                            <span class="text-sm font-medium text-red-700">Deuda total pendiente</span>
                            <span class="text-lg font-extrabold text-red-600 text-money" x-text="'$' + totalDeuda.toLocaleString('es-CO')"></span>
                        </div>

                        {{-- Selector de venta (si hay más de una) --}}
                        <template x-if="creditos.length > 1">
                            <div class="form-group">
                                <label class="form-label">Seleccionar crédito a abonar</label>
                                <div class="space-y-2">
                                    <template x-for="(c, i) in creditos" :key="c.id">
                                        <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors"
                                               :class="creditoSeleccionado === i
                                                   ? 'border-blue-500 bg-blue-50'
                                                   : 'border-gray-200 hover:border-gray-300'">
                                            <input type="radio" :value="i" x-model.number="creditoSeleccionado" class="sr-only">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-900" x-text="c.numero"></span>
                                                    <span class="text-xs text-gray-400" x-text="c.fecha"></span>
                                                    <span class="badge text-xs"
                                                          :class="c.estado === 'Mora' ? 'badge-danger' : 'badge-warning'"
                                                          x-text="c.estado"></span>
                                                </div>
                                                <div class="flex items-center gap-3 mt-0.5">
                                                    <span class="text-xs text-gray-500">
                                                        Vence: <span x-text="c.vencimiento"
                                                                     :class="c.vencido ? 'text-red-600 font-medium' : ''"></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-right flex-shrink-0">
                                                <p class="text-sm font-extrabold text-red-600 text-money" x-text="'$' + c.saldo.toLocaleString('es-CO')"></p>
                                                <p class="text-xs text-gray-400">pendiente</p>
                                            </div>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Info del crédito único --}}
                        <template x-if="creditos.length === 1">
                            <div class="p-3 bg-gray-50 rounded-xl flex items-center justify-between text-sm">
                                <div>
                                    <span class="font-bold text-gray-900" x-text="creditos[0].numero"></span>
                                    <span class="text-gray-400 ml-2" x-text="'·  ' + creditos[0].fecha"></span>
                                    <span class="badge ml-2 text-xs"
                                          :class="creditos[0].estado === 'Mora' ? 'badge-danger' : 'badge-warning'"
                                          x-text="creditos[0].estado"></span>
                                </div>
                                <span class="font-extrabold text-red-600 text-money" x-text="'$' + creditos[0].saldo.toLocaleString('es-CO')"></span>
                            </div>
                        </template>

                        {{-- Sin créditos --}}
                        <template x-if="creditos.length === 0">
                            <div class="text-center py-8 text-gray-400">
                                <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-sm font-medium">Este cliente no tiene créditos pendientes</p>
                            </div>
                        </template>

                        {{-- Formulario de abono (se muestra si hay créditos) --}}
                        <template x-if="creditos.length > 0">
                            <div class="space-y-4 border-t border-gray-100 pt-4">

                                {{-- Monto --}}
                                <div class="form-group">
                                    <label class="form-label">Monto del abono *</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                        <input type="text" inputmode="numeric"
                                            :value="fmtMiles(monto)"
                                            @input="applyMiles($event, v => monto = v)"
                                            class="form-input pl-6 text-lg font-bold text-money"
                                            placeholder="0">
                                    </div>
                                    <template x-if="creditoActual && monto > 0">
                                        <p class="text-xs mt-1"
                                           :class="monto >= creditoActual.saldo ? 'text-emerald-600 font-medium' : 'text-gray-400'">
                                            <template x-if="monto >= creditoActual.saldo">
                                                <span>✓ Cubre la deuda completa</span>
                                            </template>
                                            <template x-if="monto < creditoActual.saldo">
                                                <span>Quedan $<span x-text="(creditoActual.saldo - monto).toLocaleString('es-CO')"></span> pendientes</span>
                                            </template>
                                        </p>
                                    </template>
                                </div>

                                {{-- Botones rápidos de monto --}}
                                <template x-if="creditoActual">
                                    <div class="flex gap-2 flex-wrap">
                                        <button type="button" @click="monto = creditoActual.saldo"
                                            class="px-3 py-1 text-xs font-medium rounded-full border border-emerald-300 text-emerald-700 hover:bg-emerald-50 transition-colors">
                                            Pagar todo ($<span x-text="creditoActual.saldo.toLocaleString('es-CO')"></span>)
                                        </button>
                                        <template x-if="creditoActual.saldo >= 50000">
                                            <button type="button" @click="monto = Math.round(creditoActual.saldo / 2)"
                                                class="px-3 py-1 text-xs font-medium rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                                                50% ($<span x-text="Math.round(creditoActual.saldo / 2).toLocaleString('es-CO')"></span>)
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                {{-- ¿A qué cuenta entra? --}}
                                <div class="form-group">
                                    <label class="form-label">¿A qué cuenta entra? *</label>
                                    <div class="flex gap-2 flex-wrap">
                                        @foreach($cuentas as $cuenta)
                                        <label class="flex-1 cursor-pointer">
                                            <input type="radio" value="{{ $cuenta->id }}" x-model="idCuentaBancaria" class="sr-only peer">
                                            <span class="block text-center text-xs font-medium py-2 rounded-lg border-2 transition-colors cursor-pointer
                                                peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700
                                                border-gray-200 text-gray-500 hover:border-gray-300">
                                                {{ $cuenta->tipo === 'Efectivo' ? '💵' : '📦' }} {{ $cuenta->nombre }}
                                            </span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Observación --}}
                                <div class="form-group">
                                    <label class="form-label">Observación <span class="text-gray-400 font-normal">(opcional)</span></label>
                                    <input type="text" x-model="observacion" class="form-input" placeholder="Ej: pago parcial convenio, transferencia Nequi...">
                                </div>

                            </div>
                        </template>

                    </div>
                </template>

            </div>

            {{-- Footer --}}
            <template x-if="!cargando && !error && creditos.length > 0">
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3">
                    <button @click="cerrar()" type="button"
                        class="btn btn-secondary">
                        Cancelar
                    </button>
                    <button @click="enviar()" type="button"
                        :disabled="enviando || monto <= 0 || !idCuentaBancaria"
                        class="btn-success px-6 py-2 rounded-xl font-bold text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <template x-if="enviando">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                        </template>
                        <span x-text="enviando ? 'Registrando...' : 'Registrar Abono'"></span>
                    </button>
                </div>
            </template>

        </div>{{-- /panel --}}
    </div>{{-- /modal abono --}}

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

</div>

@push('scripts')
<script>
function modalAbono(cuentas) {
    return {
        abierto: false,
        cargando: false,
        enviando: false,
        error: null,
        clienteNombre: '',
        urlCreditos: '',
        totalDeuda: 0,
        creditos: [],
        creditoSeleccionado: 0,
        monto: 0,
        cuentas: cuentas || [],
        idCuentaBancaria: cuentas?.[0] ? String(cuentas[0].id) : '',
        observacion: '',
        necesitaRecargar: false,
        ticketAbierto: false,
        ticketUrl: '',

        get creditoActual() {
            return this.creditos[this.creditoSeleccionado] ?? null;
        },

        async abrir(url, nombre) {
            this.abierto = true;
            this.cargando = true;
            this.error = null;
            this.urlCreditos = url;
            this.clienteNombre = nombre;
            this.creditos = [];
            this.creditoSeleccionado = 0;
            this.monto = 0;
            this.idCuentaBancaria = this.cuentas?.[0] ? String(this.cuentas[0].id) : '';
            this.observacion = '';
            this.necesitaRecargar = false;

            try {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Error al cargar créditos');
                const data = await res.json();
                this.totalDeuda = data.total_deuda;
                this.creditos = data.creditos;
            } catch (e) {
                this.error = 'No se pudieron cargar los créditos. Intenta de nuevo.';
            } finally {
                this.cargando = false;
            }
        },

        cerrar() {
            this.abierto = false;
            if (this.necesitaRecargar) window.location.reload();
        },

        async enviar() {
            const credito = this.creditoActual;
            if (!credito || this.monto <= 0 || !this.idCuentaBancaria) return;

            this.enviando = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const body = new FormData();
                body.append('_token', token);
                body.append('monto', this.monto);
                body.append('id_cuenta_bancaria', this.idCuentaBancaria);
                if (this.observacion) body.append('observacion', this.observacion);

                const res = await fetch(credito.url_abonar, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    const msg = err.message || err.errors?.monto?.[0] || 'Error al registrar el abono.';
                    showToast(msg, 'error');
                    return;
                }

                const data = await res.json();
                if (data.ticket_url) {
                    this.ticketUrl = data.ticket_url + '?embed=1';
                    this.ticketAbierto = true;
                }
                showToast('Abono registrado correctamente.', 'success');

                // Refrescar los créditos en el modal sin cerrar
                this.necesitaRecargar = true;
                this.monto = 0;
                this.observacion = '';
                this.cargando = true;
                try {
                    const r2 = await fetch(this.urlCreditos, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!r2.ok) throw new Error();
                    const d2 = await r2.json();
                    this.totalDeuda = d2.total_deuda;
                    this.creditos = d2.creditos;
                    this.creditoSeleccionado = 0;
                    if (this.creditos.length === 0) {
                        setTimeout(() => this.cerrar(), 1200);
                    }
                } catch {
                    setTimeout(() => window.location.reload(), 500);
                } finally {
                    this.cargando = false;
                }
            } catch (e) {
                showToast('Error de conexión. Intenta de nuevo.', 'error');
            } finally {
                this.enviando = false;
            }
        },
    };
}
</script>
@endpush

@endsection
