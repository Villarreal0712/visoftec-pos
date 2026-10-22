<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('documento', 20)->unique();
            $table->string('nombre', 100);
            $table->string('telefono', 50)->nullable();
            $table->string('direccion', 150)->nullable();
            $table->string('correo', 100)->nullable();
            $table->decimal('limite_credito', 18, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
