<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleTraslado extends Model
{
    use HasFactory;

    protected $table = 'detalle_traslados';

    protected $fillable = [
        'id_traslado',
        'id_detalle_producto',
        'cantidad',
        'costo_unitario',
    ];

    protected $casts = [
        'cantidad'       => 'integer',
        'costo_unitario' => 'decimal:2',
    ];

    public function traslado(): BelongsTo
    {
        return $this->belongsTo(Traslado::class, 'id_traslado');
    }

    public function detalleProducto(): BelongsTo
    {
        return $this->belongsTo(DetalleProducto::class, 'id_detalle_producto');
    }
}
