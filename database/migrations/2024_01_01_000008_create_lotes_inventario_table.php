<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_detalle_producto')->constrained('detalle_productos')->restrictOnDelete();
            $table->timestamp('fecha_ingreso')->useCurrent();
            $table->integer('cantidad_disponible')->default(0);
            $table->decimal('costo_unitario', 18, 2);
            $table->enum('tipo_origen', ['Propio', 'Prestado'])->default('Propio');
            $table->foreignId('id_movimiento_origen')->nullable()
                  ->constrained('historial_movimientos')->nullOnDelete();
            $table->timestamps();

            $table->index('id_detalle_producto', 'idx_lotes_detalle');
            $table->index('fecha_ingreso', 'idx_lotes_fecha');
            // Índice compuesto para consultas FIFO
            $table->index(['id_detalle_producto', 'fecha_ingreso', 'cantidad_disponible'], 'idx_lotes_fifo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_inventario');
    }
};
