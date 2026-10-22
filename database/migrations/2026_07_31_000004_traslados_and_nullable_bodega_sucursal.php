<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bodegas independientes — id_sucursal deja de ser obligatorio
        Schema::table('bodegas', function (Blueprint $table) {
            $table->dropForeign(['id_sucursal']);
            $table->foreignId('id_sucursal')
                  ->nullable()
                  ->change();
            $table->foreign('id_sucursal')
                  ->references('id')->on('sucursales')
                  ->nullOnDelete();
        });

        // Tabla principal del traslado
        Schema::create('traslados', function (Blueprint $table) {
            $table->id();
            $table->string('numero_traslado', 20)->unique();
            $table->foreignId('id_bodega_origen')
                  ->constrained('bodegas')->restrictOnDelete();
            $table->foreignId('id_bodega_destino')
                  ->constrained('bodegas')->restrictOnDelete();
            $table->foreignId('id_usuario')
                  ->constrained('users')->restrictOnDelete();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index('id_bodega_origen');
            $table->index('id_bodega_destino');
            $table->index('id_usuario');
            $table->index('created_at');
        });

        // Ítems del traslado
        Schema::create('detalle_traslados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_traslado')
                  ->constrained('traslados')->cascadeOnDelete();
            $table->foreignId('id_detalle_producto')
                  ->constrained('detalle_productos')->restrictOnDelete();
            $table->integer('cantidad');
            $table->decimal('costo_unitario', 18, 2)->default(0);
            $table->timestamps();

            $table->index('id_traslado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_traslados');
        Schema::dropIfExists('traslados');

        Schema::table('bodegas', function (Blueprint $table) {
            $table->dropForeign(['id_sucursal']);
            $table->foreignId('id_sucursal')
                  ->nullable(false)
                  ->change();
            $table->foreign('id_sucursal')
                  ->references('id')->on('sucursales')
                  ->restrictOnDelete();
        });
    }
};
