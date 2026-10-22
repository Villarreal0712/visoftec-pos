@extends('layouts.app')

@section('title', 'Cuentas con Locales Externos')
@section('page-title', 'Cuentas con Locales Externos')

@section('breadcrumbs')
    <li class="flex items-center gap-2"><span>/</span><span class="text-gray-800 font-medium">Cuentas Externos</span></li>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-5">

    @if($locales->isEmpty())
    <div class="card">
        <div class="card-body text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <p class="font-medium text-sm">Sin cuentas pendientes</p>
            <p class="text-xs text-gray-300 mt-1">Cuando vendas productos prestados de locales externos aparecerán aquí.</p>
        </div>
    </div>
    @else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($locales as $local)
        <a href="{{ route('prestamos.show', $local) }}"
           class="card hover:shadow-md transition-shadow cursor-pointer group">
            <div class="card-body space-y-3">
                {{-- Header --}}
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors">
                            {{ $local->nombre_local }}
                        </p>
                        @if($local->contacto)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $local->contacto }}</p>
                        @endif
                    </div>
                    @if($local->prestamos_pendientes > 0)
                    <span class="text-xs font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">
                        {{ $local->prestamos_pendientes }} pendiente{{ $local->prestamos_pendientes !== 1 ? 's' : '' }}
                    </span>
                    @else
                    <span class="text-xs font-medium bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Al día</span>
                    @endif
                </div>

                {{-- Montos --}}
                <div class="space-y-1.5 text-sm border-t pt-3">
                    <div class="flex justify-between text-gray-500">
                        <span>Total vendido</span>
                        <span class="font-semibold text-gray-800 text-money">${{ number_format($local->total_vendido, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>Deuda calculada</span>
                        <span class="font-semibold text-money
                            {{ $local->total_deuda > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $local->total_deuda > 0 ? '$'.number_format($local->total_deuda, 0, ',', '.') : '—' }}
                        </span>
                    </div>
                    @if($local->sin_costo > 0)
                    <p class="text-xs text-amber-600">
                        ⚠ {{ $local->sin_costo }} ítem{{ $local->sin_costo !== 1 ? 's' : '' }} sin costo definido
                    </p>
                    @endif
                    <div class="flex justify-between text-gray-500 border-t pt-1.5">
                        <span>Ya pagado</span>
                        <span class="font-semibold text-emerald-600 text-money">${{ number_format($local->total_liquidado, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="text-xs text-blue-500 text-right group-hover:underline">Ver detalle →</div>
            </div>
        </a>
        @endforeach
    </div>
    @endif

</div>
@endsection
