<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->decimal('ventas_mixto', 18, 2)->default(0)
                  ->after('ventas_transferencia')
                  ->comment('Ventas con método de pago Mixto (efectivo + otro)');
        });
    }

    public function down(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->dropColumn('ventas_mixto');
        });
    }
};
