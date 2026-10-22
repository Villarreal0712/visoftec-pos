<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuracion';

    protected $fillable = [
        'nombre_comercio',
        'nit',
        'direccion',
        'telefono',
        'email',
        'ciudad',
        'mensaje_ticket',
        'ancho_ticket',
    ];

    /**
     * Devuelve siempre la fila única de configuración (id=1).
     * Si no existe la crea con valores vacíos.
     */
    public static function instancia(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'nombre_comercio' => config('app.name', 'Mi Tienda'),
        ]);
    }
}
