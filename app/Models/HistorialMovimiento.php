<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialMovimiento extends Model
{
    use HasFactory;

    protected $table = 'historial_movimientos';

    protected $fillable = [
        'id_detalle_producto',
        'tipo_operacion',
        'tipo_origen',
        'cantidad',
        'costo_unitario',
        'id_usuario',
        'id_local_externo',
        'observacion',
        'id_entrada',
        'id_bodega',
        'id_traslado',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'costo_unitario' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    // Tipos de operación
    const TIPO_ENTRADA = 'Entrada';
    const TIPO_SALIDA = 'Salida';
    const TIPO_AJUSTE = 'Ajuste';
    const TIPO_TRANSFERENCIA = 'Transferencia';
    const TIPO_VENTA = 'Venta';
    const TIPO_MERMA = 'Merma';

    public function detalleProducto(): BelongsTo
    {
        return $this->belongsTo(DetalleProducto::class, 'id_detalle_producto');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'id_bodega');
    }

    public function localExterno(): BelongsTo
    {
        return $this->belongsTo(LocalExterno::class, 'id_local_externo');
    }

    public function traslado(): BelongsTo
    {
        return $this->belongsTo(Traslado::class, 'id_traslado');
    }

    /**
     * Relaciones que necesitan direccion()/desde()/hacia() — para cargar con with().
     */
    public const RELACIONES_UBICACION = ['bodega', 'localExterno', 'traslado.bodegaOrigen', 'traslado.bodegaDestino'];

    /**
     * 'entra' | 'sale' | 'neutro' según el efecto real sobre el stock propio.
     * (El tipo solo no basta: un traslado tiene una fila que sale y otra que entra, y
     * "Ajuste" es tanto la devolución de una venta anulada como una salida manual.)
     */
    public function direccion(): string
    {
        // Venta directa: la mercancía es de un local externo, pasa directo al cliente
        if ($this->tipo_origen === 'Directo') {
            return 'neutro';
        }

        return match ($this->tipo_operacion) {
            self::TIPO_ENTRADA => 'entra',
            self::TIPO_VENTA, self::TIPO_SALIDA, self::TIPO_MERMA => 'sale',
            self::TIPO_TRANSFERENCIA => ($this->cantidad < 0 || $this->id_local_externo) ? 'sale' : 'entra',
            self::TIPO_AJUSTE => $this->esAnulacionVenta()
                ? ($this->cantidad > 0 ? 'entra' : 'neutro')
                : 'sale',
            default => 'neutro',
        };
    }

    /** Desde dónde salió la mercancía. */
    public function desde(): string
    {
        if ($this->traslado) {
            return $this->nombreBodega($this->traslado->bodegaOrigen);
        }

        return match (true) {
            $this->tipo_operacion === self::TIPO_ENTRADA => $this->localExterno
                ? 'Local externo: ' . $this->localExterno->nombre_local
                : 'Proveedor / compra',
            $this->tipo_origen === 'Directo' => $this->localExterno
                ? 'Local externo: ' . $this->localExterno->nombre_local
                : 'Local externo (préstamo)',
            $this->esAnulacionVenta() => 'Devolución de venta' . $this->numeroVenta(),
            $this->tipo_operacion === self::TIPO_TRANSFERENCIA && $this->direccion() === 'entra'
                => $this->bodegaPorObservacion('desde') ?? '—',
            default => $this->nombreBodega($this->bodega),
        };
    }

    /** Hacia dónde fue la mercancía. */
    public function hacia(): string
    {
        if ($this->traslado) {
            return $this->nombreBodega($this->traslado->bodegaDestino);
        }

        return match (true) {
            $this->tipo_operacion === self::TIPO_ENTRADA,
            $this->esAnulacionVenta() && $this->cantidad > 0 => $this->nombreBodega($this->bodega),
            $this->esAnulacionVenta() => 'Local externo (préstamo cancelado)',
            $this->tipo_operacion === self::TIPO_VENTA => 'Cliente' . $this->numeroVenta(),
            $this->tipo_operacion === self::TIPO_TRANSFERENCIA && $this->localExterno
                => 'Local externo: ' . $this->localExterno->nombre_local,
            $this->tipo_operacion === self::TIPO_TRANSFERENCIA && $this->direccion() === 'sale'
                => $this->bodegaPorObservacion('hacia') ?? '—',
            $this->tipo_operacion === self::TIPO_TRANSFERENCIA => $this->nombreBodega($this->bodega),
            $this->tipo_operacion === self::TIPO_MERMA => 'Baja (merma / daño)',
            $this->tipo_operacion === self::TIPO_AJUSTE => 'Baja (ajuste)',
            $this->tipo_operacion === self::TIPO_SALIDA => 'Salida / devolución',
            default => '—',
        };
    }

    private function esAnulacionVenta(): bool
    {
        return $this->tipo_operacion === self::TIPO_AJUSTE
            && str_starts_with((string) $this->observacion, 'Anulación venta');
    }

    private function numeroVenta(): string
    {
        return preg_match('/#(\d+)/', (string) $this->observacion, $m) ? " (venta #{$m[1]})" : '';
    }

    private function nombreBodega(?Bodega $bodega): string
    {
        return $bodega?->nombre ?? 'Sin bodega registrada';
    }

    /**
     * Traslados viejos sin id_traslado: la observación trae "hacia bodega ID 3" / "desde bodega ID 2".
     */
    private function bodegaPorObservacion(string $sentido): ?string
    {
        if (!preg_match('/' . $sentido . ' bodega ID (\d+)/', (string) $this->observacion, $m)) {
            return null;
        }
        static $nombres = null;
        $nombres ??= Bodega::pluck('nombre', 'id');
        return $nombres[(int) $m[1]] ?? "Bodega #{$m[1]}";
    }

    /**
     * Scope para entradas
     */
    public function scopeEntradas($query)
    {
        return $query->where('tipo_operacion', self::TIPO_ENTRADA);
    }

    /**
     * Scope para salidas
     */
    public function scopeSalidas($query)
    {
        return $query->whereIn('tipo_operacion', [self::TIPO_SALIDA, self::TIPO_VENTA, self::TIPO_MERMA]);
    }

    /**
     * Scope por período
     */
    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }
}
