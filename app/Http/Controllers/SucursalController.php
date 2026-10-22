<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function index()
    {
        $sucursales = Sucursal::with(['bodegas', 'bodegaDefault'])->orderBy('nombre')->get();
        $bodegas    = Bodega::with('sucursal')->where('activo', true)->orderBy('nombre')->get();
        return view('sucursales.index', compact('sucursales', 'bodegas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:100',
            'direccion' => 'nullable|string|max:255',
            'telefono'  => 'nullable|string|max:30',
        ]);
        $data['activo'] = true;

        $sucursal = Sucursal::create($data);

        // Crear bodega principal automáticamente
        $bodega = Bodega::create([
            'id_sucursal' => $sucursal->id,
            'nombre'      => 'Principal',
            'activo'      => true,
        ]);
        $sucursal->update(['id_bodega_default' => $bodega->id]);

        return back()->with('success', "Sucursal \"{$sucursal->nombre}\" creada con bodega Principal.");
    }

    public function update(Request $request, Sucursal $sucursal)
    {
        $data = $request->validate([
            'nombre'           => 'required|string|max:100',
            'direccion'        => 'nullable|string|max:255',
            'telefono'         => 'nullable|string|max:30',
            'id_bodega_default'=> 'nullable|exists:bodegas,id',
        ]);

        $sucursal->update($data);
        return back()->with('success', 'Sucursal actualizada.');
    }

    public function toggleActivo(Sucursal $sucursal)
    {
        $sucursal->update(['activo' => !$sucursal->activo]);
        $estado = $sucursal->activo ? 'activada' : 'desactivada';
        return back()->with('success', "Sucursal {$estado}.");
    }
}
