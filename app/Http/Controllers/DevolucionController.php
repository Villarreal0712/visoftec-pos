<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\CuentaBancaria;
use App\Models\DetalleDevolucion;
use App\Models\Devolucion;
use App\Models\HistorialMovimiento;
use App\Models\MovimientoCuentaBancaria;
use App\Models\MovimientoSaldoCliente;
use App\Models\PrestamoExterno;
use App\Models\ReembolsoCliente;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DevolucionController extends Controller
{
    /**
     * Devolución parcial de una venta: los ítems propios vuelven a su lote (misma bodega), los
     * prestados vuelven al local externo (baja la deuda del préstamo). El valor devuelto primero
     * descuenta lo que el cliente aún debe de esa venta; el resto queda como saldo a favor o se
     * le reembolsa. La venta conserva su total (el dinero sí entró ese día) y suma total_devuelto.
     */
    public function store(Request $request, Venta $venta)
    {
        // El layout solo muestra mensajes 'error' (no la bolsa de errores de validación)
        $validator = Validator::make($request->all(), [
            'items'                      => 'required|array|min:1',
            'items.*.id_historial_venta' => 'required|integer',
            'items.*.cantidad'           => 'nullable|integer|min:0',
            'destino'                    => 'required|in:saldo,reembolso',
            'id_cuenta_bancaria'         => ['nullable', Rule::exists('cuentas_bancarias', 'id')->where('activo', true)],
            'motivo'                     => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        $items = collect($request->items)
            ->filter(fn($i) => (int) $i['cantidad'] > 0)
            ->mapWithKeys(fn($i) => [(int) $i['id_historial_venta'] => (int) $i['cantidad']]);

        if ($items->isEmpty()) {
            return back()->with('error', 'Indica cuántas unidades devuelve el cliente.');
        }

        try {
            $devolucion = DB::transaction(function () use ($request, $venta, $items) {
                $ventaLock = Venta::lockForUpdate()->findOrFail($venta->id);

                if ($ventaLock->anulada) {
                    throw new \Exception('La venta está anulada.');
                }

                $lineas = $ventaLock->historialVentas()->with('lote.detalleProducto.producto')
                    ->whereIn('id', $items->keys())->lockForUpdate()->get()->keyBy('id');

                $total = 0;
                $deudaPrestamoPorLocal = [];
                foreach ($items as $idLinea => $cantidad) {
                    $linea = $lineas->get($idLinea);
                    if (!$linea) {
                        throw new \Exception('Uno de los productos no pertenece a esta venta.');
                    }
                    if ($cantidad > $linea->cantidad_neta) {
                        $det = $linea->lote->detalleProducto;
                        throw new \Exception("De {$det->producto->nombre} {$det->talla} solo se pueden devolver {$linea->cantidad_neta} unidad(es).");
                    }
                    $total += $cantidad * (float) $linea->precio_venta;

                    if ($linea->lote->tipo_origen === 'Directo') {
                        $prestamo = PrestamoExterno::where('id_historial_venta', $linea->id)->first();
                        // Un préstamo liquidado (estado heredado) ya se le pagó al local: la unidad no
                        // volvería ni al local ni al inventario, así que no se registra en silencio.
                        if ($prestamo && $prestamo->estado === PrestamoExterno::ESTADO_LIQUIDADO) {
                            throw new \Exception('No se puede devolver: el préstamo de ese producto ya está liquidado con el local externo. Contacta al administrador para un ajuste manual.');
                        }
                        if ($prestamo && $prestamo->estado === PrestamoExterno::ESTADO_PENDIENTE && $prestamo->costo_acordado !== null) {
                            $deudaPrestamoPorLocal[$prestamo->id_local_externo] =
                                ($deudaPrestamoPorLocal[$prestamo->id_local_externo] ?? 0) + $cantidad * (float) $prestamo->costo_acordado;
                        }
                    }
                }
                $total = round($total, 2);

                // Misma regla que la anulación: si ya se le pagó al local, devolverle mercancía
                // dejaría un saldo a nuestro favor que el sistema no sabe manejar.
                foreach ($deudaPrestamoPorLocal as $idLocal => $monto) {
                    if (PrestamoExterno::deudaYaCubierta($idLocal, $monto)) {
                        throw new \Exception('No se puede devolver: la deuda de ese préstamo con el local externo ya fue pagada. Contacta al administrador para un ajuste manual.');
                    }
                }

                // Primero se descuenta lo que el cliente todavía debe de esta venta
                $montoDeuda = $ventaLock->es_credito ? min($total, (float) $ventaLock->saldo_pendiente) : 0;
                $excedente  = round($total - $montoDeuda, 2);

                $cliente = null;
                $cuenta  = null;
                if ($excedente > 0 && $request->destino === 'saldo') {
                    if (!$ventaLock->id_cliente) {
                        throw new \Exception('Esta venta no tiene cliente: no se le puede dejar saldo a favor. Elige reembolsar el dinero.');
                    }
                    $cliente = Cliente::lockForUpdate()->findOrFail($ventaLock->id_cliente);
                }
                if ($excedente > 0 && $request->destino === 'reembolso' && $request->filled('id_cuenta_bancaria')) {
                    $cuenta = CuentaBancaria::lockForUpdate()->findOrFail($request->id_cuenta_bancaria);
                }

                $devolucion = Devolucion::create([
                    'id_venta'          => $ventaLock->id,
                    'id_cliente'        => $ventaLock->id_cliente,
                    'id_usuario'        => auth()->id(),
                    'id_sucursal'       => auth()->user()->id_sucursal ?? 1,
                    'total'             => $total,
                    'monto_deuda'       => $montoDeuda,
                    'monto_saldo_favor' => $cliente ? $excedente : 0,
                    'monto_reembolso'   => $excedente > 0 && !$cliente ? $excedente : 0,
                    'motivo'            => $request->motivo,
                ]);

                foreach ($items as $idLinea => $cantidad) {
                    $linea = $lineas->get($idLinea);
                    $this->devolverLinea($linea, $cantidad, $ventaLock);

                    DetalleDevolucion::create([
                        'id_devolucion'      => $devolucion->id,
                        'id_historial_venta' => $linea->id,
                        'cantidad'           => $cantidad,
                        'precio_venta'       => $linea->precio_venta,
                        'costo_unitario'     => $linea->costo_unitario,
                    ]);
                }

                $ventaLock->total_devuelto = round((float) $ventaLock->total_devuelto + $total, 2);
                $ventaLock->save();
                if ($ventaLock->es_credito) {
                    $ventaLock->actualizarEstadoCredito();
                }

                if ($cliente) {
                    $cliente->moverSaldoFavor($excedente, MovimientoSaldoCliente::TIPO_DEVOLUCION,
                        ['id_venta' => $ventaLock->id, 'id_devolucion' => $devolucion->id],
                        "Devolución de la venta #{$ventaLock->id}");
                } elseif ($excedente > 0) {
                    ReembolsoCliente::create([
                        'id_cliente'         => $ventaLock->id_cliente,
                        'id_devolucion'      => $devolucion->id,
                        'id_usuario'         => auth()->id(),
                        'id_sucursal'        => auth()->user()->id_sucursal ?? 1,
                        'monto'              => $excedente,
                        'id_cuenta_bancaria' => $cuenta?->id,
                        'observacion'        => "Devolución de la venta #{$ventaLock->id}",
                    ]);
                    // El efectivo lo controla el Cuadre de Caja; una cuenta Banco baja su saldo aquí
                    if ($cuenta && $cuenta->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                        $cuenta->registrarMovimiento($excedente, MovimientoCuentaBancaria::TIPO_RETIRO,
                            $ventaLock->id, "Reembolso devolución venta #{$ventaLock->id}");
                    }
                }

                return $devolucion;
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $f = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
        $partes = [];
        if ($devolucion->monto_deuda > 0)       $partes[] = "{$f($devolucion->monto_deuda)} descontados de la deuda";
        if ($devolucion->monto_saldo_favor > 0) $partes[] = "{$f($devolucion->monto_saldo_favor)} quedan como saldo a favor del cliente";
        if ($devolucion->monto_reembolso > 0)   $partes[] = "{$f($devolucion->monto_reembolso)} para devolver al cliente";

        return redirect()->route('ventas.show', $venta)
            ->with('success', "Devolución registrada por {$f($devolucion->total)}: " . implode(', ', $partes) . '.');
    }

    /** Devuelve $cantidad unidades de una línea: al lote propio o al local externo del préstamo. */
    private function devolverLinea($linea, int $cantidad, Venta $venta): void
    {
        $lote = $linea->lote;

        if ($lote->tipo_origen === 'Directo') {
            // La mercancía es del local externo: vuelve a ellos y se debe menos
            $prestamo = PrestamoExterno::where('id_historial_venta', $linea->id)->lockForUpdate()->first();
            if ($prestamo && $prestamo->estado === PrestamoExterno::ESTADO_PENDIENTE) {
                if ($cantidad >= $prestamo->cantidad) {
                    $prestamo->estado = PrestamoExterno::ESTADO_ANULADO;
                } else {
                    $prestamo->cantidad -= $cantidad;
                }
                $prestamo->observaciones = trim(($prestamo->observaciones ?? '') . "\nDevolución venta #{$venta->id}: {$cantidad} und. vuelven al local.");
                $prestamo->save();
            }

            HistorialMovimiento::create([
                'id_detalle_producto' => $lote->id_detalle_producto,
                'tipo_operacion'      => HistorialMovimiento::TIPO_AJUSTE,
                'tipo_origen'         => 'Directo',
                'cantidad'            => 0,
                'costo_unitario'      => $linea->costo_unitario,
                'id_usuario'          => auth()->id(),
                'observacion'         => "Devolución venta directa #{$venta->id} — {$cantidad} und. vuelven al local externo",
                'id_local_externo'    => $prestamo?->id_local_externo,
            ]);
        } else {
            $lote->increment('cantidad_disponible', $cantidad);

            HistorialMovimiento::create([
                'id_detalle_producto' => $lote->id_detalle_producto,
                'tipo_operacion'      => HistorialMovimiento::TIPO_AJUSTE,
                'tipo_origen'         => $lote->tipo_origen,
                'cantidad'            => $cantidad,
                'costo_unitario'      => $linea->costo_unitario,
                'id_usuario'          => auth()->id(),
                'observacion'         => "Devolución venta #{$venta->id}",
                'id_bodega'           => $lote->id_bodega,
            ]);
        }

        $linea->increment('cantidad_devuelta', $cantidad);
    }
}
