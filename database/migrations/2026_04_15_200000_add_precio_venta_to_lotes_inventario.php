<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes_inventario', function (Blueprint $table) {
            $table->decimal('precio_venta', 18, 2)->nullable()->after('costo_unitario');
        });
    }

    public function down(): void
    {
        Schema::table('lotes_inventario', function (Blueprint $table) {
            $table->dropColumn('precio_venta');
        });
    }
};
