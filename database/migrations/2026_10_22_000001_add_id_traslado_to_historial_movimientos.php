<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad de bodegas en el reporte de movimientos:
 * - id_traslado: liga las dos filas de un traslado (salida del origen / entrada al destino).
 * - Rellena id_bodega (y id_local_externo en ventas directas) de los movimientos viejos que no
 *   lo tenían, a partir de los datos que sí quedaron guardados. Solo completa campos vacíos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_movimientos', function (Blueprint $table) {
            $table->foreignId('id_traslado')->nullable()->after('id_entrada')
                  ->constrained('traslados')->nullOnDelete();
        });

        // Entradas: la bodega del lote que creó el movimiento (o la de la entrada).
        DB::statement("
            UPDATE historial_movimientos hm
            JOIN lotes_inventario l ON l.id_movimiento_origen = hm.id
            SET hm.id_bodega = l.id_bodega
            WHERE hm.id_bodega IS NULL AND hm.tipo_operacion = 'Entrada' AND l.id_bodega IS NOT NULL
        ");
        DB::statement("
            UPDATE historial_movimientos hm
            JOIN entradas_mercancia e ON e.id = hm.id_entrada
            SET hm.id_bodega = e.id_bodega
            WHERE hm.id_bodega IS NULL AND hm.tipo_operacion = 'Entrada' AND e.id_bodega IS NOT NULL
        ");

        // Traslados: la observación es "Traslado TRS-0001 — salida hacia…" / "— entrada desde…".
        // La parte negativa sale del origen; la positiva entra al destino.
        DB::statement("
            UPDATE historial_movimientos hm
            JOIN traslados t ON hm.observacion LIKE CONCAT('Traslado ', t.numero_traslado, ' —%')
            SET hm.id_traslado = t.id,
                hm.id_bodega   = COALESCE(hm.id_bodega, IF(hm.cantidad < 0, t.id_bodega_origen, t.id_bodega_destino))
            WHERE hm.tipo_operacion = 'Transferencia' AND hm.id_traslado IS NULL
        ");

        // Ventas y anulaciones ("Venta #12" / "Anulación venta #12"): la bodega del lote vendido.
        foreach (['Venta #', 'Anulación venta #'] as $prefijo) {
            DB::statement("
                UPDATE historial_movimientos hm
                JOIN (
                    SELECT hv.id_venta, l.id_detalle_producto, MIN(l.id_bodega) AS id_bodega
                    FROM historial_ventas hv
                    JOIN lotes_inventario l ON l.id = hv.id_lote
                    WHERE l.id_bodega IS NOT NULL
                    GROUP BY hv.id_venta, l.id_detalle_producto
                ) x ON x.id_detalle_producto = hm.id_detalle_producto
                   AND hm.observacion = CONCAT(?, x.id_venta)
                SET hm.id_bodega = x.id_bodega
                WHERE hm.id_bodega IS NULL
            ", [$prefijo]);
        }

        // Ventas directas: "Venta directa #12 (local externo #3)" → id_local_externo = 3.
        DB::statement("
            UPDATE historial_movimientos hm
            JOIN locales_externos le
              ON le.id = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(hm.observacion, 'local externo #', -1), ')', 1) AS UNSIGNED)
            SET hm.id_local_externo = le.id
            WHERE hm.id_local_externo IS NULL AND hm.observacion LIKE 'Venta directa #% (local externo #%)'
        ");
    }

    public function down(): void
    {
        // Los id_bodega / id_local_externo rellenados se dejan: son datos correctos.
        Schema::table('historial_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_traslado');
        });
    }
};
