<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_venta')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('id_lote')->constrained('lotes_inventario')->restrictOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio_venta', 18, 2);
            $table->decimal('costo_unitario', 18, 2)->default(0)
                  ->comment('Costo del lote al momento de la venta');
            $table->timestamps();

            $table->index('id_venta');
            $table->index('id_lote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_ventas');
    }
};
