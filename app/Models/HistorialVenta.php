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
        'precio_venta',
        'costo_unitario',
    ];

    protected $casts = [
        'cantidad' => 'integer',
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

    /**
     * Calcula la ganancia del ítem
     */
    public function getGananciaAttribute(): float
    {
        return ($this->precio_venta - $this->costo_unitario) * $this->cantidad;
    }
}
