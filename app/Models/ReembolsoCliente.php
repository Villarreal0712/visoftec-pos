<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dinero devuelto a un cliente: por una devolución o porque pidió que le reembolsen su saldo
 * a favor. El cuadre de caja lo descuenta cuando sale del cajón (sin cuenta o cuenta Efectivo).
 */
class ReembolsoCliente extends Model
{
    protected $table = 'reembolsos_cliente';

    protected $fillable = [
        'id_cliente',
        'id_devolucion',
        'id_usuario',
        'id_sucursal',
        'monto',
        'id_cuenta_bancaria',
        'observacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(Devolucion::class, 'id_devolucion');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'id_cuenta_bancaria');
    }

    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }

    /** Reembolsos que salieron del cajón: sin cuenta o desde una cuenta tipo Efectivo. */
    public function scopeEnEfectivo($query)
    {
        return $query->where(fn($q) => $q->whereNull('id_cuenta_bancaria')
            ->orWhereHas('cuentaBancaria', fn($c) => $c->where('tipo', CuentaBancaria::TIPO_EFECTIVO)));
    }
}
