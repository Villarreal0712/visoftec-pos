@extends('layouts.app')

@section('title', 'Traslados')
@section('page-title', 'Traslados de Inventario')

@section('content')
<div class="space-y-5">

    {{-- Cabecera --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-sm text-gray-500 mt-0.5">Movimientos de stock entre bodegas</p>
        </div>
        <a href="{{ route('traslados.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nuevo Traslado
        </a>
    </div>

    {{-- Búsqueda --}}
    <form method="GET" action="{{ route('traslados.index') }}" class="flex gap-2">
        <input type="text" name="buscar" value="{{ request('buscar') }}"
               placeholder="Buscar por número o descripción..."
               class="form-input flex-1 max-w-sm">
        <button type="submit" class="btn-secondary">Buscar</button>
        @if(request('buscar'))
        <a href="{{ route('traslados.index') }}" class="btn-secondary">Limpiar</a>
        @endif
    </form>

    {{-- Tabla --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">N° Traslado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Origen</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Destino</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Registrado por</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($traslados as $t)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm font-semibold text-blue-600">{{ $t->numero_traslado }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-900">{{ $t->bodegaOrigen->nombre ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-900">{{ $t->bodegaDestino->nombre ?? '—' }}</span>
                                <svg class="w-3 h-3 text-gray-400 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none"></svg>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $t->usuario->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('traslados.show', $t) }}" class="btn-secondary btn-sm">Ver detalle</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <p class="font-medium">No hay traslados registrados</p>
                                <p class="text-sm mt-1">Crea el primer traslado para mover stock entre bodegas</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{ $traslados->links() }}

</div>
@endsection
