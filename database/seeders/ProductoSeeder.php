<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\DetalleProducto;
use App\Models\HistorialMovimiento;
use App\Models\LoteInventario;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::where('usuario', 'admin')->first();

        $productos = [
            [
                'nombre' => 'Camiseta Básica',
                'categoria' => 'Ropa',
                'descripcion' => 'Camiseta de algodón 100%, disponible en varias tallas',
                'precio_venta' => 25000,
                'stock_minimo' => 5,
                'detalles' => [
                    ['talla' => 'S', 'codigo_barras' => 'CAM-001-S', 'costo' => 12000, 'cantidad' => 15],
                    ['talla' => 'M', 'codigo_barras' => 'CAM-001-M', 'costo' => 12000, 'cantidad' => 20],
                    ['talla' => 'L', 'codigo_barras' => 'CAM-001-L', 'costo' => 12000, 'cantidad' => 18],
                    ['talla' => 'XL', 'codigo_barras' => 'CAM-001-XL', 'costo' => 13000, 'cantidad' => 10],
                ],
            ],
            [
                'nombre' => 'Jean Clásico',
                'categoria' => 'Pantalones',
                'descripcion' => 'Jean de corte recto, denim 100%',
                'precio_venta' => 85000,
                'stock_minimo' => 3,
                'detalles' => [
                    ['talla' => '28', 'codigo_barras' => 'JEA-001-28', 'costo' => 45000, 'cantidad' => 8],
                    ['talla' => '30', 'codigo_barras' => 'JEA-001-30', 'costo' => 45000, 'cantidad' => 12],
                    ['talla' => '32', 'codigo_barras' => 'JEA-001-32', 'costo' => 45000, 'cantidad' => 10],
                    ['talla' => '34', 'codigo_barras' => 'JEA-001-34', 'costo' => 47000, 'cantidad' => 6],
                ],
            ],
            [
                'nombre' => 'Zapatillas Deportivas',
                'categoria' => 'Calzado',
                'descripcion' => 'Zapatillas para running, suela antideslizante',
                'precio_venta' => 120000,
                'stock_minimo' => 2,
                'detalles' => [
                    ['talla' => '38', 'codigo_barras' => 'ZAP-001-38', 'costo' => 65000, 'cantidad' => 5],
                    ['talla' => '40', 'codigo_barras' => 'ZAP-001-40', 'costo' => 65000, 'cantidad' => 7],
                    ['talla' => '42', 'codigo_barras' => 'ZAP-001-42', 'costo' => 65000, 'cantidad' => 4],
                    ['talla' => '44', 'codigo_barras' => 'ZAP-001-44', 'costo' => 68000, 'cantidad' => 3],
                ],
            ],
            [
                'nombre' => 'Chaqueta Deportiva',
                'categoria' => 'Ropa',
                'descripcion' => 'Chaqueta con capucha, material impermeable',
                'precio_venta' => 150000,
                'stock_minimo' => 2,
                'detalles' => [
                    ['talla' => 'S', 'codigo_barras' => 'CHA-001-S', 'costo' => 80000, 'cantidad' => 4],
                    ['talla' => 'M', 'codigo_barras' => 'CHA-001-M', 'costo' => 80000, 'cantidad' => 6],
                    ['talla' => 'L', 'codigo_barras' => 'CHA-001-L', 'costo' => 80000, 'cantidad' => 5],
                ],
            ],
            [
                'nombre' => 'Gorra Bordada',
                'categoria' => 'Accesorios',
                'descripcion' => 'Gorra ajustable con bordado frontal',
                'precio_venta' => 35000,
                'stock_minimo' => 5,
                'detalles' => [
                    ['talla' => 'Única', 'codigo_barras' => 'GOR-001-U', 'costo' => 18000, 'cantidad' => 25],
                ],
            ],
            [
                'nombre' => 'Medias Deportivas Pack x3',
                'categoria' => 'Accesorios',
                'descripcion' => 'Pack de 3 pares de medias deportivas',
                'precio_venta' => 15000,
                'stock_minimo' => 10,
                'detalles' => [
                    ['talla' => 'Única', 'codigo_barras' => 'MED-001-U', 'costo' => 7000, 'cantidad' => 40],
                ],
            ],
            [
                'nombre' => 'Pantalón Sudadera',
                'categoria' => 'Pantalones',
                'descripcion' => 'Sudadera cómoda para uso casual o deportivo',
                'precio_venta' => 45000,
                'stock_minimo' => 3,
                'detalles' => [
                    ['talla' => 'S', 'codigo_barras' => 'SUD-001-S', 'costo' => 22000, 'cantidad' => 8],
                    ['talla' => 'M', 'codigo_barras' => 'SUD-001-M', 'costo' => 22000, 'cantidad' => 12],
                    ['talla' => 'L', 'codigo_barras' => 'SUD-001-L', 'costo' => 22000, 'cantidad' => 10],
                    ['talla' => 'XL', 'codigo_barras' => 'SUD-001-XL', 'costo' => 24000, 'cantidad' => 5],
                ],
            ],
            [
                'nombre' => 'Blusa Manga Corta',
                'categoria' => 'Ropa',
                'descripcion' => 'Blusa elegante para mujer, varios colores',
                'precio_venta' => 30000,
                'stock_minimo' => 5,
                'detalles' => [
                    ['talla' => 'XS', 'codigo_barras' => 'BLU-001-XS', 'costo' => 14000, 'cantidad' => 7],
                    ['talla' => 'S', 'codigo_barras' => 'BLU-001-S', 'costo' => 14000, 'cantidad' => 10],
                    ['talla' => 'M', 'codigo_barras' => 'BLU-001-M', 'costo' => 14000, 'cantidad' => 12],
                    ['talla' => 'L', 'codigo_barras' => 'BLU-001-L', 'costo' => 14000, 'cantidad' => 8],
                ],
            ],
            [
                'nombre' => 'Cinturón de Cuero',
                'categoria' => 'Accesorios',
                'descripcion' => 'Cinturón de cuero genuino, hebilla metálica',
                'precio_venta' => 40000,
                'stock_minimo' => 3,
                'detalles' => [
                    ['talla' => 'S (85cm)', 'codigo_barras' => 'CIN-001-S', 'costo' => 20000, 'cantidad' => 5],
                    ['talla' => 'M (95cm)', 'codigo_barras' => 'CIN-001-M', 'costo' => 20000, 'cantidad' => 8],
                    ['talla' => 'L (105cm)', 'codigo_barras' => 'CIN-001-L', 'costo' => 20000, 'cantidad' => 6],
                ],
            ],
            [
                'nombre' => 'Sandalias Casual',
                'categoria' => 'Calzado',
                'descripcion' => 'Sandalias cómodas para uso diario',
                'precio_venta' => 55000,
                'stock_minimo' => 2,
                'detalles' => [
                    ['talla' => '36', 'codigo_barras' => 'SAN-001-36', 'costo' => 28000, 'cantidad' => 4],
                    ['talla' => '38', 'codigo_barras' => 'SAN-001-38', 'costo' => 28000, 'cantidad' => 6],
                    ['talla' => '40', 'codigo_barras' => 'SAN-001-40', 'costo' => 28000, 'cantidad' => 5],
                ],
            ],
        ];

        foreach ($productos as $productoData) {
            $detallesData = $productoData['detalles'];
            unset($productoData['detalles']);

            $categoriaNombre = $productoData['categoria'];
            unset($productoData['categoria']);
            $categoria = Categoria::firstOrCreate(['nombre' => $categoriaNombre]);
            $productoData['id_categoria'] = $categoria->id;

            $producto = Producto::firstOrCreate(
                ['nombre' => $productoData['nombre']],
                $productoData
            );

            foreach ($detallesData as $detalleData) {
                $cantidad = $detalleData['cantidad'];
                $costo = $detalleData['costo'];
                unset($detalleData['cantidad'], $detalleData['costo']);
                $detalleData['id_producto'] = $producto->id;

                $detalle = DetalleProducto::firstOrCreate(
                    ['codigo_barras' => $detalleData['codigo_barras']],
                    $detalleData
                );

                // Crear movimiento de entrada
                $movimiento = HistorialMovimiento::create([
                    'id_detalle_producto' => $detalle->id,
                    'tipo_operacion' => HistorialMovimiento::TIPO_ENTRADA,
                    'tipo_origen' => LoteInventario::TIPO_PROPIO,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo,
                    'id_usuario' => $usuario->id,
                    'observacion' => 'Inventario inicial - seeder',
                ]);

                // Crear lote de inventario
                if (!LoteInventario::where('id_movimiento_origen', $movimiento->id)->exists()) {
                    LoteInventario::create([
                        'id_detalle_producto' => $detalle->id,
                        'fecha_ingreso' => now()->subDays(rand(1, 30)),
                        'cantidad_disponible' => $cantidad,
                        'costo_unitario' => $costo,
                        'tipo_origen' => LoteInventario::TIPO_PROPIO,
                        'id_movimiento_origen' => $movimiento->id,
                    ]);
                }
            }
        }
    }
}
