<?php

namespace App\Http\Controllers;

use App\Models\LocalExterno;
use App\Models\PrestamoExterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocalExternoController extends Controller
{
    public function index()
    {
        $locales = LocalExterno::latest()->get();
        return view('locales-externos.index', compact('locales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_local' => 'required|string|max:100',
            'contacto'     => 'nullable|string|max:100',
            'telefono'     => 'nullable|string|max:50',
            'direccion'    => 'nullable|string|max:200',
        ]);

        LocalExterno::create([
            'nombre_local' => $request->nombre_local,
            'contacto'     => $request->contacto,
            'telefono'     => $request->telefono,
            'direccion'    => $request->direccion,
            'activo'       => true,
        ]);

        return back()->with('success', "Local \"{$request->nombre_local}\" creado correctamente.");
    }

    public function update(Request $request, LocalExterno $localExterno)
    {
        $request->validate([
            'nombre_local' => 'required|string|max:100',
            'contacto'     => 'nullable|string|max:100',
            'telefono'     => 'nullable|string|max:50',
            'direccion'    => 'nullable|string|max:200',
        ]);

        $localExterno->update($request->only(['nombre_local', 'contacto', 'telefono', 'direccion']));

        return back()->with('success', 'Local actualizado correctamente.');
    }

    public function toggleActivo(LocalExterno $localExterno)
    {
        try {
            $nuevoEstado = DB::transaction(function () use ($localExterno) {
                // Bloquear el local antes de calcular su saldo: evita que una venta Directo
                // concurrente inserte deuda justo después de que se decida desactivarlo.
                $local = LocalExterno::lockForUpdate()->findOrFail($localExterno->id);

                if ($local->activo) {
                    $saldoPendiente = max(0,
                        PrestamoExterno::where('id_local_externo', $local->id)
                            ->where('estado', PrestamoExterno::ESTADO_PENDIENTE)
                            ->whereNotNull('costo_acordado')
                            ->get()
                            ->sum('total_deuda')
                        - $local->liquidaciones()->sum('monto')
                    );

                    if ($saldoPendiente > 0) {
                        throw new \Exception("No se puede desactivar \"{$local->nombre_local}\": aún se le debe $" . number_format($saldoPendiente, 0, ',', '.') . ".");
                    }
                }

                $local->update(['activo' => !$local->activo]);

                return $local->activo;
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $estado = $nuevoEstado ? 'activado' : 'desactivado';
        return back()->with('success', "Local {$estado}.");
    }
}
