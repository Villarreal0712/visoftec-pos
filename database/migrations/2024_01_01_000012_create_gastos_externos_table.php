<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_externos', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 200);
            $table->string('categoria', 100)->default('Otros');
            $table->decimal('valor', 18, 2);
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->string('comprobante')->nullable()
                  ->comment('Ruta del archivo adjunto');
            $table->text('observacion')->nullable();
            $table->enum('tipo', ['Externo', 'Local'])->default('Externo');
            $table->timestamps();

            $table->index('categoria');
            $table->index('tipo');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_externos');
    }
};
