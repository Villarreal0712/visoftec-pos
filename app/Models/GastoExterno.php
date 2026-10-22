<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Sucursal;

class GastoExterno extends Model
{
    use HasFactory;

    protected $table = 'gastos_externos';

    protected $fillable = [
        'descripcion',
        'categoria',
        'valor',
        'id_usuario',
        'id_sucursal',
        'id_cuenta_bancaria',
        'comprobante',
        'observacion',
        'tipo',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    // Tipos de gasto
    const TIPO_EXTERNO = 'Externo';
    const TIPO_LOCAL = 'Local';

    // Categorías
    const CATEGORIAS = [
        'Proveedores',
        'Servicios',
        'Arriendo',
        'Transporte',
        'Publicidad',
        'Mantenimiento',
        'Nómina',
        'Impuestos',
        'Caja Chica',
        'Otros',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal');
    }

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'id_cuenta_bancaria');
    }

    /**
     * Scope por categoría
     */
    public function scopePorCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope por período
     */
    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }

    /**
     * Scope para gastos de hoy
     */
    public function scopeDeHoy($query)
    {
        return $query->whereDate('created_at', today());
    }
}
