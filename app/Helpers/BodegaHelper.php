<?php

namespace App\Helpers;

use App\Models\Bodega;

class BodegaHelper
{
    /**
     * Retorna el id_bodega del usuario autenticado.
     * Fallback: bodega 1 (Principal).
     */
    public static function getIdBodegaActiva(): ?int
    {
        return auth()->user()?->id_bodega ?? 1;
    }

    public static function getBodegaActiva(): ?Bodega
    {
        $id = static::getIdBodegaActiva();
        return $id ? Bodega::find($id) : null;
    }
}
