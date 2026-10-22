<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('id_cuenta_bancaria')->nullable()->after('metodo_pago')
                  ->constrained('cuentas_bancarias')->nullOnDelete();
        });

        Schema::table('pagos_credito', function (Blueprint $table) {
            $table->foreignId('id_cuenta_bancaria')->nullable()->after('metodo')
                  ->constrained('cuentas_bancarias')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_cuenta_bancaria');
        });

        Schema::table('pagos_credito', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_cuenta_bancaria');
        });
    }
};
