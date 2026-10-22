<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PrestamoExterno;
use App\Models\LiquidacionPrestamo;

class LocalExterno extends Model
{
    use HasFactory;

    protected $table = 'locales_externos';

    protected $fillable = [
        'nombre_local',
        'contacto',
        'telefono',
        'direccion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function historialMovimientos(): HasMany
    {
        return $this->hasMany(HistorialMovimiento::class, 'id_local_externo');
    }

    public function prestamosExternos(): HasMany
    {
        return $this->hasMany(PrestamoExterno::class, 'id_local_externo');
    }

    public function liquidaciones(): HasMany
    {
        return $this->hasMany(LiquidacionPrestamo::class, 'id_local_externo');
    }
}
