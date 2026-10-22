<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sucursal donde se RECIBIÓ el abono (la caja donde entró la plata), que puede ser
        // distinta a la sucursal de la venta: el cuadre de caja suma los abonos por esta columna.
        Schema::table('pagos_credito', function (Blueprint $table) {
            $table->foreignId('id_sucursal')
                  ->nullable()
                  ->after('id_usuario')
                  ->constrained('sucursales')
                  ->nullOnDelete();
            $table->index('id_sucursal');
        });

        // Abonos existentes: se asumen recibidos en la sucursal de su venta (el criterio
        // que usaba el cuadre hasta ahora), así ningún cuadre cambia.
        DB::statement('UPDATE pagos_credito p JOIN ventas v ON v.id = p.id_venta SET p.id_sucursal = v.id_sucursal');
    }

    public function down(): void
    {
        Schema::table('pagos_credito', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_sucursal');
        });
    }
};
