<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\GastoExterno;
use App\Models\HistorialMovimiento;
use App\Models\HistorialVenta;
use App\Models\Producto;
use App\Models\Venta;
use App\Traits\HandlesTableFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    use HandlesTableFeatures;
    public function index()
    {
        return view('reportes.index');
    }

    /**
     * Reporte de ventas por período
     */
    public function ventas(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', today()->startOfMonth()->toDateString());
        $fechaFin = $request->get('fecha_fin', today()->toDateString());

        $query = Venta::with(['usuario', 'cliente', 'historialVentas'])
                       ->activas()
                       ->entreFechas($fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59');

        if (!auth()->user()->can('roles.gestionar')) {
            $query->where('id_sucursal', auth()->user()->id_sucursal ?? 1);
        }

        $ventas = $query->latest()->get();

        $totalVentas = $ventas->sum('total');
        $totalGanancia = $ventas->sum(fn($v) => $v->historialVentas->sum('ganancia'));

        // Agrupar por método de pago
        $porMetodoPago = $ventas->groupBy('metodo_pago')
                                ->map(fn($grupo) => [
                                    'cantidad' => $grupo->count(),
                                    'total' => $grupo->sum('total'),
                                ]);

        return view('reportes.ventas', compact(
            'ventas', 'totalVentas', 'totalGanancia', 'porMetodoPago',
            'fechaInicio', 'fechaFin'
        ));
    }

    /**
     * Reporte de cartera de clientes
     */
    public function cartera(Request $request)
    {
        $esAdmin = auth()->user()->can('roles.gestionar');
        $idSucursal = auth()->user()->id_sucursal ?? 1;

        $filtroCredito = function ($q) use ($esAdmin, $idSucursal) {
            $q->conCreditoPendiente();
            if (!$esAdmin) {
                $q->where('id_sucursal', $idSucursal);
            }
        };

        $clientes = Cliente::with(['ventas' => $filtroCredito])
                            ->whereHas('ventas', $filtroCredito)
                            ->get()
                            ->map(function ($cliente) {
                                $cliente->total_deuda = $cliente->ventas->sum('saldo_pendiente');
                                $cliente->ventas_mora = $cliente->ventas->filter(fn($v) => $v->estado_credito === Venta::ESTADO_MORA)->count();
                                return $cliente;
                            })
                            ->sortByDesc('total_deuda');

        $totalCartera = $clientes->sum('total_deuda');

        return view('reportes.cartera', compact('clientes', 'totalCartera'));
    }

    /**
     * Reporte de ganancias detalladas
     */
    public function ganancias(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', today()->startOfMonth()->toDateString());
        $fechaFin = $request->get('fecha_fin', today()->toDateString());

        $detalles = DB::table('historial_ventas as hv')
            ->join('ventas as v', 'hv.id_venta', '=', 'v.id')
            ->join('lotes_inventario as li', 'hv.id_lote', '=', 'li.id')
            ->join('detalle_productos as dp', 'li.id_detalle_producto', '=', 'dp.id')
            ->join('productos as p', 'dp.id_producto', '=', 'p.id')
            ->where('v.anulada', false)
            ->whereBetween(DB::raw('DATE(v.created_at)'), [$fechaInicio, $fechaFin])
            ->when(!auth()->user()->can('roles.gestionar'), fn($q) => $q->where('v.id_sucursal', auth()->user()->id_sucursal ?? 1))
            ->selectRaw('
                p.nombre as producto,
                dp.talla,
                SUM(hv.cantidad) as cantidad_vendida,
                AVG(hv.precio_venta) as precio_promedio,
                AVG(hv.costo_unitario) as costo_promedio,
                SUM(hv.cantidad * hv.precio_venta) as total_ingresos,
                SUM(hv.cantidad * hv.costo_unitario) as total_costos,
                SUM(hv.cantidad * (hv.precio_venta - hv.costo_unitario)) as ganancia_total
            ')
            ->groupBy('p.id', 'p.nombre', 'dp.id', 'dp.talla')
            ->orderByDesc('ganancia_total')
            ->get();

        $totalIngresos = $detalles->sum('total_ingresos');
        $totalCostos = $detalles->sum('total_costos');
        $totalGanancia = $detalles->sum('ganancia_total');

        return view('reportes.ganancias', compact(
            'detalles', 'totalIngresos', 'totalCostos', 'totalGanancia',
            'fechaInicio', 'fechaFin'
        ));
    }

    /**
     * Reporte de movimientos de inventario
     */
    public function movimientos(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', today()->toDateString());
        $fechaFin = $request->get('fecha_fin', today()->toDateString());

        $query = HistorialMovimiento::with(['detalleProducto.producto', 'usuario'])
                                    ->entreFechas($fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59')
                                    ->when($request->filled('tipo'), fn($q) => $q->where('tipo_operacion', $request->tipo))
                                    ->latest();

        if ($request->boolean('exportar')) {
            $items = (clone $query)->get();
            return $this->exportarCsv('movimientos_inventario', $items, [
                'Fecha'      => fn($m) => $m->created_at->format('d/m/Y H:i'),
                'Producto'   => fn($m) => $m->detalleProducto?->producto?->nombre ?? '—',
                'Talla'      => fn($m) => $m->detalleProducto?->talla ?? '—',
                'Tipo'       => fn($m) => $m->tipo_operacion,
                'Cantidad'   => fn($m) => $m->cantidad,
                'Costo unit.'=> fn($m) => number_format($m->costo_unitario ?? 0, 2, '.', ''),
                'Observación'=> fn($m) => $m->observacion ?? '—',
                'Usuario'    => fn($m) => $m->usuario?->nombre ?? '—',
            ]);
        }

        $movimientos = $query->paginate($this->getPerPage(50))->withQueryString();

        return view('reportes.movimientos', compact('movimientos', 'fechaInicio', 'fechaFin'));
    }

    /**
     * Exporta reporte de ventas a PDF
     */
    public function exportarVentasPDF(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', today()->toDateString());
        $fechaFin = $request->get('fecha_fin', today()->toDateString());

        $query = Venta::with(['usuario', 'cliente'])
                       ->activas()
                       ->entreFechas($fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59');

        if (!auth()->user()->can('roles.gestionar')) {
            $query->where('id_sucursal', auth()->user()->id_sucursal ?? 1);
        }

        $ventas = $query->latest()->get();

        $totalVentas = $ventas->sum('total');

        $pdf = Pdf::loadView('reportes.pdf.ventas', compact('ventas', 'totalVentas', 'fechaInicio', 'fechaFin'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download("ventas_{$fechaInicio}_{$fechaFin}.pdf");
    }

    /**
     * Exporta cartera de clientes a PDF
     */
    public function exportarCarteraPDF(Request $request)
    {
        $esAdmin = auth()->user()->can('roles.gestionar');
        $idSucursal = auth()->user()->id_sucursal ?? 1;

        $filtroCredito = function ($q) use ($esAdmin, $idSucursal) {
            $q->conCreditoPendiente();
            if (!$esAdmin) {
                $q->where('id_sucursal', $idSucursal);
            }
        };

        $clientes = Cliente::with(['ventas' => $filtroCredito])
                            ->whereHas('ventas', $filtroCredito)
                            ->get();

        $totalCartera = $clientes->sum(fn($c) => $c->ventas->sum('saldo_pendiente'));

        $pdf = Pdf::loadView('reportes.pdf.cartera', compact('clientes', 'totalCartera'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download('cartera_clientes_' . today()->toDateString() . '.pdf');
    }
}
