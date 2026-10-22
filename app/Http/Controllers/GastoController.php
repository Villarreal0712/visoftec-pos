<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use App\Models\GastoExterno;
use App\Models\MovimientoCuentaBancaria;
use App\Traits\HandlesTableFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GastoController extends Controller
{
    use HandlesTableFeatures;

    public function index(Request $request)
    {
        $query = GastoExterno::with('usuario');

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $totalFiltrado = (clone $query)->sum('valor');

        if ($request->boolean('exportar')) {
            $items = (clone $query)->latest()->get();
            return $this->exportarCsv('gastos', $items, [
                'ID'          => fn($g) => $g->id,
                'Fecha'       => fn($g) => $g->created_at->format('d/m/Y H:i'),
                'Descripción' => fn($g) => $g->descripcion,
                'Categoría'   => fn($g) => $g->categoria,
                'Tipo'        => fn($g) => $g->tipo,
                'Valor'       => fn($g) => number_format($g->valor, 2, '.', ''),
                'Registrado por' => fn($g) => $g->usuario?->nombre ?? '—',
            ]);
        }

        $gastos = $query->latest()->paginate($this->getPerPage())->withQueryString();

        // Resumen por categoría
        $porCategoria = GastoExterno::selectRaw('categoria, SUM(valor) as total')
                                     ->when($request->filled('fecha_inicio'), fn($q) => $q->whereDate('created_at', '>=', $request->fecha_inicio))
                                     ->when($request->filled('fecha_fin'), fn($q) => $q->whereDate('created_at', '<=', $request->fecha_fin))
                                     ->groupBy('categoria')
                                     ->orderByDesc('total')
                                     ->get();

        return view('gastos.index', compact('gastos', 'totalFiltrado', 'porCategoria'));
    }

    public function create()
    {
        return view('gastos.create', [
            'categorias' => GastoExterno::CATEGORIAS,
            'cuentas' => CuentaBancaria::activas()->orderByRaw("tipo = 'Efectivo' desc")->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'descripcion' => 'required|string|max:200',
            'categoria' => 'required|string|max:100',
            'valor' => 'required|numeric|min:0.01|max:99999999',
            'tipo' => 'required|in:Externo,Local',
            'observacion' => 'nullable|string|max:500',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'id_cuenta_bancaria' => ['nullable', Rule::exists('cuentas_bancarias', 'id')->where('activo', true)],
        ]);

        $validated['id_usuario']  = auth()->id();
        $validated['id_sucursal'] = auth()->user()->id_sucursal ?? 1;

        if ($request->hasFile('comprobante')) {
            $validated['comprobante'] = $request->file('comprobante')->store('gastos', 'public');
        }

        try {
            DB::transaction(function () use ($validated, $request) {
                $cuentaBancaria = null;
                if ($request->filled('id_cuenta_bancaria')) {
                    $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->id_cuenta_bancaria);
                    if ($cuentaBancaria->tipo !== CuentaBancaria::TIPO_EFECTIVO && $validated['valor'] > $cuentaBancaria->saldo_actual) {
                        throw new \Exception("El monto supera el saldo actual de la cuenta \"{$cuentaBancaria->nombre}\" ($" . number_format($cuentaBancaria->saldo_actual, 0, ',', '.') . ").");
                    }
                }

                $gasto = GastoExterno::create($validated);

                if ($cuentaBancaria && $cuentaBancaria->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                    $cuentaBancaria->registrarMovimiento(
                        (float) $gasto->valor,
                        MovimientoCuentaBancaria::TIPO_RETIRO,
                        null,
                        "Gasto #{$gasto->id}: {$gasto->descripcion}"
                    );
                }
            });
        } catch (\Exception $e) {
            // Si ya se había subido el comprobante pero la transacción falló (p. ej. saldo
            // insuficiente), no dejarlo huérfano en el disco.
            if (!empty($validated['comprobante'])) {
                Storage::disk('public')->delete($validated['comprobante']);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('gastos.index')
                         ->with('success', 'Gasto registrado correctamente.');
    }

    public function show(GastoExterno $gasto)
    {
        $gasto->load('usuario');
        return view('gastos.show', compact('gasto'));
    }

    public function edit(GastoExterno $gasto)
    {
        return view('gastos.edit', [
            'gasto' => $gasto,
            'categorias' => GastoExterno::CATEGORIAS,
            'cuentas' => CuentaBancaria::activas()->orderByRaw("tipo = 'Efectivo' desc")->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, GastoExterno $gasto)
    {
        $validated = $request->validate([
            'descripcion' => 'required|string|max:200',
            'categoria' => 'required|string|max:100',
            'valor' => 'required|numeric|min:0.01|max:99999999',
            'tipo' => 'required|in:Externo,Local',
            'observacion' => 'nullable|string|max:500',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'id_cuenta_bancaria' => ['nullable', Rule::exists('cuentas_bancarias', 'id')->where('activo', true)],
        ]);

        if ($request->hasFile('comprobante')) {
            if ($gasto->comprobante) {
                Storage::disk('public')->delete($gasto->comprobante);
            }
            $validated['comprobante'] = $request->file('comprobante')->store('gastos', 'public');
        }

        $idCuentaNueva = $request->filled('id_cuenta_bancaria') ? (int) $request->id_cuenta_bancaria : null;
        $valorNuevo    = (float) $validated['valor'];

        try {
            DB::transaction(function () use ($gasto, $validated, $idCuentaNueva, $valorNuevo) {
                // Bloquear el gasto antes de leer su cuenta/valor "anteriores": si dos ediciones
                // del mismo gasto llegan casi al mismo tiempo, la segunda debe ver ya aplicados
                // los cambios de la primera (no un valor obsoleto), para no desfasar el saldo.
                $gastoLock = GastoExterno::lockForUpdate()->findOrFail($gasto->id);

                $idCuentaAnterior = $gastoLock->id_cuenta_bancaria;
                $valorAnterior    = (float) $gastoLock->valor;
                $cambioMovimiento = $idCuentaAnterior !== $idCuentaNueva || $valorAnterior != $valorNuevo;

                if ($cambioMovimiento) {
                    // Revertir el movimiento anterior (si la cuenta no era Efectivo)
                    if ($idCuentaAnterior) {
                        $cuentaAnterior = CuentaBancaria::lockForUpdate()->find($idCuentaAnterior);
                        if ($cuentaAnterior && $cuentaAnterior->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                            $cuentaAnterior->registrarMovimiento(
                                $valorAnterior,
                                MovimientoCuentaBancaria::TIPO_CONSIGNACION,
                                null,
                                "Reverso por edición de gasto #{$gastoLock->id}"
                            );
                        }
                    }

                    // Aplicar el nuevo movimiento (si la cuenta nueva no es Efectivo)
                    if ($idCuentaNueva) {
                        $cuentaNueva = CuentaBancaria::lockForUpdate()->findOrFail($idCuentaNueva);
                        if ($cuentaNueva->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                            if ($valorNuevo > $cuentaNueva->saldo_actual) {
                                throw new \Exception("El monto supera el saldo actual de la cuenta \"{$cuentaNueva->nombre}\" ($" . number_format($cuentaNueva->saldo_actual, 0, ',', '.') . ").");
                            }
                            $cuentaNueva->registrarMovimiento(
                                $valorNuevo,
                                MovimientoCuentaBancaria::TIPO_RETIRO,
                                null,
                                "Edición de gasto #{$gastoLock->id}: {$gastoLock->descripcion}"
                            );
                        }
                    }
                }

                $gastoLock->update($validated);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('gastos.index')
                         ->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(GastoExterno $gasto)
    {
        try {
            DB::transaction(function () use ($gasto) {
                // Bloquear el gasto antes de decidir si se revierte: evita que un doble clic
                // o un reintento de red reviertan el movimiento bancario dos veces.
                $gastoLock = GastoExterno::lockForUpdate()->findOrFail($gasto->id);

                if ($gastoLock->id_cuenta_bancaria) {
                    $cuenta = CuentaBancaria::lockForUpdate()->find($gastoLock->id_cuenta_bancaria);
                    if ($cuenta && $cuenta->tipo !== CuentaBancaria::TIPO_EFECTIVO) {
                        $cuenta->registrarMovimiento(
                            (float) $gastoLock->valor,
                            MovimientoCuentaBancaria::TIPO_CONSIGNACION,
                            null,
                            "Reverso por eliminación de gasto #{$gastoLock->id}"
                        );
                    }
                }

                if ($gastoLock->comprobante) {
                    Storage::disk('public')->delete($gastoLock->comprobante);
                }

                $gastoLock->delete();
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('gastos.index')->with('info', 'Este gasto ya había sido eliminado.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gastos.index')
                         ->with('success', 'Gasto eliminado correctamente.');
    }
}
