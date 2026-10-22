@extends('layouts.app')

@section('title', 'Cartera de Clientes')
@section('page-title', 'Cartera de Clientes')

@section('content')
<div class="space-y-5">

    <div class="flex justify-between items-center">
        <div class="stat-card inline-flex">
            <div class="stat-icon bg-red-100"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg></div>
            <div>
                <p class="stat-label">Total Cartera</p>
                <p class="text-2xl font-extrabold text-red-600 text-money">${{ number_format($totalCartera, 0, ',', '.') }}</p>
            </div>
        </div>
        @can('reportes.exportar')
        <a href="{{ route('reportes.cartera.pdf') }}" class="btn-danger">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Exportar PDF
        </a>
        @endcan
    </div>

    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Teléfono</th>
                        <th class="text-center">Ventas en Mora</th>
                        <th class="text-right">Deuda Total</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                    @php $deuda = $cliente->ventas->sum('saldo_pendiente'); @endphp
                    <tr>
                        <td>
                            <p class="font-medium text-gray-900">{{ $cliente->nombre }}</p>
                            @if($cliente->correo)<p class="text-xs text-gray-400">{{ $cliente->correo }}</p>@endif
                        </td>
                        <td class="text-gray-600">{{ $cliente->documento }}</td>
                        <td class="text-gray-600">{{ $cliente->telefono ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $cliente->ventas_mora > 0 ? 'badge-danger' : 'badge-warning' }}">
                                {{ $cliente->ventas_mora }} en mora / {{ $cliente->ventas->count() }} total
                            </span>
                        </td>
                        <td class="text-right font-bold text-red-600 text-money">${{ number_format($deuda, 0, ',', '.') }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('clientes.show', $cliente) }}#creditos" class="btn btn-warning btn-sm">Gestionar</a>
                                <a href="{{ route('clientes.estado-cuenta', $cliente) }}" target="_blank" class="btn btn-secondary btn-sm">Estado</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-8 text-emerald-600 font-medium">¡No hay deudas pendientes!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
