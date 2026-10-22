<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sucursales sin id_bodega_default para romper la dependencia circular
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('bodegas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_sucursal')->constrained('sucursales')->restrictOnDelete();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('id_sucursal');
            $table->index('activo');
        });

        // Insertar sucursal y bodega por defecto
        DB::table('sucursales')->insert([
            'id'         => 1,
            'nombre'     => 'Principal',
            'activo'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('bodegas')->insert([
            'id'           => 1,
            'id_sucursal'  => 1,
            'nombre'       => 'Principal',
            'activo'       => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Ahora sí agregar la FK a bodega por defecto en sucursales
        Schema::table('sucursales', function (Blueprint $table) {
            $table->foreignId('id_bodega_default')
                  ->nullable()
                  ->after('telefono')
                  ->constrained('bodegas')
                  ->nullOnDelete();
        });

        DB::table('sucursales')->where('id', 1)->update(['id_bodega_default' => 1]);
    }

    public function down(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropForeign(['id_bodega_default']);
            $table->dropColumn('id_bodega_default');
        });
        Schema::dropIfExists('bodegas');
        Schema::dropIfExists('sucursales');
    }
};
