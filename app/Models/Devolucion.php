<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Devolución parcial (o total) de ítems de una venta. El valor devuelto se reparte en:
 * monto_deuda (descontó lo que el cliente aún debía) + monto_saldo_favor + monto_reembolso.
 */
class Devolucion extends Model
{
    protected $table = 'devoluciones';

    protected $fillable = [
        'id_venta',
        'id_cliente',
        'id_usuario',
        'id_sucursal',
        'total',
        'monto_deuda',
        'monto_saldo_favor',
        'monto_reembolso',
        'motivo',
    ];

    protected $casts = [
        'total'             => 'decimal:2',
        'monto_deuda'       => 'decimal:2',
        'monto_saldo_favor' => 'decimal:2',
        'monto_reembolso'   => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleDevolucion::class, 'id_devolucion');
    }

    public function reembolso(): HasOne
    {
        return $this->hasOne(ReembolsoCliente::class, 'id_devolucion');
    }
}
