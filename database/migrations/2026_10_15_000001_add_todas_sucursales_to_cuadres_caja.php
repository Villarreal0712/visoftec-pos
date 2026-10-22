<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->boolean('todas_sucursales')->default(false)
                  ->after('id_sucursal')
                  ->comment('Cuadre de antes de manejar sucursales: su detalle incluye ventas, abonos y gastos de todas las sucursales');
        });
    }

    public function down(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->dropColumn('todas_sucursales');
        });
    }
};
