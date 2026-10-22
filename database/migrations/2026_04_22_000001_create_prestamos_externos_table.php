<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos_externos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_venta')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('id_historial_venta')->constrained('historial_ventas')->restrictOnDelete();
            $table->foreignId('id_local_externo')->constrained('locales_externos')->restrictOnDelete();
            $table->foreignId('id_detalle_producto')->constrained('detalle_productos')->restrictOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio_venta', 18, 2)->comment('A cuánto se vendió al cliente');
            $table->decimal('costo_acordado', 18, 2)->nullable()->comment('Lo que cobra el local por unidad. Nullable hasta cuadrar.');
            $table->enum('estado', ['pendiente', 'liquidado', 'anulado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('id_local_externo');
            $table->index('estado');
            $table->index('id_venta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos_externos');
    }
};
