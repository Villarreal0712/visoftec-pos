<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestamoExterno extends Model
{
    use HasFactory;

    protected $table = 'prestamos_externos';

    protected $fillable = [
        'id_venta',
        'id_historial_venta',
        'id_local_externo',
        'id_detalle_producto',
        'cantidad',
        'precio_venta',
        'costo_acordado',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'cantidad'       => 'integer',
        'precio_venta'   => 'decimal:2',
        'costo_acordado' => 'decimal:2',
    ];

    const ESTADO_PENDIENTE  = 'pendiente';
    const ESTADO_LIQUIDADO  = 'liquidado';
    const ESTADO_ANULADO    = 'anulado';

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function historialVenta(): BelongsTo
    {
        return $this->belongsTo(HistorialVenta::class, 'id_historial_venta');
    }

    public function localExterno(): BelongsTo
    {
        return $this->belongsTo(LocalExterno::class, 'id_local_externo');
    }

    public function detalleProducto(): BelongsTo
    {
        return $this->belongsTo(DetalleProducto::class, 'id_detalle_producto');
    }

    /**
     * Monto total vendido al cliente por este préstamo
     */
    public function getTotalVentaAttribute(): float
    {
        return $this->cantidad * $this->precio_venta;
    }

    /**
     * Lo que se le debe al local (null si no se ha acordado costo)
     */
    public function getTotalDeudaAttribute(): ?float
    {
        return $this->costo_acordado !== null
            ? $this->cantidad * $this->costo_acordado
            : null;
    }

    /**
     * Ganancia estimada (solo cuando hay costo acordado)
     */
    public function getGananciaEstimadaAttribute(): ?float
    {
        return $this->costo_acordado !== null
            ? $this->cantidad * ($this->precio_venta - $this->costo_acordado)
            : null;
    }

    /**
     * ¿Quitar $montoQueSeQuita de la deuda con este local la dejaría por debajo de lo que ya
     * se le pagó? El estado "liquidado" por préstamo nunca se asigna (las liquidaciones se
     * registran agregadas por local), así que se compara contra los pagos registrados.
     */
    public static function deudaYaCubierta(int $idLocalExterno, float $montoQueSeQuita): bool
    {
        $totalPendienteLocal = self::where('id_local_externo', $idLocalExterno)
            ->where('estado', self::ESTADO_PENDIENTE)
            ->whereNotNull('costo_acordado')
            ->get()
            ->sum('total_deuda');

        $totalLiquidadoLocal = LiquidacionPrestamo::where('id_local_externo', $idLocalExterno)->sum('monto');

        return $totalLiquidadoLocal > $totalPendienteLocal - $montoQueSeQuita;
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeActivos($query)
    {
        return $query->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_LIQUIDADO]);
    }
}
