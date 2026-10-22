<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'documento',
        'nombre',
        'telefono',
        'direccion',
        'correo',
        'limite_credito',
        'activo',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'limite_credito' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'id_cliente');
    }

    /**
     * Total de deuda pendiente (excluye ventas anuladas)
     */
    public function getSaldoPendienteTotalAttribute(): float
    {
        return $this->ventas()
                    ->activas()
                    ->conCreditoPendiente()
                    ->sum('saldo_pendiente');
    }

    /**
     * Total de compras realizadas (excluye ventas anuladas)
     */
    public function getTotalComprasAttribute(): float
    {
        return $this->ventas()->activas()->sum('total');
    }

    /**
     * Días de mora (excluye ventas anuladas)
     */
    public function getDiasMoraAttribute(): int
    {
        $ventaMoraNegativa = $this->ventas()
            ->activas()
            ->where('estado_credito', Venta::ESTADO_MORA)
            ->where('fecha_vencimiento', '<', now())
            ->orderBy('fecha_vencimiento', 'asc')
            ->first();

        if (!$ventaMoraNegativa) {
            return 0;
        }

        return now()->diffInDays($ventaMoraNegativa->fecha_vencimiento);
    }

    /**
     * Scope para buscar clientes
     */
    public function scopeBuscar($query, string $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
                     ->orWhere('documento', 'like', "%{$termino}%")
                     ->orWhere('telefono', 'like', "%{$termino}%")
                     ->orWhere('correo', 'like', "%{$termino}%");
    }

    /**
     * Scope para clientes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
