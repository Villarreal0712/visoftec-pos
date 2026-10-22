<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\CuentaBancaria;
use App\Models\MovimientoCuentaBancaria;
use App\Models\PagoCredito;
use App\Models\Venta;
use App\Traits\HandlesTableFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    use HandlesTableFeatures;

    public function index(Request $request)
    {
        $query = Cliente::activos();

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        if ($request->boolean('con_deuda')) {
            $query->whereHas('ventas', fn($q) => $q->conCreditoPendiente());
        }

        if ($request->boolean('exportar')) {
            $items = (clone $query)
                ->withCount(['ventas' => fn($q) => $q->activas()])
                ->withSum(['ventas as total_compras' => fn($q) => $q->activas()], 'total')
                ->withSum(['ventas as deuda_pendiente' => fn($q) => $q->conCreditoPendiente()], 'saldo_pendiente')
                ->orderBy('nombre')
                ->get();
            return $this->exportarCsv('clientes', $items, [
                'ID'              => fn($c) => $c->id,
                'Nombre'          => fn($c) => $c->nombre,
                'Documento'       => fn($c) => $c->documento,
                'Teléfono'        => fn($c) => $c->telefono ?? '—',
                'Correo'          => fn($c) => $c->correo ?? '—',
                'Total compras'   => fn($c) => number_format($c->total_compras ?? 0, 2, '.', ''),
                'Deuda pendiente' => fn($c) => number_format($c->deuda_pendiente ?? 0, 2, '.', ''),
                'Ventas'          => fn($c) => $c->ventas_count ?? 0,
            ]);
        }

        $clientes = $query->withCount(['ventas' => fn($q) => $q->activas()])
                          ->withSum(['ventas as total_compras' => fn($q) => $q->activas()], 'total')
                          ->orderBy('nombre')
                          ->paginate($this->getPerPage())
                          ->withQueryString();

        $cuentas = CuentaBancaria::activas()->orderByRaw("tipo = 'Efectivo' desc")->orderBy('nombre')->get(['id', 'nombre', 'tipo']);

        return view('clientes.index', compact('clientes', 'cuentas'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'documento' => 'required|string|max:20|unique:clientes,documento',
            'nombre' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:150',
            'correo' => 'nullable|email|max:100',
            'limite_credito' => 'nullable|numeric|min:0|max:99999999',
        ]);

        $cliente = Cliente::create($validated);

        return redirect()->route('clientes.show', $cliente)
                         ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Cliente $cliente)
    {
        $cliente->load(['ventas' => fn($q) => $q->latest()->with('historialVentas.lote.detalleProducto.producto')]);

        $ventas = $cliente->ventas;
        $ventasCredito = $ventas->filter(fn($v) => !$v->anulada && $v->es_credito && $v->estado_credito !== Venta::ESTADO_PAGADO);
        $totalDeuda = $ventasCredito->sum('saldo_pendiente');
        $saldoPendiente = $totalDeuda;
        $totalCompras = $ventas->where('anulada', false)->sum('total');

        $cuentas = CuentaBancaria::activas()->orderByRaw("tipo = 'Efectivo' desc")->orderBy('nombre')->get(['id', 'nombre', 'tipo']);

        return view('clientes.show', compact('cliente', 'ventas', 'ventasCredito', 'totalDeuda', 'saldoPendiente', 'totalCompras', 'cuentas'));
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'documento' => "required|string|max:20|unique:clientes,documento,{$cliente->id}",
            'nombre' => 'required|string|max:100',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:150',
            'correo' => 'nullable|email|max:100',
            'limite_credito' => 'nullable|numeric|min:0|max:99999999',
        ]);

        $cliente->update($validated);

        return redirect()->route('clientes.show', $cliente)
                         ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        if ($cliente->ventas()->exists()) {
            $cliente->update(['activo' => false]);
            return redirect()->route('clientes.index')
                             ->with('info', 'Cliente desactivado (tiene ventas asociadas).');
        }

        $cliente->delete();
        return redirect()->route('clientes.index')
                         ->with('success', 'Cliente eliminado correctamente.');
    }

    /**
     * Creación rápida de cliente desde el POS — respuesta JSON
     */
    public function storeRapido(Request $request)
    {
        $validated = $request->validate([
            'documento'      => 'required|string|max:20|unique:clientes,documento',
            'nombre'         => 'required|string|max:100',
            'telefono'       => 'nullable|string|max:50',
            'correo'         => 'nullable|email|max:100',
            'limite_credito' => 'nullable|numeric|min:0|max:99999999',
        ]);

        $cliente = Cliente::create($validated);

        return response()->json([
            'id'        => $cliente->id,
            'nombre'    => $cliente->nombre,
            'documento' => $cliente->documento,
        ], 201);
    }

    /**
     * Créditos pendientes de un cliente — respuesta JSON para el modal de abono
     */
    public function creditosPendientesJson(Cliente $cliente)
    {
        $creditos = $cliente->ventas()
            ->activas()
            ->where('es_credito', true)
            ->whereIn('estado_credito', [Venta::ESTADO_PARCIAL, Venta::ESTADO_MORA])
            ->select('id', 'total', 'saldo_pendiente', 'estado_credito', 'fecha_vencimiento', 'created_at')
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(fn($v) => [
                'id'          => $v->id,
                'numero'      => '#' . str_pad($v->id, 5, '0', STR_PAD_LEFT),
                'fecha'       => $v->created_at->format('d/m/Y'),
                'vencimiento' => $v->fecha_vencimiento?->format('d/m/Y') ?? '—',
                'vencido'     => $v->fecha_vencimiento && $v->fecha_vencimiento < now(),
                'total'       => (float) $v->total,
                'saldo'       => (float) $v->saldo_pendiente,
                'estado'      => $v->estado_credito,
                'url_abonar'  => route('ventas.abonar', $v),
            ]);

        return response()->json([
            'cliente'     => $cliente->nombre,
            'total_deuda' => (float) $cliente->saldo_pendiente_total,
            'creditos'    => $creditos,
        ]);
    }

    /**
     * Distribuye un pago global entre todos los créditos pendientes del cliente (FIFO por vencimiento)
     */
    public function registrarAbonoMixto(Request $request, Cliente $cliente)
    {
        $request->validate([
            'monto_total'        => 'required|numeric|min:0.01|max:99999999',
            'metodo'             => 'required_without:id_cuenta_bancaria|nullable|in:Efectivo,Tarjeta,Transferencia',
            'id_cuenta_bancaria' => ['nullable', Rule::exists('cuentas_bancarias', 'id')->where('activo', true)],
            'observacion'        => 'nullable|string|max:200',
        ]);

        try {
            $pagos = DB::transaction(function () use ($request, $cliente) {
                // Bloquear las ventas del cliente antes de sumar su deuda: si dos abonos a este
                // mismo cliente llegan casi simultáneos, el segundo debe ver la deuda ya reducida
                // por el primero, no un saldo obsoleto que permitiría sobrepagar el crédito.
                $ventas = $cliente->ventas()
                    ->activas()
                    ->where('es_credito', true)
                    ->whereIn('estado_credito', [Venta::ESTADO_PARCIAL, Venta::ESTADO_MORA])
                    ->lockForUpdate()
                    ->orderBy('fecha_vencimiento')
                    ->get();

                if ($ventas->isEmpty()) {
                    throw new \Exception('No hay créditos pendientes.');
                }

                $totalDeuda = $ventas->sum('saldo_pendiente');
                if ((float) $request->monto_total > $totalDeuda) {
                    throw new \Exception('El monto no puede superar la deuda total ($' . number_format($totalDeuda, 0, ',', '.') . ').');
                }

                // El método de pago se deriva de la cuenta elegida, igual que en ventas,
                // para que el Cuadre de Caja siga sumando por los mismos valores de siempre.
                $cuentaBancaria = null;
                $metodo = $request->metodo ?: Venta::METODO_EFECTIVO;

                if ($request->filled('id_cuenta_bancaria')) {
                    $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->id_cuenta_bancaria);
                    $metodo = $cuentaBancaria->tipo === CuentaBancaria::TIPO_EFECTIVO
                        ? Venta::METODO_EFECTIVO
                        : Venta::METODO_TRANSFERENCIA;
                }

                $restante = (float) $request->monto_total;
                $creados  = [];

                foreach ($ventas as $venta) {
                    if ($restante <= 0) break;

                    $abono = min($restante, (float) $venta->saldo_pendiente);

                    $creados[] = PagoCredito::create([
                        'id_venta'           => $venta->id,
                        'id_usuario'         => auth()->id(),
                        'id_sucursal'        => auth()->user()->id_sucursal ?? 1,
                        'monto'              => $abono,
                        'metodo'             => $metodo,
                        'id_cuenta_bancaria' => $cuentaBancaria?->id,
                        'observacion'        => $request->observacion ?? null,
                    ]);

                    $venta->actualizarEstadoCredito();
                    $restante -= $abono;
                }

                // Un abono mixto reparte un solo pago entre varias ventas, así que el movimiento
                // de la cuenta se registra una sola vez por el total (no tiene una venta única asociada).
                if ($cuentaBancaria && $cuentaBancaria->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                    $cuentaBancaria->registrarMovimiento(
                        (float) $request->monto_total,
                        MovimientoCuentaBancaria::TIPO_ABONO,
                        null,
                        "Abono mixto de {$cliente->nombre} distribuido en " . count($creados) . " venta(s)"
                    );
                }

                return $creados;
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $pagoIds = collect($pagos)->pluck('id')->join(',');

        return response()->json([
            'message'     => 'Abono distribuido en ' . count($pagos) . ' venta(s).',
            'pagos_count' => count($pagos),
            'ticket_url'  => route('clientes.ticket-mixto', $cliente) . '?pagos=' . $pagoIds,
        ]);
    }

    /**
     * Comprobante consolidado para abono mixto
     */
    public function ticketAbonoMixto(Cliente $cliente, Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', $request->get('pagos', ''))));

        if (empty($ids)) abort(404);

        $pagos = PagoCredito::with(['venta', 'usuario'])
            ->whereIn('id', $ids)
            ->whereHas('venta', fn($q) => $q->where('id_cliente', $cliente->id))
            ->orderBy('id')
            ->get();

        if ($pagos->isEmpty()) abort(404);

        $comercio = Configuracion::first();

        return view('ventas.ticket-abono-mixto', compact('pagos', 'cliente', 'comercio'));
    }

    /**
     * Registra un abono a una venta a crédito
     */
    public function registrarAbono(Request $request, Venta $venta)
    {
        $validated = $request->validate([
            'monto'              => 'required|numeric|min:0.01|max:99999999',
            'metodo'             => 'required_without:id_cuenta_bancaria|nullable|in:Efectivo,Tarjeta,Transferencia',
            'id_cuenta_bancaria' => ['nullable', Rule::exists('cuentas_bancarias', 'id')->where('activo', true)],
            'observacion'        => 'nullable|string|max:200',
        ]);

        try {
            $pago = DB::transaction(function () use ($validated, $venta) {
                // Bloquear la venta antes de validar el monto contra su saldo pendiente: si dos
                // abonos a la misma venta llegan casi simultáneos, el segundo debe ver el saldo
                // ya reducido por el primero, no un saldo obsoleto que permitiría sobrepagar.
                $ventaLock = Venta::lockForUpdate()->findOrFail($venta->id);

                if ((float) $validated['monto'] > (float) $ventaLock->saldo_pendiente) {
                    throw new \Exception('El monto no puede superar el saldo pendiente ($' . number_format($ventaLock->saldo_pendiente, 0, ',', '.') . ').');
                }

                // El método de pago se deriva de la cuenta elegida (no de lo que mande el cliente),
                // igual que en ventas, para que el Cuadre de Caja siga sumando por los mismos valores.
                $cuentaBancaria = null;
                $metodo = $validated['metodo'] ?? Venta::METODO_EFECTIVO;

                if (!empty($validated['id_cuenta_bancaria'])) {
                    $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($validated['id_cuenta_bancaria']);
                    $metodo = $cuentaBancaria->tipo === CuentaBancaria::TIPO_EFECTIVO
                        ? Venta::METODO_EFECTIVO
                        : Venta::METODO_TRANSFERENCIA;
                }

                $pago = PagoCredito::create([
                    'id_venta'           => $ventaLock->id,
                    'id_usuario'         => auth()->id(),
                    // Sucursal donde entra la plata (la de quien recibe), no la de la venta
                    'id_sucursal'        => auth()->user()->id_sucursal ?? 1,
                    'monto'              => $validated['monto'],
                    'metodo'             => $metodo,
                    'id_cuenta_bancaria' => $cuentaBancaria?->id,
                    'observacion'        => $validated['observacion'] ?? null,
                ]);

                if ($cuentaBancaria && $cuentaBancaria->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                    $cuentaBancaria->registrarMovimiento(
                        (float) $validated['monto'],
                        MovimientoCuentaBancaria::TIPO_ABONO,
                        $ventaLock->id,
                        "Abono venta #{$ventaLock->id}"
                    );
                }

                $ventaLock->actualizarEstadoCredito();

                return $pago;
            });
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message'    => 'Abono registrado correctamente.',
                'ticket_url' => route('abonos.ticket', $pago),
            ]);
        }

        return redirect()->route('abonos.ticket', $pago)
                         ->with('success', 'Abono registrado correctamente.');
    }

    /**
     * Anula un abono y recalcula el saldo de la venta
     */
    public function destroyAbono(PagoCredito $pago)
    {
        try {
            DB::transaction(function () use ($pago) {
                // Bloquear el pago y la venta antes de decidir si se revierte: evita que un
                // doble clic o un reintento de red reviertan el movimiento bancario dos veces.
                $pagoLock = PagoCredito::lockForUpdate()->findOrFail($pago->id);
                $venta    = Venta::lockForUpdate()->findOrFail($pagoLock->id_venta);

                if ($venta->anulada) {
                    throw new \Exception('La venta ya está anulada.');
                }

                // Revertir el dinero de la cuenta bancaria para que su saldo siga reflejando la realidad.
                // Efectivo no se toca aquí: su saldo lo maneja el Cuadre de Caja, no este ledger.
                if ($pagoLock->id_cuenta_bancaria) {
                    $cuentaBancaria = CuentaBancaria::lockForUpdate()->find($pagoLock->id_cuenta_bancaria);
                    if ($cuentaBancaria && $cuentaBancaria->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                        $cuentaBancaria->registrarMovimiento(
                            (float) $pagoLock->monto,
                            MovimientoCuentaBancaria::TIPO_RETIRO,
                            $pagoLock->id_venta,
                            "Anulación abono #{$pagoLock->id}"
                        );
                    }
                }

                $pagoLock->delete();
                $venta->actualizarEstadoCredito();
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Este abono ya había sido anulado.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Abono anulado. El saldo de la venta fue recalculado.');
    }

    /**
     * Ticket de comprobante de abono
     */
    public function ticketAbono(PagoCredito $pago)
    {
        $pago->load([
            'venta.cliente',
            'venta.historialVentas.lote.detalleProducto.producto',
            'venta.pagosCredito',
            'usuario',
        ]);

        return view('ventas.ticket-abono', compact('pago'));
    }

    /**
     * Estado de cuenta del cliente (para imprimir)
     */
    public function estadoCuenta(Cliente $cliente)
    {
        $ventasCredito = $cliente->ventas()
                                 ->activas()
                                 ->where('es_credito', true)
                                 ->with(['historialVentas.lote.detalleProducto.producto', 'pagosCredito'])
                                 ->latest()
                                 ->get();

        $todosAbonos    = $ventasCredito->flatMap(fn($v) => $v->pagosCredito);
        $saldoPendiente = $ventasCredito->sum('saldo_pendiente');

        return view('clientes.estado-cuenta', compact('cliente', 'ventasCredito', 'todosAbonos', 'saldoPendiente'));
    }
}
