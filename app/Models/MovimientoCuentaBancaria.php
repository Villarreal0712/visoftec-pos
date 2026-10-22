<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCuentaBancaria extends Model
{
    use HasFactory;

    protected $table = 'movimientos_cuenta_bancaria';

    const TIPO_VENTA = 'Venta';
    const TIPO_ABONO = 'Abono';
    const TIPO_CONSIGNACION = 'Consignacion';
    const TIPO_RETIRO = 'Retiro';
    const TIPO_AJUSTE = 'Ajuste';

    protected $fillable = [
        'id_cuenta_bancaria',
        'id_venta',
        'id_usuario',
        'tipo',
        'monto',
        'saldo_resultante',
        'observacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldo_resultante' => 'decimal:2',
    ];

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'id_cuenta_bancaria');
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
