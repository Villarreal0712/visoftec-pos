@extends('layouts.app')

@section('title', 'Gasto #' . $gasto->id)
@section('page-title', 'Detalle de Gasto')

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    <div class="flex flex-wrap gap-3">
        @can('gastos.editar')
        <a href="{{ route('gastos.edit', $gasto) }}" class="btn-secondary">Editar</a>
        @endcan
        <a href="{{ route('gastos.index') }}" class="btn-secondary">← Volver</a>
        @can('gastos.eliminar')
        <form id="del-gasto" method="POST" action="{{ route('gastos.destroy', $gasto) }}" class="inline ml-auto">
            @csrf @method('DELETE')
            <button type="button" onclick="confirmarEliminar('del-gasto')" class="btn btn-danger btn-sm">Eliminar</button>
        </form>
        @endcan
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Gasto #{{ str_pad($gasto->id, 5, '0', STR_PAD_LEFT) }}</h3>
            <span class="badge {{ $gasto->tipo === 'Externo' ? 'badge-info' : 'badge-warning' }}">{{ $gasto->tipo }}</span>
        </div>
        <div class="card-body space-y-4 text-sm">

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Descripción</p>
                    <p class="font-medium text-gray-900">{{ $gasto->descripcion }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Categoría</p>
                    <span class="badge badge-gray">{{ $gasto->categoria }}</span>
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <div class="p-4 bg-red-50 rounded-xl text-center">
                    <p class="text-2xl font-extrabold text-red-600 text-money">${{ number_format($gasto->valor, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-500 mt-1">Valor del gasto</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl text-center">
                    <p class="text-base font-bold text-gray-700">{{ $gasto->created_at->format('d/m/Y') }}</p>
                    <p class="text-xs text-gray-500 mt-1">Fecha</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-xl text-center">
                    <p class="text-base font-bold text-gray-700">{{ $gasto->usuario->nombre ?? '—' }}</p>
                    <p class="text-xs text-gray-500 mt-1">Registrado por</p>
                </div>
            </div>

            @if($gasto->observacion)
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Observación</p>
                <p class="text-gray-700 bg-gray-50 rounded-lg p-3">{{ $gasto->observacion }}</p>
            </div>
            @endif

            @if($gasto->comprobante)
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Comprobante</p>
                @php $ext = pathinfo($gasto->comprobante, PATHINFO_EXTENSION); @endphp
                @if(in_array(strtolower($ext), ['jpg','jpeg','png']))
                <a href="{{ asset('storage/' . $gasto->comprobante) }}" target="_blank">
                    <img src="{{ asset('storage/' . $gasto->comprobante) }}" alt="Comprobante"
                         class="max-h-48 rounded-xl border border-gray-200 object-cover hover:opacity-90 transition-opacity">
                </a>
                @else
                <a href="{{ asset('storage/' . $gasto->comprobante) }}" target="_blank" class="btn-secondary inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Ver comprobante PDF
                </a>
                @endif
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
