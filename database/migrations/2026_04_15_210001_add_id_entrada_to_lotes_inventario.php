<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes_inventario', function (Blueprint $table) {
            $table->foreignId('id_entrada')->nullable()->after('id_movimiento_origen')
                  ->constrained('entradas_mercancia')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lotes_inventario', function (Blueprint $table) {
            $table->dropForeign(['id_entrada']);
            $table->dropColumn('id_entrada');
        });
    }
};
