<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Administrador
        $admin = User::firstOrCreate(
            ['usuario' => 'admin'],
            [
                'nombre' => 'Administrador Sistema',
                'email' => 'admin@tienda.com',
                'password' => Hash::make('admin123'),
                'activo' => true,
                'id_sucursal' => 1,
                'id_bodega' => 1,
            ]
        );
        $admin->assignRole('Administrador');

        // Vendedor
        $vendedor = User::firstOrCreate(
            ['usuario' => 'vendedor1'],
            [
                'nombre' => 'María García',
                'email' => 'maria@tienda.com',
                'password' => Hash::make('vendedor123'),
                'activo' => true,
                'id_sucursal' => 1,
                'id_bodega' => 1,
            ]
        );
        $vendedor->assignRole('Vendedor');

        // Almacenista
        $almacenista = User::firstOrCreate(
            ['usuario' => 'almacenista1'],
            [
                'nombre' => 'Carlos López',
                'email' => 'carlos@tienda.com',
                'password' => Hash::make('almacen123'),
                'activo' => true,
                'id_sucursal' => 1,
                'id_bodega' => 1,
            ]
        );
        $almacenista->assignRole('Almacenista');
    }
}
