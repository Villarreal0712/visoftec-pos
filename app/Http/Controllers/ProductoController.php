<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\DetalleProducto;
use App\Models\Producto;
use App\Traits\HandlesTableFeatures;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    use HandlesTableFeatures;

    public function index(Request $request)
    {
        $query = Producto::with(['detalles.lotes', 'categoria'])
                         ->activos();

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        if ($request->filled('categoria')) {
            $query->where('id_categoria', $request->categoria);
        }

        if ($request->boolean('stock_bajo')) {
            $query->whereHas('detalles', function ($q) {
                $q->whereHas('lotes', function ($lq) {
                    $lq->where('cantidad_disponible', '>', 0);
                }, '<', 1);
            });
        }

        if ($request->boolean('exportar')) {
            $items = (clone $query)->orderBy('nombre')->get();
            return $this->exportarCsv('productos', $items, [
                'ID'          => fn($p) => $p->id,
                'Nombre'      => fn($p) => $p->nombre,
                'Categoría'   => fn($p) => $p->categoria?->nombre ?? '—',
                'Precio venta'=> fn($p) => number_format($p->precio_venta ?? 0, 2, '.', ''),
                'Stock total' => fn($p) => $p->detalles->sum(fn($d) => $d->lotes->sum('cantidad_disponible')),
                'Stock mínimo'=> fn($p) => $p->stock_minimo,
            ]);
        }

        $productos = $query->orderBy('nombre')->paginate($this->getPerPage())->withQueryString();

        $categorias = Categoria::activas()->orderBy('nombre')->get();

        return view('productos.index', compact('productos', 'categorias'));
    }

    public function create()
    {
        $categorias = Categoria::activas()->orderBy('nombre')->get();
        $nextProductoId = (Producto::max('id') ?? 0) + 1;

        return view('productos.create', compact('categorias', 'nextProductoId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'       => 'required|string|max:100',
            'id_categoria' => 'nullable|exists:categorias,id',
            'descripcion'  => 'nullable|string|max:500',
            'precio_venta' => 'nullable|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'imagen'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'detalles'     => 'required|array|min:1',
            'detalles.*.talla' => 'nullable|string|max:20',
        ]);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto = Producto::create($validated);

        foreach ($request->detalles as $detalleData) {
            $producto->detalles()->create([
                'talla' => $detalleData['talla'] ?? null,
                'codigo_barras' => DetalleProducto::generarCodigoBarras($producto->id),
            ]);
        }

        return redirect()->route('productos.index')
                         ->with('success', 'Producto creado correctamente.');
    }

    public function show(Producto $producto)
    {
        $producto->load(['categoria', 'detalles.lotes', 'detalles.historialMovimientos.usuario']);
        return view('productos.show', compact('producto'));
    }

    public function edit(Producto $producto)
    {
        $producto->load(['detalles', 'detalles.lotes' => fn($q) => $q->conStock()]);
        $categorias = Categoria::activas()->orderBy('nombre')->get();

        return view('productos.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, Producto $producto)
    {
        $validated = $request->validate([
            'nombre'       => 'required|string|max:100',
            'id_categoria' => 'nullable|exists:categorias,id',
            'descripcion'  => 'nullable|string|max:500',
            'precio_venta' => 'nullable|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'imagen'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'detalles_existentes.*.talla' => 'nullable|string|max:20',
            'detalles_nuevos.*.talla' => 'nullable|string|max:20',
        ]);

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $validated['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto->update($validated);

        // Actualizar la talla de variantes existentes (el código de barras no se edita)
        foreach ($request->input('detalles_existentes', []) as $id => $detalleData) {
            $producto->detalles()->whereKey($id)->update([
                'talla' => $detalleData['talla'] ?? null,
            ]);
        }

        // Crear nuevas variantes con código de barras auto-generado
        foreach ($request->input('detalles_nuevos', []) as $detalleData) {
            if (empty($detalleData['talla']) && empty($detalleData['codigo_barras'])) {
                continue;
            }
            $producto->detalles()->create([
                'talla' => $detalleData['talla'] ?? null,
                'codigo_barras' => DetalleProducto::generarCodigoBarras($producto->id),
            ]);
        }

        return redirect()->route('productos.show', $producto)
                         ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        // Verificar que no tenga ventas asociadas
        $tieneVentas = $producto->detalles()
                                ->whereHas('lotes.historialVentas')
                                ->exists();

        if ($tieneVentas) {
            return back()->with('error', 'No se puede eliminar un producto con ventas registradas. Use desactivar en su lugar.');
        }

        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }

        $producto->update(['activo' => false]);

        return redirect()->route('productos.index')
                         ->with('success', 'Producto desactivado correctamente.');
    }

    /**
     * Búsqueda rápida para el POS (devuelve JSON)
     */
    public function buscarPOS(Request $request)
    {
        $termino = $request->get('q', '');

        $idBodegaActiva = auth()->user()->id_bodega ?? 1;

        $detalles = DetalleProducto::with(['producto', 'lotes' => fn($q) => $q->conStock($idBodegaActiva)])
            ->whereHas('producto', fn($q) => $q->activos())
            ->where(function ($query) use ($termino) {
                $query->where('codigo_barras', $termino)
                      ->orWhereHas('producto', fn($q) => $q->where('nombre', 'like', "%{$termino}%"));
            })
            ->limit(20)
            ->get()
            ->map(function ($detalle) {
                $stockTotal = $detalle->lotes->sum('cantidad_disponible');
                // Precio: del lote activo si hay stock, sino del producto
                $loteActivo = $detalle->lotes->first();
                $precio = $loteActivo?->precio_venta ?? $detalle->producto->precio_venta ?? 0;
                $costo  = $loteActivo?->costo_unitario ?? 0;
                return [
                    'id'            => $detalle->id,
                    'producto'      => $detalle->producto->nombre,
                    'talla'         => $detalle->talla,
                    'codigo_barras' => $detalle->codigo_barras,
                    'precio'        => (float) $precio,
                    'costo'         => (float) round($costo, 2),
                    'stock'         => $stockTotal,
                ];
            });

        return response()->json($detalles);
    }
}
