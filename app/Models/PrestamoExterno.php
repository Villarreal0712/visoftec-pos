<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoExterno extends Model
{
    use HasFactory;

    protected $table = 'prestamos_externos';

    protected $fillable = [
        'id_venta',
        'id_historial_venta',
        'id_local_externo',
        'id_detalle_producto',
        'cantidad',
        'precio_venta',
        'costo_acordado',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'cantidad'       => 'integer',
        'precio_venta'   => 'decimal:2',
        'costo_acordado' => 'decimal:2',
    ];

    const ESTADO_PENDIENTE  = 'pendiente';
    const ESTADO_LIQUIDADO  = 'liquidado';
    const ESTADO_ANULADO    = 'anulado';

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function historialVenta(): BelongsTo
    {
        return $this->belongsTo(HistorialVenta::class, 'id_historial_venta');
    }

    public function localExterno(): BelongsTo
    {
        return $this->belongsTo(LocalExterno::class, 'id_local_externo');
    }

    public function detalleProducto(): BelongsTo
    {
        return $this->belongsTo(DetalleProducto::class, 'id_detalle_producto');
    }

    /**
     * Monto total vendido al cliente por este préstamo
     */
    public function getTotalVentaAttribute(): float
    {
        return $this->cantidad * $this->precio_venta;
    }

    /**
     * Lo que se le debe al local (null si no se ha acordado costo)
     */
    public function getTotalDeudaAttribute(): ?float
    {
        return $this->costo_acordado !== null
            ? $this->cantidad * $this->costo_acordado
            : null;
    }

    /**
     * Ganancia estimada (solo cuando hay costo acordado)
     */
    public function getGananciaEstimadaAttribute(): ?float
    {
        return $this->costo_acordado !== null
            ? $this->cantidad * ($this->precio_venta - $this->costo_acordado)
            : null;
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeActivos($query)
    {
        return $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_LIQUIDADO]);
    }
}
