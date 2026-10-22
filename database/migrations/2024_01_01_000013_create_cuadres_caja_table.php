<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuadres_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->decimal('saldo_inicial', 18, 2)->default(0);
            $table->decimal('ventas_efectivo', 18, 2)->default(0);
            $table->decimal('ventas_tarjeta', 18, 2)->default(0);
            $table->decimal('ventas_transferencia', 18, 2)->default(0);
            $table->decimal('pagos_credito', 18, 2)->default(0);
            $table->decimal('gastos', 18, 2)->default(0);
            $table->decimal('total_sistema', 18, 2)->default(0);
            $table->decimal('total_contado', 18, 2)->default(0);
            $table->decimal('diferencia', 18, 2)->default(0);
            $table->string('justificacion')->nullable();
            $table->enum('estado', ['Abierto', 'Cerrado'])->default('Abierto');
            $table->json('desglose_billetes')->nullable()
                  ->comment('JSON con conteo de billetes/monedas');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('id_usuario');
            $table->index('fecha_apertura');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuadres_caja');
    }
};
