@extends('layouts.app')

@section('title', 'Abrir Caja')
@section('page-title', 'Apertura de Caja')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Abrir Caja del Día</h3>
            <a href="{{ route('cuadre-caja.index') }}" class="btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            <div class="p-4 bg-blue-50 rounded-xl mb-6 space-y-1">
                <p class="text-sm text-blue-700 font-medium">
                    {{ now()->format('l, d \d\e F \d\e Y') }}
                </p>
                @if($sucursal)
                <p class="text-sm text-blue-800 font-bold">
                    Local: {{ $sucursal->nombre }}
                </p>
                @endif
                <p class="text-xs text-blue-500">Esta caja quedará activa para todos los vendedores del local hasta que se cierre.</p>
            </div>

            <form method="POST" action="{{ route('cuadre-caja.store') }}" class="space-y-5">
                @csrf

                <div class="form-group">
                    <label class="form-label">Saldo Inicial en Caja *</label>
                    <p class="text-xs text-gray-400 mb-2">Ingresa el efectivo con que inicias el turno</p>
                    <div class="relative" x-data="moneyInput({{ old('saldo_inicial', 0) }})">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                        <input type="text" inputmode="numeric" x-ref="display" @input="update($event)"
                            class="form-input pl-6 text-xl text-money font-bold" required placeholder="0">
                        <input type="hidden" name="saldo_inicial" :value="raw">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" rows="2" class="form-textarea" placeholder="Notas de apertura (opcional)">{{ old('observaciones') }}</textarea>
                </div>

                <button type="submit" class="btn-success w-full py-3 text-base font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Abrir Caja Ahora
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
