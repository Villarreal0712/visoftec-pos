<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->enum('ancho_ticket', ['58mm', '80mm'])->default('80mm')
                  ->after('mensaje_ticket')
                  ->comment('Ancho del papel de la impresora térmica');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn('ancho_ticket');
        });
    }
};
