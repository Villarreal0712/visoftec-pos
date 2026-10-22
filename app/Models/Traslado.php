<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Traslado extends Model
{
    use HasFactory;

    protected $table = 'traslados';

    protected $fillable = [
        'numero_traslado',
        'id_bodega_origen',
        'id_bodega_destino',
        'id_usuario',
        'observacion',
    ];

    public function bodegaOrigen(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_origen');
    }

    public function bodegaDestino(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_destino');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleTraslado::class, 'id_traslado');
    }

    public static function generarNumero(): string
    {
        $ultimo = static::max('id') ?? 0;
        return 'TRS-' . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }
}
