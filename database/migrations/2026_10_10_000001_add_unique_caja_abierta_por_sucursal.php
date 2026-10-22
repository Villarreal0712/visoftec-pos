<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Garantiza a nivel de base de datos que no pueda existir más de un cuadre de caja
     * "Abierto" por sucursal, cerrando la ventana de carrera que dejaba el chequeo
     * "leer estado -> si no hay, crear" en CuadreCajaController::store().
     */
    public function up(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->unsignedBigInteger('sucursal_si_abierta')
                  ->nullable()
                  ->virtualAs("IF(estado = 'Abierto', id_sucursal, NULL)")
                  ->after('id_sucursal');
        });

        DB::statement('ALTER TABLE cuadres_caja ADD UNIQUE INDEX cuadres_caja_sucursal_si_abierta_unique (sucursal_si_abierta)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cuadres_caja DROP INDEX cuadres_caja_sucursal_si_abierta_unique');

        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->dropColumn('sucursal_si_abierta');
        });
    }
};
