<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entradas_mercancia', function (Blueprint $table) {
            $table->id();
            $table->string('numero_entrada', 20)->unique();
            $table->enum('tipo_origen', ['Propio', 'Prestado'])->default('Propio');
            $table->foreignId('id_local_externo')->nullable()->constrained('locales_externos')->nullOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->string('observacion', 300)->nullable();
            $table->timestamps();

            $table->index('numero_entrada');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entradas_mercancia');
    }
};
