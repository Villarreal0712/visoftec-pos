<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_venta')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->decimal('monto', 18, 2);
            $table->enum('metodo', ['Efectivo', 'Tarjeta', 'Transferencia'])->default('Efectivo');
            $table->string('observacion', 200)->nullable();
            $table->timestamps();

            $table->index('id_venta');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_credito');
    }
};
