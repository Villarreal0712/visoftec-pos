<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // RolesPermissionsSeeder es estructural (roles, permisos, la cuenta Efectivo) y
        // siempre debe ejecutarse, incluso en producción.
        $this->call(RolesPermissionsSeeder::class);

        // Los siguientes son datos de EJEMPLO (usuarios con contraseñas predecibles,
        // clientes y productos ficticios) — nunca deben correr en un servidor real.
        if (app()->environment('production')) {
            $this->command?->warn('Entorno de producción detectado: se omiten los seeders de datos de ejemplo (usuarios, clientes, productos, locales demo). Crea el usuario Administrador real manualmente.');
            return;
        }

        $this->call([
            UserSeeder::class,
            ClienteSeeder::class,
            ProductoSeeder::class,
            LocalExternoSeeder::class,
        ]);
    }
}
