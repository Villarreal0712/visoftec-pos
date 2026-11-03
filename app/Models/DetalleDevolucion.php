<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleDevolucion extends Model
{
    protected $table = 'detalle_devoluciones';

    protected $fillable = [
        'id_devolucion',
        'id_historial_venta',
        'cantidad',
        'precio_venta',
        'costo_unitario',
    ];

    protected $casts = [
        'cantidad'       => 'integer',
        'precio_venta'   => 'decimal:2',
        'costo_unitario' => 'decimal:2',
    ];

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(Devolucion::class, 'id_devolucion');
    }

    public function historialVenta(): BelongsTo
    {
        return $this->belongsTo(HistorialVenta::class, 'id_historial_venta');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->cantidad * $this->precio_venta;
    }
}
