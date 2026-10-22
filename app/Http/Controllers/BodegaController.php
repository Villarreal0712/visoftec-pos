<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class BodegaController extends Controller
{
    public function index()
    {
        $sucursales = Sucursal::with(['bodegas' => fn($q) => $q->orderBy('nombre')])
                              ->orderBy('nombre')
                              ->get();

        $bodegasAcopio = Bodega::whereNull('id_sucursal')->orderBy('nombre')->get();

        return view('bodegas.index', compact('sucursales', 'bodegasAcopio'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_sucursal' => 'nullable|exists:sucursales,id',
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
        ]);
        $data['activo'] = true;

        $bodega = Bodega::create($data);
        return back()->with('success', "Bodega \"{$bodega->nombre}\" creada.");
    }

    public function update(Request $request, Bodega $bodega)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $bodega->update($data);
        return back()->with('success', 'Bodega actualizada.');
    }

    public function toggleActivo(Bodega $bodega)
    {
        $bodega->update(['activo' => !$bodega->activo]);
        $estado = $bodega->activo ? 'activada' : 'desactivada';
        return back()->with('success', "Bodega {$estado}.");
    }
}
