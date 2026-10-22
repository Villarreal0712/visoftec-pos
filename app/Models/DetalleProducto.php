<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetalleProducto extends Model
{
    use HasFactory;

    protected $table = 'detalle_productos';

    protected $fillable = [
        'id_producto',
        'talla',
        'codigo_barras',
        'precio_venta',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(LoteInventario::class, 'id_detalle_producto');
    }

    public function historialMovimientos(): HasMany
    {
        return $this->hasMany(HistorialMovimiento::class, 'id_detalle_producto');
    }

    /**
     * Obtiene el stock total disponible para este detalle (suma de lotes)
     */
    public function getStockTotalAttribute(): int
    {
        return $this->lotes->sum('cantidad_disponible');
    }

    /**
     * Obtiene el costo promedio ponderado
     */
    public function getCostoPromedioAttribute(): float
    {
        $lotes = $this->lotes->where('cantidad_disponible', '>', 0);
        if ($lotes->isEmpty()) {
            return 0;
        }

        $totalCantidad = $lotes->sum('cantidad_disponible');
        $totalCosto = $lotes->sum(fn($lote) => $lote->cantidad_disponible * $lote->costo_unitario);

        return $totalCantidad > 0 ? $totalCosto / $totalCantidad : 0;
    }

    /**
     * Scope para buscar por código de barras
     */
    public function scopePorCodigoBarras($query, string $codigo)
    {
        return $query->where('codigo_barras', $codigo);
    }

    /**
     * Genera un código de barras único para una variante.
     * Formato: [id_producto][consecutivo 3 dígitos]
     * Ejemplo: producto 12, tercera variante → "12003"
     */
    public static function generarCodigoBarras(int $idProducto): string
    {
        $consecutivo = static::where('id_producto', $idProducto)->count() + 1;
        $codigo = $idProducto . str_pad($consecutivo, 3, '0', STR_PAD_LEFT);

        // Garantizar unicidad ante concurrencia o huecos en el consecutivo
        while (static::where('codigo_barras', $codigo)->exists()) {
            $consecutivo++;
            $codigo = $idProducto . str_pad($consecutivo, 3, '0', STR_PAD_LEFT);
        }

        return $codigo;
    }
}
