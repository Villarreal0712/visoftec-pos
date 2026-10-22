<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    public function edit()
    {
        $config = Configuracion::instancia();
        return view('configuracion.edit', compact('config'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nombre_comercio' => 'required|string|max:120',
            'nit'             => 'nullable|string|max:30',
            'direccion'       => 'nullable|string|max:200',
            'telefono'        => 'nullable|string|max:30',
            'email'           => 'nullable|email|max:120',
            'ciudad'          => 'nullable|string|max:80',
            'mensaje_ticket'  => 'nullable|string|max:300',
            'ancho_ticket'    => 'required|in:58mm,80mm',
        ]);

        Configuracion::instancia()->update($data);

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}
