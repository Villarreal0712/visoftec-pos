<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntradaMercancia extends Model
{
    use HasFactory;

    protected $table = 'entradas_mercancia';

    protected $fillable = [
        'numero_entrada',
        'tipo_origen',
        'id_local_externo',
        'id_usuario',
        'id_bodega',
        'observacion',
    ];

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

    public function lotes(): HasMany
    {
        return $this->hasMany(LoteInventario::class, 'id_entrada');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(HistorialMovimiento::class, 'id_entrada');
    }

    /**
     * Genera el próximo número de entrada: ENT-0001, ENT-0002, ...
     */
    public static function generarNumero(): string
    {
        $ultimo = static::max('id') ?? 0;
        return 'ENT-' . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }
}
