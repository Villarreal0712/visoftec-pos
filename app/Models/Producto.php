<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'id_categoria',
        'descripcion',
        'imagen',
        'precio_venta',
        'stock_minimo',
        'activo',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'stock_minimo' => 'integer',
        'activo'       => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleProducto::class, 'id_producto');
    }

    /**
     * Obtiene el stock total sumando todos los lotes de todos los detalles
     */
    public function getStockTotalAttribute(): int
    {
        return $this->detalles->sum(function ($detalle) {
            return $detalle->lotes->sum('cantidad_disponible');
        });
    }

    /**
     * Verifica si el producto tiene stock bajo
     */
    public function getTieneStockBajoAttribute(): bool
    {
        return $this->stock_total <= $this->stock_minimo;
    }

    /**
     * Scope para productos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por nombre o categoría
     */
    public function scopeBuscar($query, string $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
                     ->orWhereHas('categoria', fn($q) => $q->where('nombre', 'like', "%{$termino}%"));
    }
}
