<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE lotes_inventario MODIFY tipo_origen ENUM('Propio','Prestado','Directo') NOT NULL DEFAULT 'Propio'");
        DB::statement("ALTER TABLE historial_movimientos MODIFY tipo_origen ENUM('Propio','Prestado','Directo') NOT NULL DEFAULT 'Propio'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE lotes_inventario MODIFY tipo_origen ENUM('Propio','Prestado') NOT NULL DEFAULT 'Propio'");
        DB::statement("ALTER TABLE historial_movimientos MODIFY tipo_origen ENUM('Propio','Prestado') NOT NULL DEFAULT 'Propio'");
    }
};
