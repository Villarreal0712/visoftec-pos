<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoteInventario extends Model
{
    use HasFactory;

    protected $table = 'lotes_inventario';

    protected $fillable = [
        'id_detalle_producto',
        'id_bodega',
        'fecha_ingreso',
        'cantidad_disponible',
        'costo_unitario',
        'precio_venta',
        'tipo_origen',
        'id_movimiento_origen',
        'id_entrada',
    ];

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'cantidad_disponible' => 'integer',
        'costo_unitario' => 'decimal:2',
        'precio_venta' => 'decimal:2',
    ];

    /**
     * Tipos de origen válidos
     */
    const TIPO_PROPIO = 'Propio';
    const TIPO_PRESTADO = 'Prestado';

    public function detalleProducto(): BelongsTo
    {
        return $this->belongsTo(DetalleProducto::class, 'id_detalle_producto');
    }

    public function historialVentas(): HasMany
    {
        return $this->hasMany(HistorialVenta::class, 'id_lote');
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'id_bodega');
    }

    public function movimientoOrigen(): BelongsTo
    {
        return $this->belongsTo(HistorialMovimiento::class, 'id_movimiento_origen');
    }

    /**
     * Scope para lotes con stock disponible (FIFO - más antiguos primero).
     * Si se pasa $idBodega, filtra por bodega; null devuelve todas.
     */
    public function scopeConStock($query, ?int $idBodega = null)
    {
        return $query->where('cantidad_disponible', '>', 0)
                     ->when($idBodega, fn($q) => $q->where('id_bodega', $idBodega))
                     ->orderBy('fecha_ingreso', 'asc')
                     ->orderBy('id', 'asc');
    }

    /**
     * Scope para lotes propios
     */
    public function scopePropios($query)
    {
        return $query->where('tipo_origen', self::TIPO_PROPIO);
    }
}
