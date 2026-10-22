@extends('layouts.app')

@section('title', 'Reportes')
@section('page-title', 'Reportes y Estadísticas')

@section('content')
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">

    @can('reportes.ver')
    <a href="{{ route('reportes.ventas') }}" class="card p-6 hover:shadow-md transition-shadow group">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <h3 class="font-bold text-gray-900">Reporte de Ventas</h3>
        </div>
        <p class="text-sm text-gray-500">Ventas por período con desglose de métodos de pago, clientes y totales.</p>
        <div class="flex items-center gap-2 mt-4 text-blue-600 text-sm font-medium">
            Ver reporte
            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>

    <a href="{{ route('reportes.ganancias') }}" class="card p-6 hover:shadow-md transition-shadow group">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <h3 class="font-bold text-gray-900">Ganancias Detalladas</h3>
        </div>
        <p class="text-sm text-gray-500">Análisis de ganancias por producto, con costos y margen de rentabilidad.</p>
        <div class="flex items-center gap-2 mt-4 text-emerald-600 text-sm font-medium">
            Ver reporte
            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>

    <a href="{{ route('reportes.cartera') }}" class="card p-6 hover:shadow-md transition-shadow group">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center group-hover:bg-amber-200 transition-colors">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <h3 class="font-bold text-gray-900">Cartera de Clientes</h3>
        </div>
        <p class="text-sm text-gray-500">Clientes con deudas pendientes, días de mora y saldos por cobrar.</p>
        <div class="flex items-center gap-2 mt-4 text-amber-600 text-sm font-medium">
            Ver reporte
            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>

    <a href="{{ route('reportes.movimientos') }}" class="card p-6 hover:shadow-md transition-shadow group">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
            </div>
            <h3 class="font-bold text-gray-900">Movimientos de Inventario</h3>
        </div>
        <p class="text-sm text-gray-500">Historial completo de entradas, salidas, ventas y ajustes de inventario.</p>
        <div class="flex items-center gap-2 mt-4 text-purple-600 text-sm font-medium">
            Ver reporte
            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>

    @can('reportes.exportar')
    <div class="card p-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="font-bold text-gray-900">Exportar PDF</h3>
        </div>
        <p class="text-sm text-gray-500 mb-4">Descarga reportes en formato PDF para archivar o compartir.</p>
        <div class="space-y-2">
            <a href="{{ route('reportes.ventas.pdf') }}?fecha_inicio={{ today()->toDateString() }}&fecha_fin={{ today()->toDateString() }}" class="btn-danger w-full btn-sm">
                PDF Ventas de Hoy
            </a>
            <a href="{{ route('reportes.cartera.pdf') }}" class="btn-danger w-full btn-sm">
                PDF Cartera Clientes
            </a>
        </div>
    </div>
    @endcan
    @endcan

</div>
@endsection
