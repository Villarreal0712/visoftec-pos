<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historial del saldo a favor de un cliente (ver Cliente::moverSaldoFavor()). */
class MovimientoSaldoCliente extends Model
{
    protected $table = 'movimientos_saldo_cliente';

    const TIPO_DEVOLUCION = 'Devolución';
    const TIPO_COMPRA     = 'Compra';
    const TIPO_ABONO      = 'Abono';
    const TIPO_REEMBOLSO  = 'Reembolso';
    const TIPO_ANULACION  = 'Anulación';

    protected $fillable = [
        'id_cliente',
        'tipo',
        'monto',
        'saldo_resultante',
        'id_venta',
        'id_devolucion',
        'id_pago_credito',
        'id_reembolso',
        'id_usuario',
        'observacion',
    ];

    protected $casts = [
        'monto'            => 'decimal:2',
        'saldo_resultante' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
