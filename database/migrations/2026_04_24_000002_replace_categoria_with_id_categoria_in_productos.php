<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['categoria']);
            $table->dropColumn('categoria');
            $table->foreignId('id_categoria')->nullable()->after('nombre')
                  ->constrained('categorias')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['id_categoria']);
            $table->dropColumn('id_categoria');
            $table->string('categoria', 100)->nullable()->after('nombre');
            $table->index('categoria');
        });
    }
};
