@extends('layouts.mobile')
@section('title', 'Menú POS')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-blue-700 to-blue-900 flex flex-col select-none">

    {{-- Header --}}
    <div class="px-5 pt-10 pb-4 flex items-start justify-between">
        <div>
            <p class="text-blue-300 text-xs font-semibold uppercase tracking-widest mb-1">VISOFTEC POS Móvil</p>
            <h1 class="text-3xl font-extrabold text-white leading-tight">¿Qué vas<br>a hacer?</h1>
            <p class="text-blue-200 text-sm mt-2">
                Hola, <span class="font-semibold text-white">{{ auth()->user()->nombre ?? auth()->user()->name }}</span>
            </p>
        </div>
        <div class="flex items-center gap-1 mt-1">
            <a href="{{ route('dashboard') }}"
               class="text-blue-300 hover:text-white p-2.5 rounded-2xl hover:bg-blue-600/60 transition-colors"
               title="Volver al panel principal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="text-blue-300 hover:text-white p-2.5 rounded-2xl hover:bg-blue-600/60 transition-colors"
                    title="Cerrar sesión">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Botones --}}
    <div class="flex-1 flex flex-col gap-4 px-4 pb-12 mt-2">

        {{-- VENTA NORMAL --}}
        <a href="{{ route('ventas.movil.venta') }}"
           class="flex items-center gap-4 bg-white rounded-3xl px-5 py-6 shadow-2xl active:scale-95 transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-blue-100 flex items-center justify-center flex-shrink-0 text-4xl">🛒</div>
            <div class="flex-1 min-w-0">
                <p class="text-xl font-extrabold text-gray-900">Venta Normal</p>
                <p class="text-sm text-gray-400 mt-0.5">Registra una venta al contado</p>
            </div>
            <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        {{-- TRASLADO (solo si tiene permiso de inventario) --}}
        @can('inventario.entradas')
        <a href="{{ route('ventas.movil.traslado') }}"
           class="flex items-center gap-4 bg-white rounded-3xl px-5 py-6 shadow-2xl active:scale-95 transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-violet-100 flex items-center justify-center flex-shrink-0 text-4xl">📦</div>
            <div class="flex-1 min-w-0">
                <p class="text-xl font-extrabold text-gray-900">Traslado</p>
                <p class="text-sm text-gray-400 mt-0.5">Mover mercancía entre bodegas</p>
            </div>
            <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        @endcan

        {{-- ENTRADA (solo si tiene permiso de inventario) --}}
        @can('inventario.entradas')
        <a href="{{ route('inventario.entrada') }}"
           class="flex items-center gap-4 bg-white rounded-3xl px-5 py-6 shadow-2xl active:scale-95 transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-green-100 flex items-center justify-center flex-shrink-0 text-4xl">📥</div>
            <div class="flex-1 min-w-0">
                <p class="text-xl font-extrabold text-gray-900">Entrada</p>
                <p class="text-sm text-gray-400 mt-0.5">Registrar mercancía que llega</p>
            </div>
            <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        @endcan

        {{-- SALIDA (mermas, ajustes, devoluciones) --}}
        @can('inventario.salidas')
        <a href="{{ route('ventas.movil.salida') }}"
           class="flex items-center gap-4 bg-white rounded-3xl px-5 py-6 shadow-2xl active:scale-95 transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-orange-100 flex items-center justify-center flex-shrink-0 text-4xl">📤</div>
            <div class="flex-1 min-w-0">
                <p class="text-xl font-extrabold text-gray-900">Salida</p>
                <p class="text-sm text-gray-400 mt-0.5">Mermas, daños, ajustes y devoluciones</p>
            </div>
            <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        @endcan

        {{-- PRÉSTAMO (crédito forzado) --}}
        <a href="{{ route('ventas.movil.prestamo') }}"
           class="flex items-center gap-4 bg-white rounded-3xl px-5 py-6 shadow-2xl active:scale-95 transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center flex-shrink-0 text-4xl">🤝</div>
            <div class="flex-1 min-w-0">
                <p class="text-xl font-extrabold text-gray-900">Préstamo</p>
                <p class="text-sm text-gray-400 mt-0.5">Venta a crédito para un cliente</p>
            </div>
            <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        {{-- HISTORIAL DE VENTAS --}}
        <a href="{{ route('ventas.movil.historial') }}"
           class="flex items-center gap-4 bg-white rounded-3xl px-5 py-6 shadow-2xl active:scale-95 transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-emerald-100 flex items-center justify-center flex-shrink-0 text-4xl">📋</div>
            <div class="flex-1 min-w-0">
                <p class="text-xl font-extrabold text-gray-900">Historial</p>
                <p class="text-sm text-gray-400 mt-0.5">Ver y anular ventas del día</p>
            </div>
            <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        {{-- CONSULTA DE TALLAS --}}
        <a href="{{ route('ventas.movil.consulta') }}"
           class="flex items-center gap-4 bg-white rounded-3xl px-5 py-6 shadow-2xl active:scale-95 transition-transform">
            <div class="w-16 h-16 rounded-2xl bg-teal-100 flex items-center justify-center flex-shrink-0 text-4xl">🔍</div>
            <div class="flex-1 min-w-0">
                <p class="text-xl font-extrabold text-gray-900">Consultar Tallas</p>
                <p class="text-sm text-gray-400 mt-0.5">Escanea la exhibición, ve el stock</p>
            </div>
            <svg class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
@endsection
