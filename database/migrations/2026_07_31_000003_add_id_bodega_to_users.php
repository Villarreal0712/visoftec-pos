<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('id_bodega')
                  ->nullable()
                  ->after('id_sucursal')
                  ->constrained('bodegas')
                  ->nullOnDelete();
        });

        DB::table('users')->whereNull('id_bodega')->update(['id_bodega' => 1]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_bodega']);
            $table->dropColumn('id_bodega');
        });
    }
};
