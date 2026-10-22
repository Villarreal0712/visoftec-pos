@extends('layouts.app')

@section('title', 'Cuadre de Caja')
@section('page-title', 'Cuadre de Caja')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-500">Registro de apertura y cierre de caja diario</p>
        @can('caja.abrir')
        @php
            $idSucursalUsuario = auth()->user()->id_sucursal;
            $cajaAbiertaLocal = $idSucursalUsuario
                ? \App\Models\CuadreCaja::abiertos()->where('id_sucursal', $idSucursalUsuario)->first()
                : null;
        @endphp
        @if($cajaAbiertaLocal)
        <a href="{{ route('cuadre-caja.show', $cajaAbiertaLocal) }}" class="btn-success">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Ver Caja Abierta
        </a>
        @else
        <a href="{{ route('cuadre-caja.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Abrir Caja
        </a>
        @endif
        @endcan
    </div>

    @if($esAdmin && $sucursales->count() > 1)
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('cuadre-caja.index') }}" class="flex flex-wrap gap-3">
                <select name="sucursal" class="form-input w-auto">
                    <option value="">Todos los locales</option>
                    @foreach($sucursales as $s)
                    <option value="{{ $s->id }}" {{ request('sucursal') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary">Filtrar</button>
                @if(request('sucursal'))
                <a href="{{ route('cuadre-caja.index') }}" class="btn-secondary">Limpiar</a>
                @endif
            </form>
        </div>
    </div>
    @elseif(!$esAdmin && auth()->user()->sucursal)
    <p class="text-xs text-gray-500">
        Mostrando cuadres de: <span class="font-medium">{{ auth()->user()->sucursal->nombre }}</span>
    </p>
    @endif

    <div class="card">
        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
            <x-per-page-selector :current="request('per_page', 25)" />
            @can('caja.ver')<x-export-btn />@endcan
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha Apertura</th>
                        @if($esAdmin)<th>Local</th>@endif
                        <th>Usuario</th>
                        <th class="text-right">Saldo Inicial</th>
                        <th class="text-right">Total Sistema</th>
                        <th class="text-right">Total Contado</th>
                        <th class="text-right">Diferencia</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cuadres as $cuadre)
                    <tr>
                        <td class="text-sm text-gray-700">
                            <span class="block font-medium">{{ $cuadre->fecha_apertura->format('d/m/Y') }}</span>
                            <span class="text-xs text-gray-400">{{ $cuadre->fecha_apertura->format('H:i') }}</span>
                        </td>
                        @if($esAdmin)<td class="text-xs text-gray-500">{{ $cuadre->sucursal?->nombre ?? '—' }}</td>@endif
                        <td class="text-gray-600">{{ $cuadre->usuario?->nombre ?? '—' }}</td>
                        <td class="text-right text-money">${{ number_format($cuadre->saldo_inicial, 0, ',', '.') }}</td>
                        <td class="text-right text-money font-medium">${{ number_format($cuadre->total_sistema, 0, ',', '.') }}</td>
                        <td class="text-right text-money font-medium">${{ number_format($cuadre->total_contado, 0, ',', '.') }}</td>
                        <td class="text-right font-bold text-money {{ $cuadre->diferencia < 0 ? 'text-red-600' : ($cuadre->diferencia > 0 ? 'text-emerald-600' : 'text-gray-500') }}">
                            {{ $cuadre->diferencia >= 0 ? '+' : '' }}${{ number_format($cuadre->diferencia, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            @if($cuadre->estado === 'Abierto')
                                <span class="badge badge-warning">Abierto</span>
                            @else
                                <span class="badge badge-success">Cerrado</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('cuadre-caja.show', $cuadre) }}" class="btn btn-secondary btn-sm">Ver</a>
                                @if($cuadre->estado === 'Cerrado')
                                <a href="{{ route('cuadre-caja.imprimir', $cuadre) }}" class="btn btn-secondary btn-sm" target="_blank">Imprimir</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-8 text-gray-400">No hay cuadres de caja registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">{{ $cuadres->links() }}</div>
    </div>

</div>
@endsection
