<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialVenta extends Model
{
    use HasFactory;

    protected $table = 'historial_ventas';

    protected $fillable = [
        'id_venta',
        'id_lote',
        'cantidad',
        'cantidad_devuelta',
        'precio_venta',
        'costo_unitario',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'cantidad_devuelta' => 'integer',
        'precio_venta' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteInventario::class, 'id_lote');
    }

    /**
     * Calcula el subtotal del ítem
     */
    public function getSubtotalAttribute(): float
    {
        return $this->cantidad * $this->precio_venta;
    }

    /** Unidades que el cliente se quedó (vendidas menos devueltas). */
    public function getCantidadNetaAttribute(): int
    {
        return $this->cantidad - $this->cantidad_devuelta;
    }

    /**
     * Calcula la ganancia del ítem (sin las unidades devueltas)
     */
    public function getGananciaAttribute(): float
    {
        return ($this->precio_venta - $this->costo_unitario) * $this->cantidad_neta;
    }
}
