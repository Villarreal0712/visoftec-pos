@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Tarjetas de estadísticas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="stat-card">
            <div class="stat-icon bg-blue-100">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div>
                <p class="stat-label">Ventas Hoy</p>
                <p class="stat-value text-money">{{ $ventasHoy }}</p>
                <p class="text-xs text-gray-400 mt-0.5">transacciones</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon bg-emerald-100">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="stat-label">Ganancia Hoy</p>
                <p class="stat-value text-money text-emerald-600">{{ number_format($gananciaHoy, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">ingreso: {{ number_format($ingresoHoy, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon {{ $productosStockBajo > 0 ? 'bg-amber-100' : 'bg-gray-100' }}">
                <svg class="w-6 h-6 {{ $productosStockBajo > 0 ? 'text-amber-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <p class="stat-label">Stock Bajo</p>
                <p class="stat-value text-money {{ $productosStockBajo > 0 ? 'text-amber-600' : '' }}">{{ $productosStockBajo }}</p>
                <p class="text-xs text-gray-400 mt-0.5">productos</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon {{ $clientesCreditoPendiente > 0 ? 'bg-red-100' : 'bg-gray-100' }}">
                <svg class="w-6 h-6 {{ $clientesCreditoPendiente > 0 ? 'text-red-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div>
                <p class="stat-label">Créditos Pendientes</p>
                <p class="stat-value text-money {{ $clientesCreditoPendiente > 0 ? 'text-red-600' : '' }}">{{ $clientesCreditoPendiente }}</p>
                <p class="text-xs text-gray-400 mt-0.5">clientes</p>
            </div>
        </div>

    </div>

    {{-- Gráficos --}}
    <div class="grid lg:grid-cols-2 gap-6">

        {{-- Ventas de la semana --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ventas Últimos 7 Días</h3>
                <span class="badge badge-info">Semana</span>
            </div>
            <div class="card-body">
                <canvas id="chartVentasSemana" height="200"></canvas>
            </div>
        </div>

        {{-- Ganancias mensuales --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ganancias Mensuales</h3>
                <span class="badge badge-success">6 Meses</span>
            </div>
            <div class="card-body">
                <canvas id="chartGanancias" height="200"></canvas>
            </div>
        </div>

    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Top productos --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Productos del Mes</h3>
            </div>
            <div class="card-body p-0">
                @forelse($topProductos as $i => $producto)
                <div class="flex items-center gap-3 px-5 py-3 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                    <span class="w-7 h-7 rounded-full {{ $i === 0 ? 'bg-yellow-100 text-yellow-700' : ($i === 1 ? 'bg-gray-100 text-gray-600' : 'bg-orange-50 text-orange-600') }} flex items-center justify-center text-xs font-bold flex-shrink-0">
                        {{ $i + 1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $producto->nombre }}</p>
                        <p class="text-xs text-gray-500">{{ $producto->total_vendido }} unidades</p>
                    </div>
                    <p class="text-sm font-semibold text-gray-900 text-money">
                        ${{ number_format($producto->ingresos, 0, ',', '.') }}
                    </p>
                </div>
                @empty
                <div class="text-center py-8 text-gray-400 text-sm">Sin ventas este mes</div>
                @endforelse
            </div>
        </div>

        {{-- Últimas ventas --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="card-title">Últimas Ventas</h3>
                <a href="{{ route('ventas.index') }}" class="text-sm text-blue-600 hover:underline">Ver todas</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Método</th>
                            <th class="text-right">Total</th>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ultimasVentas as $venta)
                        <tr>
                            <td>
                                <a href="{{ route('ventas.show', $venta) }}" class="text-blue-600 font-medium hover:underline">
                                    #{{ $venta->id }}
                                </a>
                            </td>
                            <td class="text-gray-700">
                                {{ $venta->cliente?->nombre ?? 'Venta directa' }}
                            </td>
                            <td>
                                <span class="badge {{ $venta->es_credito ? 'badge-warning' : 'badge-success' }}">
                                    {{ $venta->metodo_pago }}
                                </span>
                            </td>
                            <td class="text-right font-semibold text-money">
                                ${{ number_format($venta->total, 0, ',', '.') }}
                            </td>
                            <td class="text-gray-500 text-xs">
                                {{ $venta->created_at->format('H:i') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-gray-400">No hay ventas registradas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Alertas de stock bajo --}}
    @if($productosStockBajo > 0)
    <div class="alert-warning">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div>
            <p class="font-medium">{{ $productosStockBajo }} producto(s) con stock bajo</p>
            <p class="text-sm mt-0.5">
                <a href="{{ route('inventario.index') }}?stock_bajo=1" class="underline hover:no-underline">Ver productos afectados →</a>
            </p>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
// Los datos de PHP se inyectan de forma sincrona (seguro hacerlo aquí)
const ventasSemana      = @json($ventasSemana);
const gananciasMensuales = @json($gananciasMensuales);

// Los charts requieren window.Chart, que lo setea app.js (type="module", diferido).
// DOMContentLoaded dispara DESPUÉS de que los módulos diferidos ya corrieron.
document.addEventListener('DOMContentLoaded', function () {

    const colors = {
        blue:    { bg: 'rgba(59,130,246,0.15)',  border: 'rgb(59,130,246)' },
        emerald: { bg: 'rgba(16,185,129,0.15)',  border: 'rgb(16,185,129)' },
    };

    // ── Gráfico: ventas de los últimos 7 días ────────────────────────────
    const dias7 = Array.from({length: 7}, (_, i) => {
        const d = new Date();
        d.setDate(d.getDate() - (6 - i));
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    });

    const labels7 = dias7.map(fecha => {
        const d = new Date(fecha + 'T00:00:00');
        return d.toLocaleDateString('es-CO', { weekday: 'short', day: 'numeric' });
    });

    const ventasData = dias7.map(fecha => {
        const found = ventasSemana.find(v => v.fecha === fecha);
        return found ? found.total : 0;
    });

    new Chart(document.getElementById('chartVentasSemana'), {
        type: 'bar',
        data: {
            labels: labels7,
            datasets: [{
                label: 'Ventas ($)',
                data: ventasData,
                backgroundColor: colors.blue.bg,
                borderColor: colors.blue.border,
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString('es-CO') } }
            }
        }
    });

    // ── Gráfico: ganancias mensuales ─────────────────────────────────────
    const meses     = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const labelsGan = gananciasMensuales.map(g => meses[g.mes - 1] + ' ' + g.anio);
    const dataGan   = gananciasMensuales.map(g => g.ganancia);
    const dataVen   = gananciasMensuales.map(g => g.ventas);

    new Chart(document.getElementById('chartGanancias'), {
        type: 'line',
        data: {
            labels: labelsGan.length ? labelsGan : ['Sin datos'],
            datasets: [
                {
                    label: 'Ventas',
                    data: dataVen,
                    borderColor: colors.blue.border,
                    backgroundColor: colors.blue.bg,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Ganancia',
                    data: dataGan,
                    borderColor: colors.emerald.border,
                    backgroundColor: colors.emerald.bg,
                    fill: true,
                    tension: 0.4,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString('es-CO') } }
            }
        }
    });

}); // fin DOMContentLoaded
</script>
@endpush
