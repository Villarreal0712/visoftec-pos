<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
        'saldo_favor' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'id_cliente');
    }

    public function movimientosSaldo(): HasMany
    {
        return $this->hasMany(MovimientoSaldoCliente::class, 'id_cliente')->latest('id');
    }

    /**
     * Sube (monto > 0) o usa (monto < 0) el saldo a favor y deja el movimiento en el historial.
     * saldo_favor no está en $fillable: solo cambia por aquí. El caller debe tener el cliente
     * bloqueado (lockForUpdate) dentro de una transacción.
     */
    public function moverSaldoFavor(float $monto, string $tipo, array $referencias = [], ?string $observacion = null): MovimientoSaldoCliente
    {
        $monto = round($monto, 2);
        $nuevoSaldo = round((float) $this->saldo_favor + $monto, 2);

        if ($nuevoSaldo < 0) {
            throw new \Exception("\"{$this->nombre}\" no tiene saldo a favor suficiente. Disponible: $"
                . number_format((float) $this->saldo_favor, 0, ',', '.') . '.');
        }

        $this->saldo_favor = $nuevoSaldo;
        $this->save();

        return $this->movimientosSaldo()->create($referencias + [
            'tipo'             => $tipo,
            'monto'            => $monto,
            'saldo_resultante' => $nuevoSaldo,
            'id_usuario'       => auth()->id(),
            'observacion'      => $observacion,
        ]);
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
        return (float) $this->ventas()->activas()->sum(DB::raw('total - total_devuelto'));
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
