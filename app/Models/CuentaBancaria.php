<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaBancaria extends Model
{
    use HasFactory;

    protected $table = 'cuentas_bancarias';

    const TIPO_EFECTIVO = 'Efectivo';
    const TIPO_BANCO = 'Banco';

    protected $fillable = [
        'nombre',
        'banco',
        'tipo',
        'saldo_actual',
        'activo',
    ];

    protected $casts = [
        'saldo_actual' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCuentaBancaria::class, 'id_cuenta_bancaria')->latest();
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'id_cuenta_bancaria');
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Registra un movimiento y actualiza el saldo de la cuenta.
     * El caller debe tener el registro bloqueado (lockForUpdate) dentro de una transacción.
     */
    public function registrarMovimiento(float $monto, string $tipo, ?int $idVenta = null, ?string $observacion = null): MovimientoCuentaBancaria
    {
        if ($tipo === MovimientoCuentaBancaria::TIPO_RETIRO) {
            $this->decrement('saldo_actual', $monto);
        } else {
            $this->increment('saldo_actual', $monto);
        }

        return $this->movimientos()->create([
            'id_venta'         => $idVenta,
            'id_usuario'       => auth()->id(),
            'tipo'             => $tipo,
            'monto'            => $monto,
            'saldo_resultante' => $this->saldo_actual,
            'observacion'      => $observacion,
        ]);
    }
}
