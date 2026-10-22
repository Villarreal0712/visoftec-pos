<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\DetalleProducto;
use App\Models\EntradaMercancia;
use App\Models\HistorialMovimiento;
use App\Models\LocalExterno;
use App\Models\LoteInventario;
use App\Models\Categoria;
use App\Traits\HandlesTableFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventarioController extends Controller
{
    use HandlesTableFeatures;

    /**
     * Vista principal del inventario actual
     */
    public function index(Request $request)
    {
        $bodegas = Bodega::where('activo', true)->orderBy('nombre')->get();

        // Sin parámetro: abre en la bodega del usuario. "bodega=" (Todas las bodegas) o un id
        // elegido en el selector se respetan.
        if ($request->has('bodega')) {
            $idBodega = $request->filled('bodega') ? (int) $request->bodega : null;
        } else {
            $propia   = auth()->user()->id_bodega;
            $idBodega = $bodegas->contains('id', $propia) ? (int) $propia : null;
        }

        $query = DetalleProducto::with(['producto.categoria', 'lotes' => fn($q) => $q->conStock($idBodega)->with('bodega')])
                                 ->whereHas('producto', fn($q) => $q->activos());

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('codigo_barras', 'like', "%{$request->buscar}%")
                  ->orWhereHas('producto', fn($pq) => $pq->where('nombre', 'like', "%{$request->buscar}%"));
            });
        }

        if ($request->filled('categoria')) {
            $query->whereHas('producto', fn($q) => $q->where('id_categoria', $request->categoria));
        }

        $bodegaAndClause = $idBodega ? "AND li.id_bodega = {$idBodega}" : '';

        match ($request->get('stock')) {
            'con_stock' => $query->whereHas('lotes', fn($q) => $q->conStock($idBodega)),

            'bajo' => $query->where(function ($q) use ($bodegaAndClause) {
                $q->whereDoesntHave('lotes', fn($lq) => $lq->conStock())
                  ->orWhereHas('producto', fn($pq) => $pq->whereRaw(
                      "stock_minimo >= (SELECT COALESCE(SUM(li.cantidad_disponible),0)
                                        FROM lotes_inventario li
                                        WHERE li.id_detalle_producto = detalle_productos.id
                                        AND li.cantidad_disponible > 0
                                        {$bodegaAndClause})"
                  ));
            }),

            'agotado' => $query->whereDoesntHave('lotes', fn($q) => $q->conStock($idBodega)),

            default => null,
        };

        if ($request->boolean('exportar')) {
            $items = (clone $query)->get();

            $columnas = [
                'Código barras' => fn($d) => $d->codigo_barras,
                'Producto'      => fn($d) => $d->producto->nombre,
                'Talla'         => fn($d) => $d->talla ?? '—',
                'Categoría'     => fn($d) => $d->producto->categoria?->nombre ?? '—',
            ];

            // Una columna de stock por bodega (solo la filtrada, si hay filtro). Se incluyen
            // también bodegas inactivas que aún tengan stock para que el total cuadre.
            $bodegasExport = Bodega::when($idBodega,
                    fn($q) => $q->where('id', $idBodega),
                    fn($q) => $q->where('activo', true)->orWhereHas('lotes', fn($lq) => $lq->where('cantidad_disponible', '>', 0)))
                ->orderBy('nombre')
                ->get();

            // Los nombres de bodega no son únicos: si dos se llaman igual (o una choca con
            // "Stock total"/"Stock mínimo"), se les agrega el id para que ninguna columna
            // sobrescriba a otra en silencio.
            $reservadas = ['Stock total', 'Stock mínimo'];
            foreach ($bodegasExport as $bodega) {
                $titulo = "Stock {$bodega->nombre}";
                if (isset($columnas[$titulo]) || in_array(mb_strtolower($titulo), array_map('mb_strtolower', $reservadas), true)) {
                    $titulo .= " (#{$bodega->id})";
                }
                $columnas[$titulo] = fn($d) => $d->lotes->where('id_bodega', $bodega->id)->sum('cantidad_disponible');
            }

            if (!$idBodega && $bodegasExport->count() > 1) {
                $columnas['Stock total'] = fn($d) => $d->lotes->sum('cantidad_disponible');
            }

            $columnas['Stock mínimo'] = fn($d) => $d->producto->stock_minimo;
            $columnas['Precio venta'] = fn($d) => number_format($d->producto->precio_venta ?? 0, 2, '.', '');

            $sufijo = $idBodega ? '_' . \Illuminate\Support\Str::slug($bodegasExport->first()?->nombre ?? 'bodega') : '';

            return $this->exportarCsv('inventario' . $sufijo, $items, $columnas);
        }

        $detalles = $query->paginate($this->getPerPage(25))->withQueryString();

        $categorias = Categoria::activas()->orderBy('nombre')->get();
        $bodegaSeleccionada = $idBodega;

        return view('inventario.index', compact('detalles', 'categorias', 'bodegas', 'bodegaSeleccionada'));
    }

    /**
     * Formulario de entrada tipo carrito
     */
    public function createEntrada()
    {
        $localesExternos = LocalExterno::where('activo', true)->get();
        $proximoNumero   = EntradaMercancia::generarNumero();
        $bodegas         = Bodega::where('activo', true)->orderBy('nombre')->get();
        $bodegaDefecto   = auth()->user()->id_bodega ?? 1;

        return view('inventario.entrada', compact('localesExternos', 'proximoNumero', 'bodegas', 'bodegaDefecto'));
    }

    /**
     * Búsqueda de productos/variantes para el carrito de entrada (devuelve JSON)
     */
    public function buscarParaEntrada(Request $request)
    {
        $termino = trim($request->get('q', ''));

        if (strlen($termino) < 2) {
            return response()->json([]);
        }

        $detalles = DetalleProducto::with(['producto', 'lotes' => fn($q) => $q->conStock()])
            ->whereHas('producto', fn($q) => $q->activos())
            ->where(function ($q) use ($termino) {
                $q->where('codigo_barras', $termino)
                  ->orWhereHas('producto', fn($pq) => $pq->where('nombre', 'like', "%{$termino}%"));
            })
            ->limit(20)
            ->get()
            ->map(fn($d) => [
                'id'            => $d->id,
                'producto'      => $d->producto->nombre,
                'talla'         => $d->talla,
                'codigo_barras' => $d->codigo_barras,
                'stock_actual'  => $d->lotes->sum('cantidad_disponible'),
            ]);

        return response()->json($detalles);
    }

    /**
     * Guarda la entrada completa (carrito) en una sola transacción
     */
    public function storeEntrada(Request $request)
    {
        $request->validate([
            'tipo_origen'    => 'required|in:Propio,Prestado',
            'id_local_externo' => 'required_if:tipo_origen,Prestado|nullable|exists:locales_externos,id',
            'id_bodega'      => ['required', Rule::exists('bodegas', 'id')->where('activo', true)],
            'observacion'    => 'nullable|string|max:300',
            'items'          => 'required|array|min:1',
            'items.*.id_detalle_producto' => 'required|exists:detalle_productos,id',
            'items.*.cantidad'            => 'required|integer|min:1',
            'items.*.costo_unitario'      => 'required|numeric|min:0|max:99999999',
            'items.*.precio_venta'        => 'nullable|numeric|min:0|max:99999999',
        ]);

        $entradaId = null;

        DB::transaction(function () use ($request, &$entradaId) {
            $idBodega = (int) $request->id_bodega;

            $entrada = EntradaMercancia::create([
                'numero_entrada'   => EntradaMercancia::generarNumero(),
                'tipo_origen'      => $request->tipo_origen,
                'id_local_externo' => $request->id_local_externo,
                'id_usuario'       => auth()->id(),
                'id_bodega'        => $idBodega,
                'observacion'      => $request->observacion,
            ]);

            $entradaId = $entrada->id;

            foreach ($request->items as $item) {
                $movimiento = HistorialMovimiento::create([
                    'id_detalle_producto' => $item['id_detalle_producto'],
                    'tipo_operacion'      => HistorialMovimiento::TIPO_ENTRADA,
                    'tipo_origen'         => $request->tipo_origen,
                    'cantidad'            => $item['cantidad'],
                    'costo_unitario'      => $item['costo_unitario'],
                    'id_usuario'          => auth()->id(),
                    'id_local_externo'    => $request->id_local_externo,
                    'observacion'         => $request->observacion,
                    'id_entrada'          => $entrada->id,
                ]);

                LoteInventario::create([
                    'id_detalle_producto' => $item['id_detalle_producto'],
                    'id_bodega'           => $idBodega,
                    'fecha_ingreso'       => now(),
                    'cantidad_disponible' => $item['cantidad'],
                    'costo_unitario'      => $item['costo_unitario'],
                    'precio_venta'        => $item['precio_venta'] ?: null,
                    'tipo_origen'         => $request->tipo_origen,
                    'id_movimiento_origen'=> $movimiento->id,
                    'id_entrada'          => $entrada->id,
                ]);
            }
        });

        $redirect = redirect()->route('inventario.index')
                             ->with('success', 'Entrada #' . $entradaId . ' registrada correctamente.');

        // Si pidió etiquetas, pasa la URL en sesión para mostrar el banner
        if ($request->boolean('imprimir_etiquetas')) {
            $redirect->with('etiquetas_url', route('codigos-barras.entrada', $entradaId));
        }

        return $redirect;
    }

    /**
     * Formulario de salida tipo carrito (escritorio)
     */
    public function createSalida()
    {
        $localesExternos = LocalExterno::where('activo', true)->orderBy('nombre_local')->get();
        $bodegas         = Bodega::where('activo', true)->orderBy('nombre')->get();
        $bodegaDefecto   = (int) old('id_bodega', auth()->user()->id_bodega ?? 1);
        $carritoInicial  = $this->carritoSalidaDesdeOld($bodegaDefecto);

        return view('inventario.salida', compact('localesExternos', 'bodegas', 'bodegaDefecto', 'carritoInicial'));
    }

    /**
     * Si la salida falló (stock, validación), reconstruye el carrito con lo que el
     * usuario ya había agregado para que no tenga que escanear todo de nuevo.
     */
    private function carritoSalidaDesdeOld(int $idBodega): array
    {
        $old = collect(session()->getOldInput('items', []))
            ->filter(fn($i) => !empty($i['id_detalle_producto']))
            ->keyBy(fn($i) => (int) $i['id_detalle_producto']);

        if ($old->isEmpty()) {
            return [];
        }

        return DetalleProducto::with(['producto', 'lotes' => fn($q) => $q->conStock($idBodega)])
            ->whereIn('id', $old->keys())
            ->get()
            ->map(fn($d) => [
                'id'            => $d->id,
                'codigo_barras' => $d->codigo_barras,
                'nombre'        => $d->producto->nombre,
                'talla'         => $d->talla,
                'stock_bodega'  => (int) $d->lotes->sum('cantidad_disponible'),
                'cantidad'      => max(1, (int) $old[$d->id]['cantidad']),
            ])
            ->values()
            ->all();
    }

    /**
     * Formulario de salida tipo carrito (POS móvil)
     */
    public function createSalidaMovil()
    {
        $localesExternos = LocalExterno::where('activo', true)->orderBy('nombre_local')->get(['id', 'nombre_local']);
        $bodegas         = Bodega::where('activo', true)->orderBy('nombre')->get();
        $bodegaDefecto   = (int) old('id_bodega', auth()->user()->id_bodega ?? 1);
        $carritoInicial  = $this->carritoSalidaDesdeOld($bodegaDefecto);

        return view('inventario.salida-movil', compact('localesExternos', 'bodegas', 'bodegaDefecto', 'carritoInicial'));
    }

    /**
     * Guarda la salida completa (carrito) en una sola transacción.
     * Descuenta FIFO únicamente de la bodega elegida.
     */
    public function storeSalida(Request $request)
    {
        $request->validate([
            'id_bodega'        => ['required', 'integer', Rule::exists('bodegas', 'id')->where('activo', true)],
            'tipo_operacion'   => 'required|in:Salida,Transferencia,Merma,Ajuste',
            'id_local_externo' => 'required_if:tipo_operacion,Transferencia|nullable|exists:locales_externos,id',
            'observacion'      => 'required|string|max:200',
            'items'            => 'required|array|min:1',
            'items.*.id_detalle_producto' => 'required|integer|distinct|exists:detalle_productos,id',
            'items.*.cantidad'            => 'required|integer|min:1',
        ], [
            'items.required'         => 'Agrega al menos un producto a la salida.',
            'observacion.required'   => 'Escribe el motivo de la salida.',
            'id_local_externo.required_if' => 'Selecciona el local externo de la transferencia.',
        ]);

        $idBodega = (int) $request->id_bodega;
        $idLocal  = $request->tipo_operacion === 'Transferencia' ? $request->id_local_externo : null;

        // Orden fijo por id para que dos salidas concurrentes bloqueen los lotes
        // en el mismo orden y no se produzca un deadlock.
        $items = collect($request->items)->sortBy('id_detalle_producto')->values();

        try {
            DB::transaction(function () use ($request, $items, $idBodega, $idLocal) {
                foreach ($items as $item) {
                    $idDetalle = (int) $item['id_detalle_producto'];
                    $cantidad  = (int) $item['cantidad'];

                    // lockForUpdate() evita que una venta/traslado/salida concurrente
                    // descuente el mismo stock dos veces.
                    $lotes = LoteInventario::where('id_detalle_producto', $idDetalle)
                                            ->conStock($idBodega)
                                            ->lockForUpdate()
                                            ->get();

                    $stockDisponible = $lotes->sum('cantidad_disponible');
                    if ($stockDisponible < $cantidad) {
                        $detalle = DetalleProducto::with('producto')->find($idDetalle);
                        $nombre  = $detalle?->producto?->nombre . ($detalle?->talla ? " ({$detalle->talla})" : '');
                        throw new \Exception("Stock insuficiente en esta bodega para \"{$nombre}\". Disponible: {$stockDisponible}, solicitado: {$cantidad}.");
                    }

                    $pendiente = $cantidad;

                    // Descontar FIFO y registrar un movimiento por cada lote con su costo real
                    foreach ($lotes as $lote) {
                        if ($pendiente <= 0) break;

                        $descontar = min($pendiente, $lote->cantidad_disponible);
                        $lote->decrement('cantidad_disponible', $descontar);

                        HistorialMovimiento::create([
                            'id_detalle_producto' => $idDetalle,
                            'tipo_operacion'      => $request->tipo_operacion,
                            'tipo_origen'         => $lote->tipo_origen,
                            'cantidad'            => $descontar,
                            'costo_unitario'      => $lote->costo_unitario,
                            'id_usuario'          => auth()->id(),
                            'id_local_externo'    => $idLocal,
                            'observacion'         => $request->observacion,
                            'id_bodega'           => $idBodega,
                        ]);

                        $pendiente -= $descontar;
                    }
                }
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $unidades = $items->sum(fn($i) => (int) $i['cantidad']);
        $mensaje  = "Salida registrada: {$items->count()} producto(s), {$unidades} unidad(es).";

        return $request->boolean('_from_movil')
            ? redirect()->route('ventas.movil')->with('success', $mensaje)
            : redirect()->route('inventario.index')->with('success', $mensaje);
    }

    /**
     * Historial de movimientos de un producto
     */
    public function historial(Request $request, DetalleProducto $detalle)
    {
        $detalle->load(['producto', 'lotes']);

        $query = HistorialMovimiento::with(['usuario', 'localExterno'])
                                    ->where('id_detalle_producto', $detalle->id);

        if ($request->filled('tipo')) {
            $query->where('tipo_operacion', $request->tipo);
        }

        $movimientos = $query->latest()->paginate(30)->withQueryString();

        // Estadísticas
        $stockActual   = $detalle->lotes->sum('cantidad_disponible');
        $totalEntradas = HistorialMovimiento::where('id_detalle_producto', $detalle->id)
                            ->where('tipo_operacion', 'Entrada')
                            ->sum('cantidad');
        $totalSalidas  = HistorialMovimiento::where('id_detalle_producto', $detalle->id)
                            ->whereIn('tipo_operacion', ['Salida', 'Venta'])
                            ->sum('cantidad');

        $lotesConCosto = $detalle->lotes->where('costo_unitario', '>', 0);
        $costoPromedio = $lotesConCosto->count()
            ? $lotesConCosto->avg('costo_unitario')
            : 0;

        return view('inventario.historial', compact(
            'detalle', 'movimientos',
            'stockActual', 'totalEntradas', 'totalSalidas', 'costoPromedio'
        ));
    }

    /**
     * Dado un código de barras, devuelve TODAS las tallas del mismo producto
     * con su stock en la bodega activa y, si ahí no hay, en qué otra(s) bodega(s)
     * sí hay — para poder ofrecer un Traslado en vez de ir directo a Préstamo.
     * Usado por el modo Consulta del POS móvil.
     */
    public function buscarTallas(Request $request)
    {
        $q = trim($request->get('q', ''));

        // $request->get('id_bodega', $default) solo usa el default si la clave está
        // AUSENTE; el JS del POS móvil manda "id_bodega=" vacío cuando el usuario no
        // tiene bodega asignada, así que hay que tratar explícitamente el vacío/0 como
        // "usar la bodega por defecto", no solo la ausencia del parámetro.
        $idBodegaParam = $request->get('id_bodega');
        $idBodega = ($idBodegaParam !== null && $idBodegaParam !== '')
            ? (int) $idBodegaParam
            : (int) (auth()->user()->id_bodega ?? 1);

        if ($q === '') {
            return response()->json(['encontrado' => false]);
        }

        // Buscar la variante escaneada (barcode exacto primero, luego nombre)
        $variante = DetalleProducto::with('producto')
            ->whereHas('producto', fn($pq) => $pq->activos())
            ->where('codigo_barras', $q)
            ->first();

        if (!$variante) {
            // Fallback: buscar por nombre de producto
            $variante = DetalleProducto::with('producto')
                ->whereHas('producto', fn($pq) => $pq->activos()->where('nombre', 'like', "%{$q}%"))
                ->first();
        }

        if (!$variante) {
            return response()->json(['encontrado' => false]);
        }

        // Todas las tallas del mismo producto, con el stock de TODAS las bodegas
        // (no solo la activa) para poder mostrar dónde más hay disponibilidad.
        $tallas = DetalleProducto::with(['producto', 'lotes' => fn($lq) => $lq->conStock()->with('bodega')])
            ->where('id_producto', $variante->id_producto)
            ->orderBy('talla')
            ->get()
            ->map(function ($d) use ($idBodega) {
                $lotesActivos = $d->lotes->where('id_bodega', $idBodega);
                $stock        = $lotesActivos->sum('cantidad_disponible');

                $otrasBodegas = $d->lotes->where('id_bodega', '!=', $idBodega)
                    ->groupBy('id_bodega')
                    ->map(fn($grupo) => [
                        'id_bodega' => $grupo->first()->id_bodega,
                        'nombre'    => $grupo->first()->bodega?->nombre ?? '—',
                        'cantidad'  => $grupo->sum('cantidad_disponible'),
                    ])
                    ->filter(fn($b) => $b['cantidad'] > 0)
                    ->values();

                $loteActivo = $lotesActivos->first() ?? $d->lotes->first();
                $precio     = $loteActivo?->precio_venta ?? $d->producto->precio_venta ?? 0;

                return [
                    'id'            => $d->id,
                    'talla'         => $d->talla ?? '—',
                    'codigo_barras' => $d->codigo_barras,
                    'stock'         => $stock,
                    'precio'        => (float) $precio,
                    'otras_bodegas' => $otrasBodegas,
                ];
            });

        return response()->json([
            'encontrado' => true,
            'producto'   => ['id' => $variante->id_producto, 'nombre' => $variante->producto->nombre],
            'tallas'     => $tallas,
        ]);
    }
}
