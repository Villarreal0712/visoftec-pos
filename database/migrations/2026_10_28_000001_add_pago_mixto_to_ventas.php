<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pago mixto (efectivo + transferencia): guarda cómo se repartió el total.
 * Solo se llenan en ventas con metodo_pago = 'Mixto'; en ellas id_cuenta_bancaria es la
 * cuenta que recibió la parte por transferencia. Las ventas mixtas anteriores quedan en NULL
 * (no se sabe cómo se repartieron) y se siguen contando completas como transferencia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('monto_efectivo', 18, 2)->nullable()->after('id_cuenta_bancaria');
            $table->decimal('monto_transferencia', 18, 2)->nullable()->after('monto_efectivo');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['monto_efectivo', 'monto_transferencia']);
        });
    }
};
