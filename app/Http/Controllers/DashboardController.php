<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\GastoExterno;
use App\Models\HistorialVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('roles.gestionar')) {
            return redirect()->route('ventas.create');
        }

        // Estadísticas del día
        $ventasHoy = Venta::deHoy()->count();
        $ingresoHoy = Venta::deHoy()->sum('total');
        $gastosHoy = GastoExterno::deHoy()->sum('valor');
        $gananciaHoy = $this->calcularGananciaHoy();

        // Productos con stock bajo (una sola query con subquery, sin cargar modelos en memoria)
        $productosStockBajo = Producto::activos()
            ->whereColumn(
                'stock_minimo', '>=',
                DB::raw('(SELECT COALESCE(SUM(li.cantidad_disponible),0)
                          FROM lotes_inventario li
                          JOIN detalle_productos dp ON li.id_detalle_producto = dp.id
                          WHERE dp.id_producto = productos.id)')
            )
            ->count();

        // Clientes con crédito pendiente
        $clientesCreditoPendiente = Cliente::whereHas('ventas', function ($q) {
            $q->conCreditoPendiente();
        })->count();

        // Gráfico: ventas de los últimos 7 días
        $ventasSemana = DB::table('ventas')
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as cantidad, SUM(total) as total')
            ->where('anulada', false)
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        // Top 5 productos más vendidos este mes
        $topProductos = DB::table('historial_ventas as hv')
            ->join('lotes_inventario as li', 'hv.id_lote', '=', 'li.id')
            ->join('detalle_productos as dp', 'li.id_detalle_producto', '=', 'dp.id')
            ->join('productos as p', 'dp.id_producto', '=', 'p.id')
            ->join('ventas as v', 'hv.id_venta', '=', 'v.id')
            ->where('v.created_at', '>=', now()->startOfMonth())
            ->where('v.anulada', false)
            ->selectRaw('p.nombre, SUM(hv.cantidad) as total_vendido, SUM(hv.cantidad * hv.precio_venta) as ingresos')
            ->groupBy('p.id', 'p.nombre')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        // Ganancias mensuales (últimos 6 meses)
        $gananciasMensuales = DB::table('historial_ventas as hv')
            ->join('ventas as v', 'hv.id_venta', '=', 'v.id')
            ->where('v.created_at', '>=', now()->subMonths(6))
            ->where('v.anulada', false)
            ->selectRaw('YEAR(v.created_at) as anio, MONTH(v.created_at) as mes,
                         SUM(hv.cantidad * hv.precio_venta) as ventas,
                         SUM(hv.cantidad * hv.costo_unitario) as costos')
            ->groupBy('anio', 'mes')
            ->orderBy('anio')->orderBy('mes')
            ->get()
            ->map(function ($item) {
                $item->ganancia = $item->ventas - $item->costos;
                return $item;
            });

        $ultimasVentas = Venta::with(['usuario', 'cliente'])
            ->activas()
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'ventasHoy',
            'ingresoHoy',
            'gastosHoy',
            'gananciaHoy',
            'productosStockBajo',
            'clientesCreditoPendiente',
            'ventasSemana',
            'topProductos',
            'gananciasMensuales',
            'ultimasVentas'
        ));
    }

    private function calcularGananciaHoy(): float
    {
        return DB::table('historial_ventas as hv')
            ->join('ventas as v', 'hv.id_venta', '=', 'v.id')
            ->whereDate('v.created_at', today())
            ->where('v.anulada', false)
            ->selectRaw('SUM(hv.cantidad * (hv.precio_venta - hv.costo_unitario)) as ganancia')
            ->value('ganancia') ?? 0;
    }
}
