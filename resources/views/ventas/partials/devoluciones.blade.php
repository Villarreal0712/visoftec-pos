{{-- Devoluciones de la venta: historial + modal para registrar una nueva (ventas/show) --}}

@if($venta->devoluciones->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Devoluciones</h3>
        <span class="badge badge-warning">{{ $venta->devoluciones->count() }}</span>
    </div>
    <div class="card-body space-y-3">
        @foreach($venta->devoluciones as $dev)
        <div class="border border-gray-100 rounded-xl p-3 text-sm">
            <div class="flex justify-between items-start gap-3">
                <div>
                    <p class="font-semibold">-${{ number_format($dev->total, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">{{ $dev->created_at->format('d/m/Y H:i') }} · {{ $dev->usuario?->nombre ?? '—' }}</p>
                    @if($dev->motivo)<p class="text-xs text-gray-500 italic">{{ $dev->motivo }}</p>@endif
                </div>
                <div class="text-right text-xs space-y-0.5">
                    @if($dev->monto_deuda > 0)<p class="text-gray-600">Descontado de la deuda: <span class="text-money font-medium">${{ number_format($dev->monto_deuda, 0, ',', '.') }}</span></p>@endif
                    @if($dev->monto_saldo_favor > 0)<p class="text-emerald-600">Saldo a favor: <span class="text-money font-medium">${{ number_format($dev->monto_saldo_favor, 0, ',', '.') }}</span></p>@endif
                    @if($dev->monto_reembolso > 0)<p class="text-red-600">Reembolsado{{ $dev->reembolso?->cuentaBancaria ? ' desde ' . $dev->reembolso->cuentaBancaria->nombre : ' en efectivo' }}: <span class="text-money font-medium">${{ number_format($dev->monto_reembolso, 0, ',', '.') }}</span></p>@endif
                </div>
            </div>
            <ul class="mt-2 text-xs text-gray-600 list-disc pl-5">
                @foreach($dev->detalles as $d)
                @php $det = $d->historialVenta->lote->detalleProducto; @endphp
                <li>{{ $det->producto->nombre }}{{ $det->talla ? ' (' . $det->talla . ')' : '' }} × {{ $d->cantidad }} — ${{ number_format($d->subtotal, 0, ',', '.') }}</li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>
</div>
@endif

@can('ventas.anular')
@if(!$venta->anulada && $venta->historialVentas->sum('cantidad_neta') > 0)
@php
    $lineasDevolucion = $venta->historialVentas->filter(fn($i) => $i->cantidad_neta > 0)->map(fn($i) => [
        'id'       => $i->id,
        'nombre'   => $i->lote->detalleProducto->producto->nombre . ($i->lote->detalleProducto->talla ? ' (' . $i->lote->detalleProducto->talla . ')' : ''),
        'prestado' => $i->lote->tipo_origen === 'Directo',
        'max'      => $i->cantidad_neta,
        'precio'   => (float) $i->precio_venta,
        'cantidad' => 0,
    ])->values();
@endphp
<div x-data="{
        open: false,
        lineas: @js($lineasDevolucion),
        destino: @js($venta->id_cliente ? 'saldo' : 'reembolso'),
        deudaPendiente: @js($venta->es_credito ? (float) $venta->saldo_pendiente : 0),
        get total() { return this.lineas.reduce((s, l) => s + l.cantidad * l.precio, 0); },
        get aDeuda() { return Math.min(this.total, this.deudaPendiente); },
        get excedente() { return this.total - this.aDeuda; },
        fmt(v) { return '$' + Math.round(v).toLocaleString('es-CO'); },
     }"
     @open-modal.window="if ($event.detail === 'modal-devolucion') open = true"
     x-show="open" x-cloak class="modal-overlay" x-transition>
    <div class="modal-container max-w-lg" @click.stop>
        <div class="modal-header">
            <h3 class="text-base font-bold">Devolver ítems de la venta #{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}</h3>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('ventas.devolucion', $venta) }}"
              @submit="if (total <= 0) { $event.preventDefault(); showToast('Indica cuántas unidades devuelve el cliente', 'warning'); }">
            @csrf
            <div class="modal-body space-y-4">
                <div class="space-y-2">
                    <label class="form-label">¿Qué devuelve el cliente?</label>
                    <template x-for="(l, i) in lineas" :key="l.id">
                        <div class="flex items-center justify-between gap-3 p-2 rounded-lg border border-gray-100">
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate" x-text="l.nombre"></p>
                                <p class="text-xs text-gray-400">
                                    <span x-text="fmt(l.precio)"></span> c/u · puede devolver <span x-text="l.max"></span>
                                    <span x-show="l.prestado" class="text-orange-500"> · préstamo: vuelve al local externo</span>
                                </p>
                            </div>
                            <input type="hidden" :name="'items[' + i + '][id_historial_venta]'" :value="l.id">
                            <input type="number" :name="'items[' + i + '][cantidad]'" x-model.number="l.cantidad"
                                   min="0" :max="l.max" step="1" class="form-input w-20 text-center">
                        </div>
                    </template>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg text-sm space-y-1" x-show="total > 0">
                    <div class="flex justify-between font-semibold"><span>Valor devuelto</span><span class="text-money" x-text="fmt(total)"></span></div>
                    <div class="flex justify-between text-gray-600" x-show="aDeuda > 0"><span>Se descuenta de lo que debe</span><span class="text-money" x-text="'-' + fmt(aDeuda)"></span></div>
                    <div class="flex justify-between text-gray-600" x-show="excedente > 0"><span>Ya lo había pagado</span><span class="text-money" x-text="fmt(excedente)"></span></div>
                </div>

                <div class="form-group" x-show="excedente > 0">
                    <label class="form-label">¿Qué se hace con lo que ya había pagado?</label>
                    <div class="flex gap-2">
                        <label class="flex-1 {{ $venta->id_cliente ? 'cursor-pointer' : 'opacity-40 cursor-not-allowed' }}">
                            <input type="radio" name="destino" value="saldo" x-model="destino" class="sr-only peer" @disabled(!$venta->id_cliente)>
                            <span class="block text-center text-sm font-medium py-2 rounded-lg border-2 border-gray-200 text-gray-500 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700">⭐ Saldo a favor</span>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="destino" value="reembolso" x-model="destino" class="sr-only peer">
                            <span class="block text-center text-sm font-medium py-2 rounded-lg border-2 border-gray-200 text-gray-500 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700">💵 Devolver el dinero</span>
                        </label>
                    </div>
                    @unless($venta->id_cliente)
                    <p class="text-xs text-gray-400 mt-1">Esta venta no tiene cliente: no se le puede dejar saldo a favor.</p>
                    @endunless
                </div>

                <div class="form-group" x-show="excedente > 0 && destino === 'reembolso'">
                    <label class="form-label">¿De dónde sale el dinero?</label>
                    <select name="id_cuenta_bancaria" class="form-select" :disabled="!(excedente > 0 && destino === 'reembolso')">
                        @foreach($cuentas as $cuenta)
                        <option value="{{ $cuenta->id }}">{{ $cuenta->tipo === 'Efectivo' ? '💵' : '📦' }} {{ $cuenta->nombre }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Si sale del cajón, el cuadre de caja de hoy lo descuenta.</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo</label>
                    <input type="text" name="motivo" maxlength="255" class="form-input" placeholder="Ej: talla equivocada (opcional)">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" @click="open = false" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary" :disabled="total <= 0">Registrar devolución</button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan
