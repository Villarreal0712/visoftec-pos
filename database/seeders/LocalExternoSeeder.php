<?php

namespace Database\Seeders;

use App\Models\LocalExterno;
use Illuminate\Database\Seeder;

class LocalExternoSeeder extends Seeder
{
    public function run(): void
    {
        $locales = [
            [
                'nombre_local' => 'Tienda Norte',
                'contacto' => 'Pedro Ramírez',
                'telefono' => '3001234567',
                'direccion' => 'Calle 50 # 10-25',
                'activo' => true,
            ],
            [
                'nombre_local' => 'Sucursal Centro Comercial',
                'contacto' => 'Sandra Morales',
                'telefono' => '3119876543',
                'direccion' => 'CC Plaza Mayor, Local 45',
                'activo' => true,
            ],
        ];

        foreach ($locales as $local) {
            LocalExterno::firstOrCreate(['nombre_local' => $local['nombre_local']], $local);
        }
    }
}
