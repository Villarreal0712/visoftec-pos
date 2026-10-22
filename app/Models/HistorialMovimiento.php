<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialMovimiento extends Model
{
    use HasFactory;

    protected $table = 'historial_movimientos';

    protected $fillable = [
        'id_detalle_producto',
        'tipo_operacion',
        'tipo_origen',
        'cantidad',
        'costo_unitario',
        'id_usuario',
        'id_local_externo',
        'observacion',
        'id_entrada',
        'id_bodega',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'costo_unitario' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    // Tipos de operación
    const TIPO_ENTRADA = 'Entrada';
    const TIPO_SALIDA = 'Salida';
    const TIPO_AJUSTE = 'Ajuste';
    const TIPO_TRANSFERENCIA = 'Transferencia';
    const TIPO_VENTA = 'Venta';
    const TIPO_MERMA = 'Merma';

    public function detalleProducto(): BelongsTo
    {
        return $this->belongsTo(DetalleProducto::class, 'id_detalle_producto');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'id_bodega');
    }

    public function localExterno(): BelongsTo
    {
        return $this->belongsTo(LocalExterno::class, 'id_local_externo');
    }

    /**
     * Scope para entradas
     */
    public function scopeEntradas($query)
    {
        return $query->where('tipo_operacion', self::TIPO_ENTRADA);
    }

    /**
     * Scope para salidas
     */
    public function scopeSalidas($query)
    {
        return $query->whereIn('tipo_operacion', [self::TIPO_SALIDA, self::TIPO_VENTA, self::TIPO_MERMA]);
    }

    /**
     * Scope por período
     */
    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }
}
