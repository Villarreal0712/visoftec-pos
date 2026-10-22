<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidaciones_prestamo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_local_externo')->constrained('locales_externos')->restrictOnDelete();
            $table->decimal('monto', 18, 2);
            $table->string('metodo_pago', 50)->default('Efectivo');
            $table->text('observaciones')->nullable();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('id_local_externo');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones_prestamo');
    }
};
