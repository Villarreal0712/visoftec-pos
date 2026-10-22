<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionPrestamo extends Model
{
    use HasFactory;

    protected $table = 'liquidaciones_prestamo';

    protected $fillable = [
        'id_local_externo',
        'monto',
        'metodo_pago',
        'observaciones',
        'id_usuario',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function localExterno(): BelongsTo
    {
        return $this->belongsTo(LocalExterno::class, 'id_local_externo');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }
}
