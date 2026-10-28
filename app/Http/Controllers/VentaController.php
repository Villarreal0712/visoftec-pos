<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Cliente;
use App\Models\CuadreCaja;
use App\Models\CuentaBancaria;
use App\Models\MovimientoCuentaBancaria;
use App\Models\DetalleProducto;
use App\Models\HistorialMovimiento;
use App\Models\HistorialVenta;
use App\Models\LiquidacionPrestamo;
use App\Models\LocalExterno;
use App\Models\LoteInventario;
use App\Models\PrestamoExterno;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Traits\HandlesTableFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VentaController extends Controller
{
    use HandlesTableFeatures;

    public function index(Request $request)
    {
        $esAdmin   = auth()->user()->can('roles.gestionar');
        $query     = Venta::with(['usuario', 'cliente', 'sucursal'])->latest();

        // Vendedor: solo ve ventas de su sucursal
        if (!$esAdmin) {
            $query->where('id_sucursal', auth()->user()->id_sucursal ?? 1);
        } else {
            // Admin: filtro opcional por sucursal
            if ($request->filled('sucursal')) {
                $query->where('id_sucursal', $request->sucursal);
            }
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }
        if ($request->filled('cliente')) {
            $query->whereHas('cliente', fn($q) => $q->where('nombre', 'like', "%{$request->cliente}%"));
        }
        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        $totalFiltrado = (clone $query)->activas()->sum('total');

        if ($request->boolean('exportar')) {
            $items = (clone $query)->get();
            return $this->exportarCsv('ventas', $items, [
                'ID'          => fn($v) => $v->id,
                'Fecha'       => fn($v) => $v->created_at->format('d/m/Y H:i'),
                'Cliente'     => fn($v) => $v->cliente?->nombre ?? 'Público general',
                'Método pago' => fn($v) => $v->metodo_pago,
                'Total'       => fn($v) => number_format($v->total, 2, '.', ''),
                'Crédito'     => fn($v) => $v->es_credito ? 'Sí' : 'No',
                'Saldo pend.' => fn($v) => number_format($v->saldo_pendiente, 2, '.', ''),
                'Estado'      => fn($v) => $v->anulada ? 'Anulada' : ($v->es_credito ? $v->estado_credito : 'Pagada'),
                'Vendedor'    => fn($v) => $v->usuario?->nombre ?? '—',
            ]);
        }

        $ventas    = $query->paginate($this->getPerPage())->withQueryString();
        $sucursales = $esAdmin ? Sucursal::orderBy('nombre')->get() : collect();

        return view('ventas.index', compact('ventas', 'totalFiltrado', 'sucursales', 'esAdmin'));
    }

    public function create()
    {
        $clientes = Cliente::activos()->orderBy('nombre')->get(['id', 'nombre', 'documento', 'limite_credito']);
        $localesExternos = LocalExterno::where('activo', true)->orderBy('nombre_local')->get(['id', 'nombre_local']);
        $puedeVenderPrestamo = auth()->user()->can('ventas.prestamo');
        $cajaAbierta = CuadreCaja::abiertos()
                                  ->where('id_sucursal', auth()->user()->id_sucursal ?? 1)
                                  ->first();
        $cuentas = CuentaBancaria::activas()->orderByRaw("tipo = 'Efectivo' desc")->orderBy('nombre')->get(['id', 'nombre', 'tipo']);
        $bodegaEsAcopio = Bodega::find(auth()->user()->id_bodega ?? 1)?->id_sucursal === null;

        return view('ventas.create', compact('clientes', 'localesExternos', 'puedeVenderPrestamo', 'cajaAbierta', 'cuentas', 'bodegaEsAcopio'));
    }

    public function createMovil()
    {
        $clientes = Cliente::activos()->orderBy('nombre')->get(['id', 'nombre', 'documento', 'limite_credito']);
        $localesExternos = LocalExterno::where('activo', true)->orderBy('nombre_local')->get(['id', 'nombre_local']);
        $puedeVenderPrestamo = auth()->user()->can('ventas.prestamo');
        $cajaAbierta = CuadreCaja::abiertos()
                                  ->where('id_sucursal', auth()->user()->id_sucursal ?? 1)
                                  ->first();
        $cuentas = CuentaBancaria::activas()->orderByRaw("tipo = 'Efectivo' desc")->orderBy('nombre')->get(['id', 'nombre', 'tipo']);
        $bodegaEsAcopio = Bodega::find(auth()->user()->id_bodega ?? 1)?->id_sucursal === null;

        // Pre-carga de producto desde la pantalla de Consulta
        $preloadBarcode = null;
        if (request()->filled('item')) {
            $detalle = \App\Models\DetalleProducto::find((int) request()->item);
            $preloadBarcode = $detalle?->codigo_barras;
        }

        return view('ventas.pos-movil', compact('clientes', 'localesExternos', 'puedeVenderPrestamo', 'cajaAbierta', 'preloadBarcode', 'cuentas', 'bodegaEsAcopio'));
    }

    public function consultaMovil()
    {
        $bodegaActivaId = auth()->user()->id_bodega;
        return view('ventas.pos-movil-consulta', compact('bodegaActivaId'));
    }

    public function hubMovil()
    {
        return view('ventas.pos-hub');
    }

    public function createMovilPrestamo()
    {
        abort_unless(auth()->user()->can('ventas.prestamo'), 403);

        $clientes        = Cliente::activos()->orderBy('nombre')->get(['id', 'nombre', 'documento', 'limite_credito']);
        $localesExternos = LocalExterno::where('activo', true)->orderBy('nombre_local')->get(['id', 'nombre_local']);
        $cajaAbierta     = CuadreCaja::abiertos()
                                      ->where('id_sucursal', auth()->user()->id_sucursal ?? 1)
                                      ->first();

        $preloadBarcode = null;
        if (request()->filled('item')) {
            $detalle        = \App\Models\DetalleProducto::find((int) request()->item);
            $preloadBarcode = $detalle?->codigo_barras;
        }

        return view('ventas.pos-movil-prestamo', compact('clientes', 'localesExternos', 'cajaAbierta', 'preloadBarcode'));
    }

    /**
     * Historial de ventas del día para el POS móvil (con opción de anular)
     */
    public function historialMovil(Request $request)
    {
        $esAdmin = auth()->user()->can('roles.gestionar');
        $fecha   = $request->filled('fecha') ? $request->fecha : now()->toDateString();

        $query = Venta::with([
            'cliente',
            'usuario',
            'historialVentas.lote.detalleProducto.producto',
        ])->latest();

        if (!$esAdmin) {
            $query->where('id_sucursal', auth()->user()->id_sucursal ?? 1);
        }

        if ($fecha) {
            $query->whereDate('created_at', $fecha);
        }

        if ($request->filled('referencia')) {
            $ref = $request->referencia;
            $query->whereHas('historialVentas.lote.detalleProducto', function ($q) use ($ref) {
                $q->where('codigo_barras', 'like', "%{$ref}%")
                  ->orWhereHas('producto', fn($pq) => $pq->where('nombre', 'like', "%{$ref}%"));
            });
        }

        $totalFiltrado = (clone $query)->where('anulada', false)->sum('total');
        $ventas        = $query->paginate(15)->withQueryString();

        return view('ventas.pos-movil-historial', compact('ventas', 'fecha', 'totalFiltrado'));
    }

    /**
     * Procesa y guarda una venta completa.
     * Implementa lógica FIFO para descuento de inventario.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id_detalle_producto' => 'required|exists:detalle_productos,id',
            'items.*.cantidad'            => 'required|integer|min:1',
            'items.*.precio_venta'        => 'required|numeric|min:0|max:99999999',
            'items.*.es_directo'          => 'boolean',
            'items.*.costo_directo'       => 'nullable|numeric|min:0|max:99999999',
            'items.*.id_local_externo'    => ['nullable', Rule::exists('locales_externos', 'id')->where('activo', true)],
            'metodo_pago'        => 'required_without:id_cuenta_bancaria|nullable|in:Efectivo,Tarjeta,Transferencia,Crédito,Mixto',
            'id_cuenta_bancaria' => ['nullable', Rule::exists('cuentas_bancarias', 'id')->where('activo', true)],
            // Pago mixto: cuánto se recibió en efectivo y a qué cuenta (tipo Banco) entró el resto
            'monto_efectivo'     => 'nullable|numeric|min:0|max:99999999',
            'id_cuenta_mixto'    => ['nullable', Rule::exists('cuentas_bancarias', 'id')->where('activo', true)->where('tipo', CuentaBancaria::TIPO_BANCO)],
            'id_cliente'  => 'nullable|exists:clientes,id',
            'es_credito'  => 'boolean',
        ]);

        if ($request->boolean('es_credito') && !$request->id_cliente) {
            return back()->withInput()->with('error', 'Para registrar una venta a crédito debe seleccionar un cliente.');
        }

        $request->validate([
            'dias_credito' => 'required_if:es_credito,true|nullable|integer|min:1|max:365',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $idBodegaActiva = auth()->user()->id_bodega ?? 1;

        // Las bodegas de acopio (sin sucursal) son solo para recibir/consolidar mercancía;
        // las ventas de ítems propios deben salir siempre de una bodega de sucursal.
        $hayItemsPropios = collect($request->items)->contains(fn($i) => !($i['es_directo'] ?? false));
        if ($hayItemsPropios) {
            $bodegaActiva = Bodega::find($idBodegaActiva);
            if ($bodegaActiva && $bodegaActiva->id_sucursal === null) {
                return back()->withInput()->with('error', "Tu bodega asignada (\"{$bodegaActiva->nombre}\") es de acopio y no se puede vender desde ahí. Pide al administrador que te asigne la bodega de tu sucursal.");
            }
            if (!$bodegaActiva || !$bodegaActiva->activo) {
                return back()->withInput()->with('error', 'Tu bodega asignada está desactivada. Pide al administrador que te asigne una bodega activa.');
            }
        }

        try { DB::transaction(function () use ($request, $idBodegaActiva) {
            // Validar stock — solo para ítems normales (no directos)
            // lockForUpdate() bloquea las filas de lotes durante la transacción
            // para evitar que ventas concurrentes lean el mismo stock y lo agoten doblemente.
            foreach ($request->items as $item) {
                if ($item['es_directo'] ?? false) {
                    if (!auth()->user()->can('ventas.prestamo')) {
                        throw new \Exception('No tienes permiso para registrar ventas de préstamo.');
                    }
                    if (empty($item['id_local_externo'])) {
                        $detalle = DetalleProducto::with('producto')->findOrFail($item['id_detalle_producto']);
                        throw new \Exception("Selecciona el local externo para la venta directa de {$detalle->producto->nombre}.");
                    }
                    continue;
                }

                $producto = DetalleProducto::with('producto')->findOrFail($item['id_detalle_producto']);
                $stockDisponible = LoteInventario::where('id_detalle_producto', $item['id_detalle_producto'])
                                                  ->conStock($idBodegaActiva)
                                                  ->lockForUpdate()
                                                  ->sum('cantidad_disponible');

                if ($stockDisponible < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para {$producto->producto->nombre} - {$producto->talla}. Disponible: {$stockDisponible}");
                }
            }

            $total = collect($request->items)->sum(fn($i) => $i['cantidad'] * $i['precio_venta']);
            $esCredito = $request->boolean('es_credito');

            // Si se seleccionó una cuenta bancaria específica, el método de pago se deriva
            // de su tipo (no se confía en lo que mande el cliente) para que el Cuadre de Caja
            // siga sumando exactamente por los mismos valores de siempre (Efectivo/Transferencia).
            // Una venta a crédito no mueve dinero real todavía, así que no se asocia a ninguna cuenta.
            $cuentaBancaria = null;
            $metodoPago = $request->metodo_pago ?: Venta::METODO_EFECTIVO;
            $montoEfectivo = null;
            $montoTransferencia = null;

            if ($esCredito) {
                $metodoPago = Venta::METODO_CREDITO;

                // Bloquear el cliente para que dos ventas a crédito casi simultáneas no lean
                // la misma deuda actual y ambas pasen el límite.
                $cliente = \App\Models\Cliente::lockForUpdate()->findOrFail($request->id_cliente);
                $deudaActual = Venta::conCreditoPendiente()->where('id_cliente', $cliente->id)->sum('saldo_pendiente');

                // Límite 0 = sin límite (igual que en el perfil del cliente, que solo muestra
                // la barra de uso cuando limite_credito > 0). Clientes nuevos quedan en 0 por defecto.
                if ($cliente->limite_credito > 0 && ($deudaActual + $total) > $cliente->limite_credito) {
                    throw new \Exception("\"{$cliente->nombre}\" superaría su límite de crédito. Límite: $" . number_format($cliente->limite_credito, 0, ',', '.') . ", deuda actual: $" . number_format($deudaActual, 0, ',', '.') . ".");
                }
            } elseif ($metodoPago === Venta::METODO_MIXTO) {
                // Mixto = parte en efectivo (cuadra en el cajón) + resto por transferencia a una
                // cuenta Banco (sube su saldo). Las dos partes deben ser mayores que cero; si no,
                // no es mixto y debe registrarse con un solo método.
                if (!$request->filled('id_cuenta_mixto')) {
                    throw new \Exception('En un pago mixto selecciona la cuenta que recibió la transferencia.');
                }
                $montoEfectivo = round((float) $request->monto_efectivo, 2);
                if ($montoEfectivo <= 0 || $montoEfectivo >= $total) {
                    throw new \Exception('En un pago mixto el efectivo debe ser mayor que $0 y menor que el total ($'
                        . number_format($total, 0, ',', '.') . '). Si todo fue en un solo medio, elige ese medio.');
                }
                $montoTransferencia = round($total - $montoEfectivo, 2);
                $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->id_cuenta_mixto);
            } elseif ($request->filled('id_cuenta_bancaria')) {
                $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->id_cuenta_bancaria);
                $metodoPago = $cuentaBancaria->tipo === CuentaBancaria::TIPO_EFECTIVO
                    ? Venta::METODO_EFECTIVO
                    : Venta::METODO_TRANSFERENCIA;
            }

            // Crear la venta
            $venta = Venta::create([
                'id_usuario'       => auth()->id(),
                'id_sucursal'      => auth()->user()->id_sucursal ?? 1,
                'id_cliente'       => $request->id_cliente,
                'es_credito'       => $esCredito,
                'metodo_pago'      => $metodoPago,
                'id_cuenta_bancaria' => $cuentaBancaria?->id,
                'monto_efectivo'     => $montoEfectivo,
                'monto_transferencia'=> $montoTransferencia,
                'fecha_vencimiento'=> $esCredito ? now()->addDays((int) $request->dias_credito) : null,
                'saldo_pendiente'  => $esCredito ? $total : 0,
                'estado_credito'   => $esCredito ? Venta::ESTADO_PARCIAL : Venta::ESTADO_PAGADO,
                'total'            => $total,
                'observaciones'    => $request->observaciones,
            ]);

            // Reflejar la venta en el saldo de la cuenta bancaria seleccionada (si no es Efectivo,
            // que sigue siendo controlado por el Cuadre de Caja).
            if ($cuentaBancaria && $cuentaBancaria->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                $cuentaBancaria->registrarMovimiento(
                    $venta->montoEnCuenta(),
                    MovimientoCuentaBancaria::TIPO_VENTA,
                    $venta->id,
                    $venta->esMixtoConReparto()
                        ? "Venta #{$venta->id} (pago mixto: parte por transferencia)"
                        : "Venta #{$venta->id}"
                );
            }

            // Procesar cada ítem
            foreach ($request->items as $item) {
                $detalle = DetalleProducto::with('producto')->findOrFail($item['id_detalle_producto']);

                // ── Venta directa (prestado): crear lote al vuelo y registrar préstamo ──
                if ($item['es_directo'] ?? false) {
                    $costoDirecto = (float) $item['costo_directo'];

                    $lote = LoteInventario::create([
                        'id_detalle_producto' => $detalle->id,
                        'fecha_ingreso'       => now(),
                        'cantidad_disponible' => 0,   // se consume de inmediato
                        'costo_unitario'      => $costoDirecto,
                        'precio_venta'        => $item['precio_venta'],
                        'tipo_origen'         => 'Directo',
                    ]);

                    $historial = HistorialVenta::create([
                        'id_venta'       => $venta->id,
                        'id_lote'        => $lote->id,
                        'cantidad'       => $item['cantidad'],
                        'precio_venta'   => $item['precio_venta'],
                        'costo_unitario' => $costoDirecto,
                    ]);

                    // Registrar la deuda con el local externo
                    PrestamoExterno::create([
                        'id_venta'            => $venta->id,
                        'id_historial_venta'  => $historial->id,
                        'id_local_externo'    => $item['id_local_externo'],
                        'id_detalle_producto' => $detalle->id,
                        'cantidad'            => $item['cantidad'],
                        'precio_venta'        => $item['precio_venta'],
                        'costo_acordado'      => $costoDirecto > 0 ? $costoDirecto : null,
                        'estado'              => PrestamoExterno::ESTADO_PENDIENTE,
                    ]);

                    HistorialMovimiento::create([
                        'id_detalle_producto' => $detalle->id,
                        'tipo_operacion'      => HistorialMovimiento::TIPO_VENTA,
                        'tipo_origen'         => 'Directo',
                        'cantidad'            => $item['cantidad'],
                        'costo_unitario'      => $costoDirecto,
                        'id_usuario'          => auth()->id(),
                        'observacion'         => "Venta directa #{$venta->id} (local externo #{$item['id_local_externo']})",
                        'id_local_externo'    => $item['id_local_externo'],
                    ]);

                    continue;
                }

                // ── Venta normal: FIFO ───────────────────────────────────────
                $cantidadPendiente = $item['cantidad'];

                // lockForUpdate() asegura que no haya otra transacción paralela
                // modificando los mismos lotes entre el check y el decrement.
                $lotes = LoteInventario::where('id_detalle_producto', $detalle->id)
                                       ->conStock($idBodegaActiva)
                                       ->lockForUpdate()
                                       ->get();

                foreach ($lotes as $lote) {
                    if ($cantidadPendiente <= 0) break;

                    $cantidadADescontar = min($cantidadPendiente, $lote->cantidad_disponible);

                    HistorialVenta::create([
                        'id_venta'       => $venta->id,
                        'id_lote'        => $lote->id,
                        'cantidad'       => $cantidadADescontar,
                        'precio_venta'   => $item['precio_venta'],
                        'costo_unitario' => $lote->costo_unitario,
                    ]);

                    $lote->decrement('cantidad_disponible', $cantidadADescontar);

                    HistorialMovimiento::create([
                        'id_detalle_producto' => $detalle->id,
                        'tipo_operacion'      => HistorialMovimiento::TIPO_VENTA,
                        'tipo_origen'         => $lote->tipo_origen,
                        'cantidad'            => $cantidadADescontar,
                        'costo_unitario'      => $lote->costo_unitario,
                        'id_usuario'          => auth()->id(),
                        'observacion'         => "Venta #{$venta->id}",
                        'id_bodega'           => $lote->id_bodega,
                    ]);

                    $cantidadPendiente -= $cantidadADescontar;
                }

                if ($cantidadPendiente > 0) {
                    throw new \Exception("Stock insuficiente para {$detalle->producto->nombre} - {$detalle->talla}: faltaron {$cantidadPendiente} unidad(es) al momento de descontar. Es posible que otra venta haya tomado el stock simultáneamente; intenta de nuevo.");
                }
            }

            session(['ultima_venta_id' => $venta->id]);
        }); } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $ventaId = session('ultima_venta_id');
        return redirect()->route('ventas.ticket', $ventaId)
                         ->with('success', 'Venta registrada correctamente.');
    }

    public function show(Venta $venta)
    {
        $venta->load(['usuario', 'cliente', 'historialVentas.lote.detalleProducto.producto', 'pagosCredito.usuario']);
        $cuentas = CuentaBancaria::activas()->orderByRaw("tipo = 'Efectivo' desc")->orderBy('nombre')->get(['id', 'nombre', 'tipo']);
        return view('ventas.show', compact('venta', 'cuentas'));
    }

    /**
     * Vista de ticket para imprimir
     */
    public function ticket(Venta $venta)
    {
        $venta->load(['usuario', 'cliente', 'historialVentas.lote.detalleProducto.producto']);
        return view('ventas.ticket', compact('venta'));
    }

    /**
     * Anula una venta y devuelve el stock (sin eliminar el registro)
     */
    public function destroy(Request $request, Venta $venta)
    {
        if ($venta->anulada) {
            return back()->with('error', 'Esta venta ya fue anulada.');
        }

        if ($venta->pagosCredito()->exists()) {
            return back()->with('error', 'No se puede anular una venta con abonos registrados.');
        }

        // El estado "liquidado" por préstamo individual nunca se asigna en el sistema actual
        // (las liquidaciones se registran de forma agregada por local externo, no por préstamo).
        // Por eso verificamos aquí si la deuda de esta venta ya quedó cubierta por pagos
        // registrados al local externo correspondiente, en vez de confiar en ese estado.
        $prestamosDeLaVenta = PrestamoExterno::where('id_venta', $venta->id)
            ->where('estado', PrestamoExterno::ESTADO_PENDIENTE)
            ->whereNotNull('costo_acordado')
            ->with('localExterno')
            ->get()
            ->groupBy('id_local_externo');

        foreach ($prestamosDeLaVenta as $idLocal => $prestamosLocal) {
            $costoDeEstaVenta = $prestamosLocal->sum('total_deuda');

            $totalPendienteLocal = PrestamoExterno::where('id_local_externo', $idLocal)
                ->where('estado', PrestamoExterno::ESTADO_PENDIENTE)
                ->whereNotNull('costo_acordado')
                ->get()
                ->sum('total_deuda');

            $totalLiquidadoLocal = LiquidacionPrestamo::where('id_local_externo', $idLocal)->sum('monto');
            $pendienteSinEstaVenta = $totalPendienteLocal - $costoDeEstaVenta;

            if ($totalLiquidadoLocal > $pendienteSinEstaVenta) {
                $nombreLocal = $prestamosLocal->first()->localExterno?->nombre_local ?? 'local externo';
                return back()->with('error', "No se puede anular: la deuda del préstamo con \"{$nombreLocal}\" de esta venta ya fue cubierta por pagos registrados. Contacta al administrador para un ajuste manual.");
            }
        }

        $request->validate([
            'motivo_anulacion' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $venta) {
            foreach ($venta->historialVentas()->with('lote')->get() as $item) {
                // Lotes directos (prestados): NO devolver inventario — la mercancía era ajena
                if ($item->lote->tipo_origen === 'Directo') {
                    // Marcar el préstamo como anulado
                    $prestamo = PrestamoExterno::where('id_historial_venta', $item->id);
                    $idLocalPrestamo = (clone $prestamo)->value('id_local_externo');
                    $prestamo->update(['estado' => PrestamoExterno::ESTADO_ANULADO]);

                    HistorialMovimiento::create([
                        'id_detalle_producto' => $item->lote->id_detalle_producto,
                        'tipo_operacion' => HistorialMovimiento::TIPO_AJUSTE,
                        'tipo_origen' => 'Directo',
                        'cantidad' => 0,
                        'costo_unitario' => $item->costo_unitario,
                        'id_usuario' => auth()->id(),
                        'observacion' => "Anulación venta directa #{$venta->id} — préstamo cancelado",
                        'id_local_externo' => $idLocalPrestamo,
                    ]);
                    continue;
                }

                // Lotes propios: devolver stock al inventario
                $item->lote->increment('cantidad_disponible', $item->cantidad);

                HistorialMovimiento::create([
                    'id_detalle_producto' => $item->lote->id_detalle_producto,
                    'tipo_operacion' => HistorialMovimiento::TIPO_AJUSTE,
                    'tipo_origen' => $item->lote->tipo_origen,
                    'cantidad' => $item->cantidad,
                    'costo_unitario' => $item->costo_unitario,
                    'id_usuario' => auth()->id(),
                    'observacion' => "Anulación venta #{$venta->id}",
                    'id_bodega' => $item->lote->id_bodega,
                ]);
            }

            // Marcar como anulada (no se elimina para conservar trazabilidad)
            $updateData = [
                'anulada'          => true,
                'motivo_anulacion' => $request->motivo_anulacion,
                'saldo_pendiente'  => 0,
            ];
            if ($venta->es_credito) {
                $updateData['estado_credito'] = Venta::ESTADO_PAGADO;
            }
            $venta->update($updateData);

            // Revertir el dinero de la cuenta bancaria para que su saldo siga reflejando la realidad.
            // Efectivo no se toca aquí: su saldo lo maneja el Cuadre de Caja, no este ledger.
            if ($venta->id_cuenta_bancaria) {
                $cuentaBancaria = CuentaBancaria::lockForUpdate()->find($venta->id_cuenta_bancaria);
                if ($cuentaBancaria && $cuentaBancaria->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                    // En una mixta solo se revierte lo que entró a la cuenta, no el total
                    $cuentaBancaria->registrarMovimiento(
                        $venta->montoEnCuenta(),
                        MovimientoCuentaBancaria::TIPO_RETIRO,
                        $venta->id,
                        "Anulación venta #{$venta->id}"
                    );
                }
            }
        });

        return redirect()->route('ventas.index')
                         ->with('success', 'Venta anulada y stock restaurado. El registro se conserva para trazabilidad.');
    }
}
