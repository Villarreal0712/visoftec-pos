<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('banco', 100)->nullable();
            // Efectivo = la caja física (su saldo lo sigue manejando el Cuadre de Caja, no esta tabla).
            // Banco = cuenta bancaria/digital real, con saldo propio que sí se lleva aquí.
            $table->enum('tipo', ['Efectivo', 'Banco'])->default('Banco');
            $table->decimal('saldo_actual', 18, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
