<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\DetalleProducto;
use App\Models\DetalleTraslado;
use App\Models\HistorialMovimiento;
use App\Models\LoteInventario;
use App\Models\Traslado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TrasladoController extends Controller
{
    public function index(Request $request)
    {
        $query = Traslado::with(['bodegaOrigen', 'bodegaDestino', 'usuario'])
                          ->latest();

        if ($request->filled('buscar')) {
            $query->where('numero_traslado', 'like', "%{$request->buscar}%")
                  ->orWhere('observacion', 'like', "%{$request->buscar}%");
        }

        $traslados = $query->paginate(20)->withQueryString();

        return view('traslados.index', compact('traslados'));
    }

    public function create()
    {
        $bodegas = Bodega::with('sucursal')->where('activo', true)->orderBy('nombre')->get();

        return view('traslados.create', compact('bodegas'));
    }

    public function createMovil()
    {
        $bodegas = Bodega::with('sucursal')->where('activo', true)->orderBy('nombre')->get();
        $bodegaActivaId = auth()->user()->id_bodega;

        $preloadBarcode = null;
        if (request()->filled('item')) {
            $detalle = \App\Models\DetalleProducto::find((int) request()->item);
            $preloadBarcode = $detalle?->codigo_barras;
        }

        return view('traslados.pos-movil', compact('bodegas', 'bodegaActivaId', 'preloadBarcode'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_bodega_origen'       => ['required', 'integer', Rule::exists('bodegas', 'id')->where('activo', true)],
            'id_bodega_destino'      => ['required', 'integer', 'different:id_bodega_origen', Rule::exists('bodegas', 'id')->where('activo', true)],
            'observacion'            => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.id_detalle_producto' => 'required|integer|exists:detalle_productos,id',
            'items.*.cantidad'       => 'required|integer|min:1',
        ]);

        $idOrigen  = (int) $request->id_bodega_origen;
        $idDestino = (int) $request->id_bodega_destino;

        // Verificar stock suficiente antes de hacer cualquier cambio
        foreach ($request->items as $item) {
            $stockDisponible = LoteInventario::where('id_detalle_producto', $item['id_detalle_producto'])
                                              ->conStock($idOrigen)
                                              ->sum('cantidad_disponible');

            if ($stockDisponible < $item['cantidad']) {
                $detalle = DetalleProducto::with('producto')->find($item['id_detalle_producto']);
                return back()->withInput()->withErrors([
                    'stock' => "Stock insuficiente en bodega origen para \"{$detalle?->producto?->nombre}\". "
                             . "Disponible: {$stockDisponible}, solicitado: {$item['cantidad']}.",
                ]);
            }
        }

        try {
        DB::transaction(function () use ($request, $idOrigen, $idDestino) {
            $traslado = Traslado::create([
                'numero_traslado'  => Traslado::generarNumero(),
                'id_bodega_origen' => $idOrigen,
                'id_bodega_destino'=> $idDestino,
                'id_usuario'       => auth()->id(),
                'observacion'      => $request->observacion,
            ]);

            foreach ($request->items as $item) {
                $idDetalleProducto = (int) $item['id_detalle_producto'];
                $cantidadNecesaria = (int) $item['cantidad'];

                // FIFO: tomar lotes más antiguos primero
                $lotes = LoteInventario::where('id_detalle_producto', $idDetalleProducto)
                                        ->conStock($idOrigen)
                                        ->lockForUpdate()
                                        ->get();

                $costoTotal     = 0;
                $cantidadTomada = 0;
                $pendiente      = $cantidadNecesaria;

                foreach ($lotes as $lote) {
                    if ($pendiente <= 0) break;

                    $tomar = min($pendiente, $lote->cantidad_disponible);
                    $lote->decrement('cantidad_disponible', $tomar);

                    $costoTotal     += $tomar * (float) $lote->costo_unitario;
                    $cantidadTomada += $tomar;
                    $pendiente      -= $tomar;
                }

                if ($pendiente > 0) {
                    // El stock disponible cambió entre la verificación previa y el bloqueo
                    // (otra venta/traslado concurrente lo tomó primero). Abortar todo el
                    // traslado para no crear en destino más de lo que realmente salió del origen.
                    $detalleNombre = DetalleProducto::with('producto')->find($idDetalleProducto);
                    throw new \Exception("Stock insuficiente en bodega origen para \"{$detalleNombre?->producto?->nombre}\": faltaron {$pendiente} unidad(es) al momento de trasladar. Intenta de nuevo.");
                }

                $costoPromedio = $cantidadTomada > 0 ? $costoTotal / $cantidadTomada : 0;

                // Nuevo lote en bodega destino
                LoteInventario::create([
                    'id_detalle_producto' => $idDetalleProducto,
                    'id_bodega'           => $idDestino,
                    'fecha_ingreso'       => now(),
                    'cantidad_disponible' => $cantidadTomada,
                    'costo_unitario'      => $costoPromedio,
                    'tipo_origen'         => 'Propio',
                ]);

                // Historial — salida de origen (cantidad negativa)
                HistorialMovimiento::create([
                    'id_detalle_producto' => $idDetalleProducto,
                    'tipo_operacion'      => HistorialMovimiento::TIPO_TRANSFERENCIA,
                    'tipo_origen'         => 'Propio',
                    'cantidad'            => -$cantidadTomada,
                    'costo_unitario'      => $costoPromedio,
                    'id_usuario'          => auth()->id(),
                    'observacion'         => "Traslado {$traslado->numero_traslado} — salida hacia bodega ID {$idDestino}",
                    'id_bodega'           => $idOrigen,
                    'id_traslado'         => $traslado->id,
                ]);

                // Historial — entrada en destino (cantidad positiva)
                HistorialMovimiento::create([
                    'id_detalle_producto' => $idDetalleProducto,
                    'tipo_operacion'      => HistorialMovimiento::TIPO_TRANSFERENCIA,
                    'tipo_origen'         => 'Propio',
                    'cantidad'            => $cantidadTomada,
                    'costo_unitario'      => $costoPromedio,
                    'id_usuario'          => auth()->id(),
                    'observacion'         => "Traslado {$traslado->numero_traslado} — entrada desde bodega ID {$idOrigen}",
                    'id_bodega'           => $idDestino,
                    'id_traslado'         => $traslado->id,
                ]);

                DetalleTraslado::create([
                    'id_traslado'         => $traslado->id,
                    'id_detalle_producto' => $idDetalleProducto,
                    'cantidad'            => $cantidadTomada,
                    'costo_unitario'      => $costoPromedio,
                ]);
            }

            session()->flash('traslado_id', $traslado->id);
        });
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['stock' => $e->getMessage()]);
        }

        return redirect()->route('traslados.index')
                         ->with('success', 'Traslado registrado correctamente.');
    }

    public function show(Traslado $traslado)
    {
        $traslado->load(['bodegaOrigen', 'bodegaDestino', 'usuario',
                         'detalles.detalleProducto.producto']);

        return view('traslados.show', compact('traslado'));
    }

    /** Búsqueda de productos con stock en una bodega específica (para el formulario) */
    public function buscarProducto(Request $request)
    {
        $idBodega = (int) $request->id_bodega;
        $termino  = $request->get('q', '');

        if (!$idBodega) {
            return response()->json([]);
        }

        $resultados = DetalleProducto::with(['producto', 'lotes' => fn($q) => $q->conStock($idBodega)])
            ->whereHas('lotes', fn($q) => $q->conStock($idBodega))
            ->where(function ($q) use ($termino) {
                $q->where('codigo_barras', 'like', "%{$termino}%")
                  ->orWhereHas('producto', fn($pq) => $pq->where('nombre', 'like', "%{$termino}%"));
            })
            ->limit(15)
            ->get()
            ->map(fn($d) => [
                'id'             => $d->id,
                'codigo_barras'  => $d->codigo_barras,
                'nombre'         => $d->producto->nombre,
                'talla'          => $d->talla,
                'stock_bodega'   => $d->lotes->sum('cantidad_disponible'),
            ]);

        return response()->json($resultados);
    }
}
