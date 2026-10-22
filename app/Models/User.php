<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nombre',
        'usuario',
        'email',
        'password',
        'foto_perfil',
        'activo',
        'id_sucursal',
        'id_bodega',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal');
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'id_bodega');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'id_usuario');
    }

    public function historialMovimientos(): HasMany
    {
        return $this->hasMany(HistorialMovimiento::class, 'id_usuario');
    }

    public function pagosCredito(): HasMany
    {
        return $this->hasMany(PagoCredito::class, 'id_usuario');
    }

    public function gastosExternos(): HasMany
    {
        return $this->hasMany(GastoExterno::class, 'id_usuario');
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->nombre;
    }
}
