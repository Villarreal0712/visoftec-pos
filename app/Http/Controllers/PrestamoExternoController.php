<?php

namespace App\Http\Controllers;

use App\Models\LiquidacionPrestamo;
use App\Models\LocalExterno;
use App\Models\PrestamoExterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestamoExternoController extends Controller
{
    /**
     * Resumen de deudas pendientes por local externo
     */
    public function index()
    {
        $locales = LocalExterno::where('activo', true)
            ->withCount([
                'prestamosExternos as prestamos_pendientes' => fn($q) => $q->where('estado', PrestamoExterno::ESTADO_PENDIENTE),
            ])
            ->get()
            ->map(function ($local) {
                $pendientes = $local->prestamosExternos()
                    ->where('estado', PrestamoExterno::ESTADO_PENDIENTE)
                    ->get();

                $local->total_vendido    = $pendientes->sum('total_venta');
                $local->total_deuda      = $pendientes->whereNotNull('costo_acordado')->sum('total_deuda');
                $local->sin_costo        = $pendientes->whereNull('costo_acordado')->count();
                $local->total_liquidado  = $local->liquidaciones()->sum('monto');

                return $local;
            })
            ->filter(fn($l) => $l->prestamos_pendientes > 0 || $l->total_liquidado > 0);

        return view('prestamos.index', compact('locales'));
    }

    /**
     * Detalle de un local externo: préstamos pendientes + historial de liquidaciones
     */
    public function show(LocalExterno $localExterno)
    {
        $pendientes = PrestamoExterno::with(['venta.cliente', 'detalleProducto.producto'])
            ->where('id_local_externo', $localExterno->id)
            ->where('estado', PrestamoExterno::ESTADO_PENDIENTE)
            ->latest()
            ->get();

        $liquidaciones = LiquidacionPrestamo::with('usuario')
            ->where('id_local_externo', $localExterno->id)
            ->latest()
            ->get();

        $totalVendido   = $pendientes->sum('total_venta');
        $totalDeuda     = $pendientes->whereNotNull('costo_acordado')->sum('total_deuda');
        $sinCosto       = $pendientes->whereNull('costo_acordado')->count();
        $totalLiquidado = $liquidaciones->sum('monto');
        // Sin max(0, ...): si queda negativo significa que se le pagó de más a este local
        // (sobrepago), y eso debe quedar visible en vez de mostrarse como saldo en cero.
        $saldoPendiente = $totalDeuda - $totalLiquidado;

        return view('prestamos.show', compact(
            'localExterno',
            'pendientes',
            'liquidaciones',
            'totalVendido',
            'totalDeuda',
            'sinCosto',
            'totalLiquidado',
            'saldoPendiente'
        ));
    }

    /**
     * Registrar un pago al local externo y opcionalmente actualizar costos/liquidar préstamos
     */
    public function liquidar(Request $request, LocalExterno $localExterno)
    {
        $request->validate([
            'monto'         => 'required|numeric|min:1',
            'metodo_pago'   => 'required|in:Efectivo,Tarjeta,Transferencia',
            'observaciones' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($request, $localExterno) {
                // Bloquear la fila del local para que dos liquidaciones simultáneas no lean
                // el mismo saldo pendiente y ambas pasen la validación, sobrepagando la deuda.
                $localLock = LocalExterno::lockForUpdate()->findOrFail($localExterno->id);

                $saldoPendiente = max(0,
                    PrestamoExterno::where('id_local_externo', $localLock->id)
                        ->where('estado', PrestamoExterno::ESTADO_PENDIENTE)
                        ->whereNotNull('costo_acordado')
                        ->get()
                        ->sum('total_deuda')
                    - $localLock->liquidaciones()->sum('monto')
                );

                if ($saldoPendiente <= 0) {
                    throw new \Exception('Este local no tiene saldo pendiente.');
                }

                if ($request->monto > $saldoPendiente) {
                    throw new \Exception('El monto no puede superar el saldo pendiente ($' . number_format($saldoPendiente, 0, ',', '.') . ').');
                }

                LiquidacionPrestamo::create([
                    'id_local_externo' => $localLock->id,
                    'monto'            => $request->monto,
                    'metodo_pago'      => $request->metodo_pago,
                    'observaciones'    => $request->observaciones,
                    'id_usuario'       => auth()->id(),
                ]);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('prestamos.show', $localExterno)
            ->with('success', 'Pago registrado correctamente.');
    }

    /**
     * Actualizar el costo acordado de un préstamo pendiente
     */
    public function actualizarCosto(Request $request, PrestamoExterno $prestamo)
    {
        $request->validate([
            'costo_acordado' => ['required', 'numeric', 'min:0', 'max:' . $prestamo->precio_venta],
        ]);

        $prestamo->update(['costo_acordado' => $request->costo_acordado]);

        return back()->with('success', 'Costo actualizado.');
    }
}
