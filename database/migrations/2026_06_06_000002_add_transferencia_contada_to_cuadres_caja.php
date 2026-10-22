<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->decimal('efectivo_contado', 18, 2)->default(0)
                  ->after('ventas_mixto')
                  ->comment('Efectivo físico contado en el cierre (suma del desglose de billetes)');
            $table->decimal('transferencia_contada', 18, 2)->default(0)
                  ->after('efectivo_contado')
                  ->comment('Ingresos no en efectivo verificados: tarjeta, Nequi, Daviplata, transferencia bancaria');
        });
    }

    public function down(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->dropColumn(['efectivo_contado', 'transferencia_contada']);
        });
    }
};
