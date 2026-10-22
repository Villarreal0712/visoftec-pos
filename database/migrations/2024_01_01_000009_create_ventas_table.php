<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->foreignId('id_cliente')->nullable()->constrained('clientes')->nullOnDelete();
            $table->boolean('es_credito')->default(false);
            $table->enum('metodo_pago', ['Efectivo', 'Tarjeta', 'Transferencia', 'Crédito', 'Mixto'])
                  ->default('Efectivo');
            $table->timestamp('fecha_vencimiento')->nullable()
                  ->comment('Solo aplica para ventas a crédito');
            $table->decimal('saldo_pendiente', 18, 2)->default(0);
            $table->enum('estado_credito', ['Pagado', 'Parcial', 'Mora'])->default('Pagado');
            $table->decimal('total', 18, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('id_usuario');
            $table->index('id_cliente');
            $table->index('es_credito');
            $table->index('estado_credito');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
