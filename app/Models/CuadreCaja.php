<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Sucursal;

class CuadreCaja extends Model
{
    use HasFactory;

    protected $table = 'cuadres_caja';

    protected $fillable = [
        'id_usuario',
        'id_sucursal',
        'todas_sucursales',
        'fecha_apertura',
        'fecha_cierre',
        'saldo_inicial',
        'ventas_efectivo',
        'ventas_tarjeta',
        'ventas_transferencia',
        'ventas_mixto',
        'efectivo_contado',
        'transferencia_contada',
        'pagos_credito',
        'gastos',
        'liquidaciones_externas',
        'total_sistema',
        'total_contado',
        'diferencia',
        'justificacion',
        'estado',
        'desglose_billetes',
        'observaciones',
    ];

    protected $casts = [
        'todas_sucursales' => 'boolean',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'saldo_inicial' => 'decimal:2',
        'ventas_efectivo' => 'decimal:2',
        'ventas_tarjeta' => 'decimal:2',
        'ventas_transferencia' => 'decimal:2',
        'ventas_mixto' => 'decimal:2',
        'efectivo_contado' => 'decimal:2',
        'transferencia_contada' => 'decimal:2',
        'pagos_credito' => 'decimal:2',
        'gastos' => 'decimal:2',
        'liquidaciones_externas' => 'decimal:2',
        'total_sistema' => 'decimal:2',
        'total_contado' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'desglose_billetes' => 'array',
    ];

    // Estados del cuadre
    const ESTADO_ABIERTO = 'Abierto';
    const ESTADO_CERRADO = 'Cerrado';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal');
    }

    /**
     * Calcula la diferencia entre lo contado y el sistema
     */
    public function calcularDiferencia(): float
    {
        return $this->total_contado - $this->total_sistema;
    }

    /**
     * Sucursal por la que se filtran ventas, abonos y gastos del cuadre;
     * null para los cuadres de antes de manejar sucursales (incluyen todas).
     */
    public function sucursalDelDetalle(): ?int
    {
        return $this->todas_sucursales ? null : $this->id_sucursal;
    }

    /**
     * Scope para cuadre del día actual
     */
    public function scopeDeHoy($query)
    {
        return $query->whereDate('fecha_apertura', today());
    }

    /**
     * Scope para cuadres abiertos
     */
    public function scopeAbiertos($query)
    {
        return $query->where('estado', self::ESTADO_ABIERTO);
    }
}
