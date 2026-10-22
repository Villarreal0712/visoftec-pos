<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->decimal('liquidaciones_externas', 18, 2)->default(0)
                  ->after('gastos')
                  ->comment('Pagos realizados a locales externos en el período del cuadre');
        });
    }

    public function down(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->dropColumn('liquidaciones_externas');
        });
    }
};
