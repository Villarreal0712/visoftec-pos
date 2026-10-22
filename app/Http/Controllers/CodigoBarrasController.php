<?php

namespace App\Http\Controllers;

use App\Models\DetalleProducto;
use App\Models\EntradaMercancia;
use Illuminate\Http\Request;

class CodigoBarrasController extends Controller
{
    /**
     * Generador libre: busca variantes y define cuántas etiquetas imprimir.
     */
    public function generador()
    {
        return view('codigos-barras.generador');
    }

    /**
     * Vista de impresión: recibe items[]=[{id, cantidad}] via POST.
     */
    public function imprimir(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.id'         => 'required|exists:detalle_productos,id',
            'items.*.cantidad'   => 'required|integer|min:1|max:200',
        ]);

        $ids = collect($request->items)->pluck('id');

        $detalles = DetalleProducto::with('producto')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $etiquetas = collect($request->items)
            ->map(fn($item) => [
                'detalle'  => $detalles[$item['id']] ?? null,
                'cantidad' => (int) $item['cantidad'],
            ])
            ->filter(fn($e) => $e['detalle'] !== null)
            ->values();

        return view('codigos-barras.imprimir', compact('etiquetas'));
    }

    /**
     * Imprime automáticamente las etiquetas de una entrada de mercancía.
     */
    public function imprimirEntrada(EntradaMercancia $entrada)
    {
        $lotes = $entrada->lotes()->with('detalleProducto.producto')->get();

        $etiquetas = $lotes->map(fn($lote) => [
            'detalle'  => $lote->detalleProducto,
            'cantidad' => $lote->cantidad_disponible,
        ])->filter(fn($e) => $e['detalle'] !== null)->values();

        return view('codigos-barras.imprimir', compact('etiquetas'));
    }
}
