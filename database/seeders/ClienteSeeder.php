<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = [
            [
                'documento' => '1234567890',
                'nombre' => 'Juan Pérez Rodríguez',
                'telefono' => '3001234567',
                'direccion' => 'Calle 10 # 5-23, Barrio Centro',
                'correo' => 'juan.perez@email.com',
                'limite_credito' => 500000,
                'activo' => true,
            ],
            [
                'documento' => '0987654321',
                'nombre' => 'Ana Martínez López',
                'telefono' => '3109876543',
                'direccion' => 'Carrera 15 # 8-45, Barrio Norte',
                'correo' => 'ana.martinez@email.com',
                'limite_credito' => 300000,
                'activo' => true,
            ],
            [
                'documento' => '1122334455',
                'nombre' => 'Luis Hernández García',
                'telefono' => '3201122334',
                'direccion' => 'Avenida 20 # 12-67',
                'correo' => 'luis.hernandez@email.com',
                'limite_credito' => 800000,
                'activo' => true,
            ],
            [
                'documento' => '5544332211',
                'nombre' => 'Carmen Díaz Morales',
                'telefono' => '3115544332',
                'direccion' => 'Calle 5 # 3-89, Barrio Sur',
                'correo' => 'carmen.diaz@email.com',
                'limite_credito' => 200000,
                'activo' => true,
            ],
            [
                'documento' => '9988776655',
                'nombre' => 'Roberto Gómez Vargas',
                'telefono' => '3009988776',
                'direccion' => 'Transversal 8 # 22-10',
                'correo' => null,
                'limite_credito' => 1000000,
                'activo' => true,
            ],
        ];

        foreach ($clientes as $cliente) {
            Cliente::firstOrCreate(['documento' => $cliente['documento']], $cliente);
        }
    }
}
