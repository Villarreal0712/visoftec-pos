<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_detalle_producto')->constrained('detalle_productos')->restrictOnDelete();
            $table->enum('tipo_operacion', ['Entrada', 'Salida', 'Ajuste', 'Transferencia', 'Venta', 'Merma']);
            $table->enum('tipo_origen', ['Propio', 'Prestado'])->default('Propio');
            $table->integer('cantidad');
            $table->decimal('costo_unitario', 18, 2)->nullable();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->foreignId('id_local_externo')->nullable()->constrained('locales_externos')->nullOnDelete();
            $table->string('observacion', 200)->nullable();
            $table->timestamps();

            $table->index('id_detalle_producto');
            $table->index('tipo_operacion');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_movimientos');
    }
};
