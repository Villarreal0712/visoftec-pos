<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use App\Models\MovimientoCuentaBancaria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuentaBancariaController extends Controller
{
    public function index()
    {
        $cuentas = CuentaBancaria::orderByRaw("tipo = 'Efectivo' desc")->orderBy('nombre')->get();
        $totalBancos = $cuentas->where('tipo', CuentaBancaria::TIPO_BANCO)->sum('saldo_actual');

        return view('cuentas-bancarias.index', compact('cuentas', 'totalBancos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:cuentas_bancarias,nombre',
            'banco'  => 'nullable|string|max:100',
        ]);

        $data['tipo'] = CuentaBancaria::TIPO_BANCO;
        $data['activo'] = true;

        $cuenta = CuentaBancaria::create($data);

        return back()->with('success', "Cuenta \"{$cuenta->nombre}\" creada.");
    }

    public function toggleActivo(CuentaBancaria $cuenta)
    {
        if ($cuenta->tipo === CuentaBancaria::TIPO_EFECTIVO) {
            return back()->with('error', 'La cuenta Efectivo no se puede desactivar.');
        }

        $cuenta->update(['activo' => !$cuenta->activo]);
        $estado = $cuenta->activo ? 'activada' : 'desactivada';

        return back()->with('success', "Cuenta {$estado}.");
    }

    public function show(CuentaBancaria $cuenta)
    {
        if ($cuenta->tipo === CuentaBancaria::TIPO_EFECTIVO) {
            return redirect()->route('cuentas-bancarias.index')
                             ->with('error', 'El efectivo no se administra aquí — su saldo lo maneja el Cuadre de Caja.');
        }

        $movimientos = $cuenta->movimientos()->with('usuario', 'venta')->paginate(30);

        return view('cuentas-bancarias.show', compact('cuenta', 'movimientos'));
    }

    /**
     * Registra un retiro o consignación manual sobre la cuenta.
     */
    public function registrarMovimiento(Request $request, CuentaBancaria $cuenta)
    {
        if ($cuenta->tipo === CuentaBancaria::TIPO_EFECTIVO) {
            return back()->with('error', 'El efectivo no se administra aquí — su saldo lo maneja el Cuadre de Caja.');
        }

        $request->validate([
            'tipo'        => 'required|in:' . MovimientoCuentaBancaria::TIPO_CONSIGNACION . ',' . MovimientoCuentaBancaria::TIPO_RETIRO,
            'monto'       => 'required|numeric|min:0.01|max:99999999',
            'observacion' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $cuenta) {
                // Lock para evitar que dos movimientos concurrentes lean el mismo saldo
                $cuentaFresh = CuentaBancaria::lockForUpdate()->findOrFail($cuenta->id);

                if ($request->tipo === MovimientoCuentaBancaria::TIPO_RETIRO && $request->monto > $cuentaFresh->saldo_actual) {
                    throw new \Exception("El monto supera el saldo actual de la cuenta ($" . number_format($cuentaFresh->saldo_actual, 0, ',', '.') . ").");
                }

                $cuentaFresh->registrarMovimiento(
                    (float) $request->monto,
                    $request->tipo,
                    null,
                    $request->observacion
                );
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Movimiento registrado correctamente.');
    }
}
