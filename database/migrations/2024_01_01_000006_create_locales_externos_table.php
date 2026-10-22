<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locales_externos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_local', 100);
            $table->string('contacto', 100)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locales_externos');
    }
};
