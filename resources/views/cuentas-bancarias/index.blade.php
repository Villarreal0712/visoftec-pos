@extends('layouts.app')

@section('title', 'Cuentas Bancarias')
@section('page-title', 'Cuentas Bancarias')

@section('content')
<div class="max-w-4xl mx-auto space-y-5" x-data="{ modalNueva: false }">

    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Efectivo lo maneja el Cuadre de Caja. Aquí llevas el saldo de tus cuentas bancarias/digitales.</p>
        @can('cuentas.gestionar')
        <button @click="modalNueva = true" class="btn-primary btn-sm flex-shrink-0">+ Nueva cuenta</button>
        @endcan
    </div>

    {{-- Mensajes flash --}}
    @if(session('success'))
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Total en bancos --}}
    <div class="card">
        <div class="card-body">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Total en cuentas bancarias</p>
            <p class="text-2xl font-bold text-emerald-700 text-money">$ {{ number_format($totalBancos, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Listado --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cuenta</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($cuentas as $cuenta)
                        <tr class="hover:bg-gray-50 transition-colors {{ !$cuenta->activo ? 'opacity-50' : '' }}">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 text-sm">{{ $cuenta->nombre }}</p>
                                @if($cuenta->banco)
                                <p class="text-xs text-gray-400">{{ $cuenta->banco }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($cuenta->tipo === 'Efectivo')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">💵 Efectivo</span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">📦 Banco</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono tabular-nums font-semibold text-money">
                                @if($cuenta->tipo === 'Efectivo')
                                <span class="text-gray-400 italic text-sm">Ver Cuadre de Caja</span>
                                @else
                                $ {{ number_format($cuenta->saldo_actual, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if(!$cuenta->activo)
                                <span class="text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded">Inactiva</span>
                                @else
                                <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">Activa</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if($cuenta->tipo === 'Banco')
                                    <a href="{{ route('cuentas-bancarias.show', $cuenta) }}" class="btn-secondary btn-sm">Ver</a>
                                    @can('cuentas.gestionar')
                                    <form method="POST" action="{{ route('cuentas-bancarias.toggle', $cuenta) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-secondary btn-sm" title="{{ $cuenta->activo ? 'Desactivar' : 'Activar' }}">
                                            {{ $cuenta->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-8 text-gray-400">No hay cuentas registradas</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal nueva cuenta --}}
    @can('cuentas.gestionar')
    <div x-show="modalNueva" x-cloak class="modal-overlay" x-transition style="display:none">
        <div class="modal-container max-w-sm" @click.stop>
            <div class="modal-header">
                <h3 class="card-title">Nueva cuenta bancaria</h3>
                <button @click="modalNueva = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <form method="POST" action="{{ route('cuentas-bancarias.store') }}">
                @csrf
                <div class="modal-body space-y-3">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-input" placeholder="Ej: Nequi Negocio" required autofocus>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Banco (opcional)</label>
                        <input type="text" name="banco" class="form-input" placeholder="Ej: Bancolombia">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="modalNueva = false" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear cuenta</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

</div>
@endsection
