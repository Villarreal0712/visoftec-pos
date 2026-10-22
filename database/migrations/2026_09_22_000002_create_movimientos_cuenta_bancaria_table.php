<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_cuenta_bancaria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cuenta_bancaria')->constrained('cuentas_bancarias')->restrictOnDelete();
            $table->foreignId('id_venta')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->enum('tipo', ['Venta', 'Abono', 'Consignacion', 'Retiro', 'Ajuste']);
            $table->decimal('monto', 18, 2);
            $table->decimal('saldo_resultante', 18, 2);
            $table->string('observacion', 255)->nullable();
            $table->timestamps();

            $table->index('id_cuenta_bancaria');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_cuenta_bancaria');
    }
};
