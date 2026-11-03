{{-- Saldo a favor del cliente: valor vigente, reembolso en dinero e historial (clientes/show) --}}
@if($cliente->saldo_favor > 0 || $movimientosSaldo->isNotEmpty())
<div class="card" x-data="{ reembolsar: false }">
    <div class="card-header">
        <h3 class="card-title text-emerald-700">Saldo a favor: <span class="text-money">${{ number_format($cliente->saldo_favor, 0, ',', '.') }}</span></h3>
        @can('creditos.gestionar')
        @if($cliente->saldo_favor > 0)
        <button type="button" @click="reembolsar = true" class="btn-secondary btn-sm">Devolver en dinero</button>
        @endif
        @endcan
    </div>
    <div class="px-5 py-2 text-xs text-gray-500 border-b border-gray-50">
        Se usa en el POS como forma de pago o al abonar a sus créditos.
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Movimiento</th>
                    <th>Detalle</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientosSaldo as $m)
                <tr>
                    <td class="text-sm text-gray-600">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                    <td><span class="badge {{ $m->monto > 0 ? 'badge-success' : 'badge-info' }}">{{ $m->tipo }}</span></td>
                    <td class="text-sm text-gray-600">
                        @if($m->id_venta)
                            <a href="{{ route('ventas.show', $m->id_venta) }}" class="text-blue-600 hover:underline">{{ $m->observacion }}</a>
                        @else
                            {{ $m->observacion }}
                        @endif
                        <span class="text-xs text-gray-400">· {{ $m->usuario?->nombre ?? '—' }}</span>
                    </td>
                    <td class="text-right text-money font-semibold {{ $m->monto > 0 ? 'text-emerald-600' : 'text-gray-700' }}">{{ $m->monto > 0 ? '+' : '-' }}${{ number_format(abs($m->monto), 0, ',', '.') }}</td>
                    <td class="text-right text-money text-gray-500">${{ number_format($m->saldo_resultante, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-4 text-gray-400">Sin movimientos</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('creditos.gestionar')
    @if($cliente->saldo_favor > 0)
    <div x-show="reembolsar" x-cloak class="modal-overlay" x-transition>
        <div class="modal-container" @click.stop>
            <div class="modal-header">
                <h3 class="text-base font-bold">Devolver saldo a favor en dinero</h3>
                <button type="button" @click="reembolsar = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('clientes.reembolsar-saldo', $cliente) }}">
                @csrf
                <div class="modal-body space-y-4">
                    <div class="p-3 bg-emerald-50 rounded-lg text-sm text-emerald-700 font-medium">
                        Saldo disponible: <span class="text-money">${{ number_format($cliente->saldo_favor, 0, ',', '.') }}</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Monto a devolver *</label>
                        <div class="relative" x-data="moneyInput(@js((int) floor($cliente->saldo_favor)))">
                            <input type="text" inputmode="numeric" x-ref="display" @input="update($event)"
                                class="form-input" required placeholder="0">
                            <input type="hidden" name="monto" :value="raw">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">¿De dónde sale el dinero? *</label>
                        <select name="id_cuenta_bancaria" class="form-select" required>
                            @foreach($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}">{{ $cuenta->tipo === 'Efectivo' ? '💵' : '📦' }} {{ $cuenta->nombre }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Si sale del cajón, el cuadre de caja de hoy lo descuenta.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Observación</label>
                        <input type="text" name="observacion" maxlength="200" class="form-input" placeholder="Opcional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="reembolsar = false" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Registrar reembolso</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    @endcan
</div>
@endif
