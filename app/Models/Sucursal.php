<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'id_bodega_default',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function bodegas(): HasMany
    {
        return $this->hasMany(Bodega::class, 'id_sucursal');
    }

    public function bodegaDefault(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_default');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'id_sucursal');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'id_sucursal');
    }

    public function cuadresCaja(): HasMany
    {
        return $this->hasMany(CuadreCaja::class, 'id_sucursal');
    }

    public function gastosExternos(): HasMany
    {
        return $this->hasMany(GastoExterno::class, 'id_sucursal');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
