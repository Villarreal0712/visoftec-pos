<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // lotes_inventario → bodega (la mercancía física vive en una bodega)
        Schema::table('lotes_inventario', function (Blueprint $table) {
            $table->foreignId('id_bodega')
                  ->nullable()
                  ->after('id_detalle_producto')
                  ->constrained('bodegas')
                  ->nullOnDelete();
            $table->index('id_bodega');
        });

        // entradas_mercancia → bodega (la entrada apunta a la bodega destino)
        Schema::table('entradas_mercancia', function (Blueprint $table) {
            $table->foreignId('id_bodega')
                  ->nullable()
                  ->after('id_usuario')
                  ->constrained('bodegas')
                  ->nullOnDelete();
            $table->index('id_bodega');
        });

        // ventas → sucursal (la venta se registra desde una sucursal)
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('id_sucursal')
                  ->nullable()
                  ->after('id_usuario')
                  ->constrained('sucursales')
                  ->nullOnDelete();
            $table->index('id_sucursal');
        });

        // cuadres_caja → sucursal (el cuadre pertenece a una sucursal)
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->foreignId('id_sucursal')
                  ->nullable()
                  ->after('id_usuario')
                  ->constrained('sucursales')
                  ->nullOnDelete();
            $table->index('id_sucursal');
        });

        // gastos_externos → sucursal (el gasto lo genera una sucursal)
        Schema::table('gastos_externos', function (Blueprint $table) {
            $table->foreignId('id_sucursal')
                  ->nullable()
                  ->after('id_usuario')
                  ->constrained('sucursales')
                  ->nullOnDelete();
            $table->index('id_sucursal');
        });

        // users → sucursal (el usuario opera desde una sucursal)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('id_sucursal')
                  ->nullable()
                  ->after('activo')
                  ->constrained('sucursales')
                  ->nullOnDelete();
            $table->index('id_sucursal');
        });

        // Asignar todos los registros existentes a la sucursal/bodega por defecto
        DB::table('lotes_inventario')->whereNull('id_bodega')->update(['id_bodega' => 1]);
        DB::table('entradas_mercancia')->whereNull('id_bodega')->update(['id_bodega' => 1]);
        DB::table('ventas')->whereNull('id_sucursal')->update(['id_sucursal' => 1]);
        DB::table('cuadres_caja')->whereNull('id_sucursal')->update(['id_sucursal' => 1]);
        DB::table('gastos_externos')->whereNull('id_sucursal')->update(['id_sucursal' => 1]);
        DB::table('users')->whereNull('id_sucursal')->update(['id_sucursal' => 1]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_sucursal']);
            $table->dropColumn('id_sucursal');
        });
        Schema::table('gastos_externos', function (Blueprint $table) {
            $table->dropForeign(['id_sucursal']);
            $table->dropColumn('id_sucursal');
        });
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->dropForeign(['id_sucursal']);
            $table->dropColumn('id_sucursal');
        });
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['id_sucursal']);
            $table->dropColumn('id_sucursal');
        });
        Schema::table('entradas_mercancia', function (Blueprint $table) {
            $table->dropForeign(['id_bodega']);
            $table->dropColumn('id_bodega');
        });
        Schema::table('lotes_inventario', function (Blueprint $table) {
            $table->dropForeign(['id_bodega']);
            $table->dropColumn('id_bodega');
        });
    }
};
