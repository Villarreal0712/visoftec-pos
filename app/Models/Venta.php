<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'id_usuario',
        'id_sucursal',
        'id_cliente',
        'es_credito',
        'metodo_pago',
        'id_cuenta_bancaria',
        'monto_efectivo',
        'monto_transferencia',
        'fecha_vencimiento',
        'saldo_pendiente',
        'estado_credito',
        'total',
        'observaciones',
        'anulada',
        'motivo_anulacion',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'es_credito' => 'boolean',
        'anulada' => 'boolean',
        'saldo_pendiente' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_efectivo' => 'decimal:2',
        'monto_transferencia' => 'decimal:2',
    ];

    // Estados de crédito
    const ESTADO_PAGADO = 'Pagado';
    const ESTADO_PARCIAL = 'Parcial';
    const ESTADO_MORA = 'Mora';

    // Métodos de pago
    const METODO_EFECTIVO = 'Efectivo';
    const METODO_TARJETA = 'Tarjeta';
    const METODO_TRANSFERENCIA = 'Transferencia';
    const METODO_CREDITO = 'Crédito';
    const METODO_MIXTO = 'Mixto';

    /** Venta mixta con el reparto guardado (las mixtas anteriores al 2026-10-28 no lo tienen). */
    public function esMixtoConReparto(): bool
    {
        return $this->metodo_pago === self::METODO_MIXTO && $this->monto_transferencia !== null;
    }

    /**
     * Cuánto de esta venta entró a su cuenta bancaria (id_cuenta_bancaria): en una mixta,
     * solo la parte por transferencia; en el resto, el total. Usar siempre esto —y no
     * $total— al mover o revertir el saldo de la cuenta y al desglosar por cuenta.
     */
    public function montoEnCuenta(): float
    {
        return $this->esMixtoConReparto() ? (float) $this->monto_transferencia : (float) $this->total;
    }

    /** Texto del medio de pago; en una mixta incluye el reparto. */
    public function descripcionPago(): string
    {
        if (!$this->esMixtoConReparto()) {
            return (string) $this->metodo_pago;
        }
        $f = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
        $cuenta = $this->cuentaBancaria?->nombre ?? 'transferencia';
        return "Mixto: efectivo {$f($this->monto_efectivo)} + {$cuenta} {$f($this->monto_transferencia)}";
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'id_cuenta_bancaria');
    }

    public function historialVentas(): HasMany
    {
        return $this->hasMany(HistorialVenta::class, 'id_venta');
    }

    public function pagosCredito(): HasMany
    {
        return $this->hasMany(PagoCredito::class, 'id_venta');
    }

    public function prestamosExternos(): HasMany
    {
        return $this->hasMany(PrestamoExterno::class, 'id_venta');
    }

    /**
     * Calcula el total de la venta sumando los ítems
     */
    public function calcularTotal(): float
    {
        return $this->historialVentas->sum(fn($item) => $item->cantidad * $item->precio_venta);
    }

    /**
     * Calcula el total pagado en abonos (usa query para incluir pagos recientes en la misma transacción)
     */
    public function getTotalPagadoAttribute(): float
    {
        return (float) $this->pagosCredito()->sum('monto');
    }

    /**
     * Actualiza el saldo pendiente y estado de crédito
     */
    public function actualizarEstadoCredito(): void
    {
        $totalVenta = $this->total;
        $totalPagado = $this->total_pagado;
        $saldo = $totalVenta - $totalPagado;

        $this->saldo_pendiente = max(0, $saldo);

        if ($saldo <= 0) {
            $this->estado_credito = self::ESTADO_PAGADO;
        } elseif ($this->fecha_vencimiento && now()->isAfter($this->fecha_vencimiento)) {
            $this->estado_credito = self::ESTADO_MORA;
        } else {
            $this->estado_credito = self::ESTADO_PARCIAL;
        }

        $this->save();
    }

    /**
     * Scope para excluir ventas anuladas (usar en todos los conteos/reportes)
     */
    public function scopeActivas($query)
    {
        return $query->where('anulada', false);
    }

    /**
     * Scope para ventas del día (solo activas)
     */
    public function scopeDeHoy($query)
    {
        return $query->activas()->whereDate('created_at', today());
    }

    /**
     * Scope para ventas con crédito pendiente (excluye anuladas)
     */
    public function scopeConCreditoPendiente($query)
    {
        return $query->activas()
                     ->where('es_credito', true)
                     ->whereIn('estado_credito', [self::ESTADO_PARCIAL, self::ESTADO_MORA]);
    }

    /**
     * Scope filtrar por período
     */
    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }
}
