<?php

namespace App\Http\Controllers;

use App\Models\CuadreCaja;
use App\Models\CuentaBancaria;
use App\Models\Sucursal;
use App\Models\GastoExterno;
use App\Models\LiquidacionPrestamo;
use App\Models\PagoCredito;
use App\Models\Venta;
use App\Traits\HandlesTableFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuadreCajaController extends Controller
{
    use HandlesTableFeatures;

    public function index(Request $request)
    {
        $esAdmin = auth()->user()->can('roles.gestionar');
        $query   = CuadreCaja::with(['usuario', 'sucursal'])->latest('fecha_apertura');

        // Vendedor: solo ve cuadres de su sucursal
        if (!$esAdmin) {
            $query->where('id_sucursal', $this->sucursalUsuario());
        } else {
            if ($request->filled('sucursal')) {
                $query->where('id_sucursal', $request->sucursal);
            }
        }

        if ($request->boolean('exportar')) {
            $items = (clone $query)->get();
            return $this->exportarCsv('cuadre_caja', $items, [
                'ID'            => fn($c) => $c->id,
                'Local'         => fn($c) => $c->sucursal?->nombre ?? '—',
                'Fecha apertura'=> fn($c) => $c->fecha_apertura->format('d/m/Y H:i'),
                'Fecha cierre'  => fn($c) => $c->fecha_cierre?->format('d/m/Y H:i') ?? '—',
                'Saldo inicial' => fn($c) => number_format($c->saldo_inicial, 2, '.', ''),
                'Saldo final'   => fn($c) => $c->saldo_final !== null ? number_format($c->saldo_final, 2, '.', '') : '—',
                'Estado'        => fn($c) => $c->abierto ? 'Abierto' : 'Cerrado',
                'Usuario'       => fn($c) => $c->usuario?->nombre ?? '—',
            ]);
        }

        $cuadres    = $query->paginate($this->getPerPage())->withQueryString();
        $sucursales = $esAdmin ? Sucursal::orderBy('nombre')->get() : collect();

        return view('cuadre-caja.index', compact('cuadres', 'sucursales', 'esAdmin'));
    }

    /**
     * Inicia un nuevo cuadre/apertura de caja
     */
    public function create()
    {
        $idSucursal  = $this->sucursalUsuario();
        $cajaAbierta = CuadreCaja::abiertos()
                                  ->where('id_sucursal', $idSucursal)
                                  ->first();

        if ($cajaAbierta) {
            return redirect()->route('cuadre-caja.show', $cajaAbierta)
                             ->with('info', 'Ya hay una caja abierta para este local.');
        }

        $sucursal = auth()->user()->sucursal;
        return view('cuadre-caja.create', compact('sucursal'));
    }

    /**
     * Abre una nueva caja
     */
    public function store(Request $request)
    {
        $request->validate([
            'saldo_inicial' => 'required|numeric|min:0|max:99999999',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $idSucursal = $this->sucursalUsuario();

        // Chequeo rápido para dar un mensaje amigable en el caso normal; el índice único
        // de la BD (columna generada sucursal_si_abierta) es la garantía real contra la
        // carrera de dos aperturas casi simultáneas que este chequeo solo no puede evitar.
        $cajaExistente = CuadreCaja::abiertos()->where('id_sucursal', $idSucursal)->first();
        if ($cajaExistente) {
            return redirect()->route('cuadre-caja.show', $cajaExistente)
                             ->with('info', 'Ya hay una caja abierta para este local.');
        }

        try {
            $cuadre = CuadreCaja::create([
                'id_usuario'     => auth()->id(),
                'id_sucursal'    => $idSucursal,
                'fecha_apertura' => now(),
                'saldo_inicial'  => $request->saldo_inicial,
                'estado'         => CuadreCaja::ESTADO_ABIERTO,
                'observaciones'  => $request->observaciones,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $cajaExistente = CuadreCaja::abiertos()->where('id_sucursal', $idSucursal)->first();
            if ($cajaExistente) {
                return redirect()->route('cuadre-caja.show', $cajaExistente)
                                 ->with('info', 'Ya hay una caja abierta para este local.');
            }
            return redirect()->route('cuadre-caja.index')
                             ->with('error', 'No se pudo abrir la caja. Intenta de nuevo.');
        }

        return redirect()->route('cuadre-caja.show', $cuadre)
                         ->with('success', 'Caja abierta correctamente.');
    }

    public function show(CuadreCaja $cuadreCaja)
    {
        $cuadreCaja->load('usuario');

        $totales = $this->totalesDelCuadre($cuadreCaja);
        $detalle = $this->detalleDelCuadre($cuadreCaja);

        return view('cuadre-caja.show', ['cuadreCaja' => $cuadreCaja] + $totales + $detalle);
    }

    /**
     * Cierra la caja con el arqueo final
     */
    public function cerrar(Request $request, CuadreCaja $cuadreCaja)
    {
        $request->validate([
            'efectivo_contado'       => 'required|numeric|min:0|max:99999999',
            'transferencia_contada'  => 'nullable|numeric|min:0|max:99999999',
            'justificacion'          => 'nullable|string|max:500',
            'desglose_billetes'      => 'nullable|array',
            'observaciones'          => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($request, $cuadreCaja) {
                // Bloquear la caja antes de comprobar su estado: evita que dos cierres casi
                // simultáneos (doble clic) pasen ambos el chequeo y uno sobrescriba al otro.
                $caja = CuadreCaja::lockForUpdate()->findOrFail($cuadreCaja->id);

                if ($caja->estado !== CuadreCaja::ESTADO_ABIERTO) {
                    throw new \Exception('Esta caja ya fue cerrada.');
                }

                $fin = now();
                $t   = $this->calcularTotales($caja->fecha_apertura, $fin, $caja->sucursalDelDetalle(), (float) $caja->saldo_inicial);

                $efectivoContado      = (float) $request->efectivo_contado;
                $transferenciaContada = (float) ($request->transferencia_contada ?? 0);
                $totalContado         = $efectivoContado + $transferenciaContada;
                $diferencia           = $totalContado - $t['totalSistema'];

                $caja->update([
                    'fecha_cierre'           => $fin,
                    'ventas_efectivo'        => $t['ventasEfectivo'],
                    'ventas_tarjeta'         => $t['ventasTarjeta'],
                    'ventas_transferencia'   => $t['ventasTransferencia'],
                    'ventas_mixto'           => $t['ventasMixto'],
                    'pagos_credito'          => $t['pagosCredito'],
                    'gastos'                 => $t['gastos'],
                    'liquidaciones_externas' => $t['liquidacionesExternas'],
                    'total_sistema'          => $t['totalSistema'],
                    'efectivo_contado'       => $efectivoContado,
                    'transferencia_contada'  => $transferenciaContada,
                    'total_contado'          => $totalContado,
                    'diferencia'             => $diferencia,
                    'justificacion'          => $request->justificacion,
                    'estado'                 => CuadreCaja::ESTADO_CERRADO,
                    'desglose_billetes'      => $request->desglose_billetes,
                    'observaciones'          => $request->observaciones,
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('cuadre-caja.show', $cuadreCaja)->with('info', $e->getMessage());
        }

        return redirect()->route('cuadre-caja.show', $cuadreCaja)
                         ->with('success', 'Caja cerrada correctamente.');
    }

    /**
     * Imprime el cuadre de caja
     */
    public function imprimir(CuadreCaja $cuadreCaja)
    {
        $cuadreCaja->load('usuario');

        $totales = $this->totalesDelCuadre($cuadreCaja);
        $detalle = $this->detalleDelCuadre($cuadreCaja);
        $detalle['ventas'] = $detalle['ventas']->where('anulada', false);

        return view('cuadre-caja.imprimir', ['cuadreCaja' => $cuadreCaja] + $totales + $detalle);
    }

    /**
     * Totales del cuadre. Un cuadre cerrado muestra lo que quedó guardado al cerrarlo
     * (no cambia si después se anula una venta o se reasignan sucursales); uno abierto
     * se calcula en vivo.
     */
    private function totalesDelCuadre(CuadreCaja $cuadre): array
    {
        if ($cuadre->estado !== CuadreCaja::ESTADO_CERRADO) {
            return $this->calcularTotales($cuadre->fecha_apertura, now(), $cuadre->sucursalDelDetalle(), (float) $cuadre->saldo_inicial);
        }

        return [
            'ventasEfectivo'        => (float) $cuadre->ventas_efectivo,
            'ventasTarjeta'         => (float) $cuadre->ventas_tarjeta,
            'ventasTransferencia'   => (float) $cuadre->ventas_transferencia,
            'ventasMixto'           => (float) ($cuadre->ventas_mixto ?? 0),
            'pagosCredito'          => (float) $cuadre->pagos_credito,
            'gastos'                => (float) $cuadre->gastos,
            'liquidacionesExternas' => (float) ($cuadre->liquidaciones_externas ?? 0),
            'totalSistema'          => (float) $cuadre->total_sistema,
            // El reparto efectivo/electrónico no se guarda al cerrar (solo se usa con la caja
            // abierta); estos valores mantienen las mismas claves para las vistas.
            'gastosEfectivo'          => (float) $cuadre->gastos,
            'mixtoEfectivo'           => 0.0,
            'mixtoElectronico'        => (float) ($cuadre->ventas_mixto ?? 0),
            'pagosCreditoEfectivo'    => (float) $cuadre->pagos_credito,
            'pagosCreditoElectronico' => 0.0,
        ];
    }

    /**
     * Totales del período para una sucursal ($idSucursal null = todas las sucursales).
     */
    private function calcularTotales($inicio, $fin, ?int $idSucursal, float $saldoInicial): array
    {
        $ventas = fn(string $metodo) => Venta::activas()->entreFechas($inicio, $fin)
            ->when($idSucursal, fn($q) => $q->where('id_sucursal', $idSucursal))
            ->where('metodo_pago', $metodo)
            ->where('es_credito', false)
            ->sum('total');

        $t = [
            'ventasEfectivo'      => (float) $ventas(Venta::METODO_EFECTIVO),
            'ventasTarjeta'       => (float) $ventas(Venta::METODO_TARJETA),
            'ventasTransferencia' => (float) $ventas(Venta::METODO_TRANSFERENCIA),
            'ventasMixto'         => (float) $ventas('Mixto'),
            // Abonos recibidos en este local (aunque la venta sea de otra sucursal: la plata entró aquí)
            'pagosCredito'        => (float) PagoCredito::whereBetween('created_at', [$inicio, $fin])
                ->when($idSucursal, fn($q) => $q->where('id_sucursal', $idSucursal))
                ->sum('monto'),
            'gastos'              => (float) GastoExterno::entreFechas($inicio, $fin)
                ->when($idSucursal, fn($q) => $q->where('id_sucursal', $idSucursal))
                ->sum('valor'),
            // Pagos a locales externos (liquidaciones de préstamos)
            'liquidacionesExternas' => (float) LiquidacionPrestamo::entreFechas($inicio, $fin)->sum('monto'),
        ];

        // Solo los gastos pagados en Efectivo (o sin cuenta asociada) afectan el efectivo esperado;
        // los pagados desde una cuenta bancaria ya salieron de esa cuenta, no de la caja física.
        $gastosEfectivo = (float) $this->gastosQuePesanEnEfectivo($inicio, $fin, $idSucursal)->sum('valor');

        $t['totalSistema'] = $saldoInicial
            + $t['ventasEfectivo'] + $t['ventasTarjeta'] + $t['ventasTransferencia'] + $t['ventasMixto']
            + $t['pagosCredito'] - $gastosEfectivo - $t['liquidacionesExternas'];

        // ── Reparto efectivo / electrónico (solo para la "Referencia del sistema" del cierre).
        // No cambia totalSistema ni la diferencia: solo dice cuánto debería haber en el cajón y
        // cuánto en transferencias.
        $t['gastosEfectivo'] = $gastosEfectivo;
        // Parte en efectivo de las ventas mixtas que guardan su reparto; las mixtas viejas (sin
        // reparto) se siguen contando completas como electrónico, como antes.
        $t['mixtoEfectivo'] = (float) Venta::activas()->entreFechas($inicio, $fin)
            ->when($idSucursal, fn($q) => $q->where('id_sucursal', $idSucursal))
            ->where('metodo_pago', Venta::METODO_MIXTO)
            ->where('es_credito', false)
            ->whereNotNull('monto_transferencia')
            ->sum('monto_efectivo');
        $t['mixtoElectronico'] = $t['ventasMixto'] - $t['mixtoEfectivo'];
        // Abonos en efectivo = sin cuenta (histórico) o a una cuenta tipo Efectivo
        $t['pagosCreditoEfectivo'] = (float) PagoCredito::whereBetween('created_at', [$inicio, $fin])
            ->when($idSucursal, fn($q) => $q->where('id_sucursal', $idSucursal))
            ->where(fn($q) => $q->whereNull('id_cuenta_bancaria')
                ->orWhereHas('cuentaBancaria', fn($c) => $c->where('tipo', CuentaBancaria::TIPO_EFECTIVO)))
            ->sum('monto');
        $t['pagosCreditoElectronico'] = $t['pagosCredito'] - $t['pagosCreditoEfectivo'];

        return $t;
    }

    /**
     * Ventas, abonos, gastos y liquidaciones del período del cuadre, de su sucursal
     * (o de todas, para los cuadres de antes de manejar sucursales).
     */
    private function detalleDelCuadre(CuadreCaja $cuadre): array
    {
        $inicio     = $cuadre->fecha_apertura;
        $fin        = $cuadre->fecha_cierre ?? now();
        $idSucursal = $cuadre->sucursalDelDetalle();

        return [
            'ventas' => Venta::with(['cliente', 'cuentaBancaria'])
                ->when($idSucursal, fn($q) => $q->where('id_sucursal', $idSucursal))
                ->entreFechas($inicio, $fin)
                ->latest()
                ->get(),
            'pagosDetalle' => PagoCredito::with(['venta.cliente'])
                ->when($idSucursal, fn($q) => $q->where('id_sucursal', $idSucursal))
                ->whereBetween('created_at', [$inicio, $fin])
                ->get(),
            'gastosDetalle' => GastoExterno::with('usuario')
                ->when($idSucursal, fn($q) => $q->where('id_sucursal', $idSucursal))
                ->entreFechas($inicio, $fin)
                ->get(),
            'liquidacionesDetalle' => LiquidacionPrestamo::with(['localExterno', 'usuario'])
                ->entreFechas($inicio, $fin)
                ->get(),
            // Desglose informativo: cuánto quedó en cada cuenta bancaria dinámica (ventas + abonos).
            // No participa en total_sistema/diferencia — es solo para visibilidad del usuario.
            'desglosePorCuenta' => $this->calcularDesglosePorCuenta($inicio, $fin, $idSucursal),
        ];
    }

    /**
     * Sucursal del usuario actual con el mismo respaldo que usan VentaController y
     * GastoController (?? 1). Sin esto, un usuario sin sucursal abría la caja con
     * id_sucursal NULL y el cuadre no encontraba ninguna venta (las ventas quedan en 1).
     */
    private function sucursalUsuario(): int
    {
        return auth()->user()->id_sucursal ?? 1;
    }

    /**
     * Gastos del período que deben descontarse del efectivo esperado: los que no tienen
     * cuenta bancaria asociada (comportamiento histórico) o cuya cuenta es de tipo Efectivo.
     * Los gastos pagados desde una cuenta de tipo Banco ya salieron de esa cuenta (vía el
     * ledger de movimientos_cuenta_bancaria), no de la caja física.
     */
    private function gastosQuePesanEnEfectivo($inicio, $fin, ?int $idSucursal = null)
    {
        $query = GastoExterno::entreFechas($inicio, $fin)
            ->where(function ($q) {
                $q->whereNull('id_cuenta_bancaria')
                  ->orWhereHas('cuentaBancaria', fn($q2) => $q2->where('tipo', CuentaBancaria::TIPO_EFECTIVO));
            });

        if ($idSucursal) {
            $query->where('id_sucursal', $idSucursal);
        }

        return $query;
    }

    /**
     * Cuánto dinero (ventas + abonos a crédito, menos gastos) quedó en cada cuenta bancaria
     * dinámica dentro del período. Agrupa por ID de cuenta (no por nombre) para no mezclar
     * cuentas que por error terminen con el mismo nombre. Es puramente informativo.
     */
    private function calcularDesglosePorCuenta($inicio, $fin, ?int $idSucursal = null): \Illuminate\Support\Collection
    {
        $ventasQuery = Venta::activas()->entreFechas($inicio, $fin)
                             ->where('es_credito', false)
                             ->whereNotNull('id_cuenta_bancaria');
        if ($idSucursal) {
            $ventasQuery->where('id_sucursal', $idSucursal);
        }
        $ventasPorCuenta = $ventasQuery->with('cuentaBancaria')->get()
            ->filter(fn($v) => $v->cuentaBancaria?->tipo === CuentaBancaria::TIPO_BANCO)
            ->groupBy('id_cuenta_bancaria');

        $pagosQuery = PagoCredito::whereBetween('created_at', [$inicio, $fin])
                                  ->whereNotNull('id_cuenta_bancaria');
        if ($idSucursal) {
            $pagosQuery->where('id_sucursal', $idSucursal);
        }
        $pagosPorCuenta = $pagosQuery->with('cuentaBancaria')->get()
            ->filter(fn($p) => $p->cuentaBancaria?->tipo === CuentaBancaria::TIPO_BANCO)
            ->groupBy('id_cuenta_bancaria');

        $gastosQuery = GastoExterno::entreFechas($inicio, $fin)->whereNotNull('id_cuenta_bancaria');
        if ($idSucursal) {
            $gastosQuery->where('id_sucursal', $idSucursal);
        }
        $gastosPorCuenta = $gastosQuery->with('cuentaBancaria')->get()
            ->filter(fn($g) => $g->cuentaBancaria?->tipo === CuentaBancaria::TIPO_BANCO)
            ->groupBy('id_cuenta_bancaria');

        $idsCuentas = $ventasPorCuenta->keys()->merge($pagosPorCuenta->keys())->merge($gastosPorCuenta->keys())->unique();

        return $idsCuentas->map(function ($idCuenta) use ($ventasPorCuenta, $pagosPorCuenta, $gastosPorCuenta) {
            $grupoVentas = $ventasPorCuenta->get($idCuenta, collect());
            $grupoPagos  = $pagosPorCuenta->get($idCuenta, collect());
            $grupoGastos = $gastosPorCuenta->get($idCuenta, collect());
            $cuenta = $grupoVentas->first()->cuentaBancaria
                ?? $grupoPagos->first()->cuentaBancaria
                ?? $grupoGastos->first()->cuentaBancaria;

            return [
                'nombre' => $cuenta->nombre,
                // montoEnCuenta(): en una venta mixta solo cuenta la parte por transferencia
                'total'  => $grupoVentas->sum(fn($v) => $v->montoEnCuenta()) + $grupoPagos->sum('monto') - $grupoGastos->sum('valor'),
            ];
        })->sortByDesc('total')->values();
    }
}
