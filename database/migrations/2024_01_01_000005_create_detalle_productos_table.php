<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_producto')->constrained('productos')->restrictOnDelete();
            $table->string('talla', 20)->nullable();
            $table->string('codigo_barras', 50)->nullable()->unique();
            $table->decimal('precio_venta', 18, 2)->nullable()
                  ->comment('Si null, usa el precio del producto padre');
            $table->timestamps();

            $table->index('id_producto');
            $table->index('codigo_barras');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_productos');
    }
};
