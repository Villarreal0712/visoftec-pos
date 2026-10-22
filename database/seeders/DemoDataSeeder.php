<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\CuadreCaja;
use App\Models\DetalleProducto;
use App\Models\EntradaMercancia;
use App\Models\GastoExterno;
use App\Models\HistorialMovimiento;
use App\Models\HistorialVenta;
use App\Models\LocalExterno;
use App\Models\LoteInventario;
use App\Models\PagoCredito;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ══════════════════════════════════════════════════
        // CONFIGURACIÓN DEL COMERCIO
        // ══════════════════════════════════════════════════
        Configuracion::firstOrCreate(['id' => 1], [
            'nombre_comercio' => 'Perfumería Santorini',
            'nit'             => '900.456.789-1',
            'direccion'       => 'Cra 45 # 52-30, Centro Comercial Oviedo',
            'telefono'        => '604 321 9876',
            'email'           => 'ventas@santorini.co',
            'ciudad'          => 'Medellín, Antioquia',
            'mensaje_ticket'  => '¡Gracias por elegirnos! Cambios y devoluciones en 8 días con ticket.',
        ]);

        // ══════════════════════════════════════════════════
        // USUARIOS
        // ══════════════════════════════════════════════════
        $admin = User::firstOrCreate(['usuario' => 'admin'], [
            'nombre'   => 'Administrador Sistema',
            'email'    => 'admin@santorini.co',
            'password' => Hash::make('admin123'),
            'activo'   => true,
            'id_sucursal' => 1,
            'id_bodega'   => 1,
        ]);
        if (!$admin->hasRole('Administrador')) {
            $admin->assignRole('Administrador');
        }

        $vendedor = User::firstOrCreate(['usuario' => 'maria'], [
            'nombre'   => 'María García',
            'email'    => 'maria@santorini.co',
            'password' => Hash::make('vendedor123'),
            'activo'   => true,
            'id_sucursal' => 1,
            'id_bodega'   => 1,
        ]);
        if (!$vendedor->hasRole('Vendedor') && Role::where('name', 'Vendedor')->exists()) {
            $vendedor->syncRoles(['Vendedor']);
        }

        $almacenista = User::firstOrCreate(['usuario' => 'carlos'], [
            'nombre'   => 'Carlos López',
            'email'    => 'carlos@santorini.co',
            'password' => Hash::make('almacen123'),
            'activo'   => true,
            'id_sucursal' => 1,
            'id_bodega'   => 1,
        ]);
        if (!$almacenista->hasRole('Almacenista') && Role::where('name', 'Almacenista')->exists()) {
            $almacenista->syncRoles(['Almacenista']);
        }

        // ══════════════════════════════════════════════════
        // CATEGORÍAS
        // ══════════════════════════════════════════════════
        $cats = [];
        $catDatos = [
            ['nombre' => 'Perfumes Mujer',     'descripcion' => 'Fragancias femeninas importadas'],
            ['nombre' => 'Perfumes Hombre',    'descripcion' => 'Fragancias masculinas importadas'],
            ['nombre' => 'Cremas y Lociones',  'descripcion' => 'Hidratantes, corporales y faciales'],
            ['nombre' => 'Maquillaje',         'descripcion' => 'Labiales, bases y accesorios'],
            ['nombre' => 'Cuidado Personal',   'descripcion' => 'Shampoo, body splash y más'],
        ];
        foreach ($catDatos as $cd) {
            $cats[$cd['nombre']] = Categoria::firstOrCreate(
                ['nombre' => $cd['nombre']],
                ['descripcion' => $cd['descripcion'], 'activo' => true]
            );
        }

        // ══════════════════════════════════════════════════
        // LOCALES EXTERNOS (proveedores en consignación)
        // ══════════════════════════════════════════════════
        $local1 = LocalExterno::firstOrCreate(['nombre_local' => 'Distribuidora Fragancias SA'], [
            'contacto' => 'Pedro Ruiz',
            'telefono' => '310 555 0011',
            'activo'   => true,
        ]);
        $local2 = LocalExterno::firstOrCreate(['nombre_local' => 'Importadora Belleza Total'], [
            'contacto' => 'Sofía Herrera',
            'telefono' => '315 777 2233',
            'activo'   => true,
        ]);

        // ══════════════════════════════════════════════════
        // CLIENTES
        // ══════════════════════════════════════════════════
        $clientesDatos = [
            ['documento' => '1001234567', 'nombre' => 'Ana María Rodríguez',  'telefono' => '3201234567', 'correo' => 'ana@gmail.com',    'limite_credito' => 500000],
            ['documento' => '1009876543', 'nombre' => 'Jorge Alberto Gómez',  'telefono' => '3109876543', 'correo' => 'jorge@gmail.com',   'limite_credito' => 300000],
            ['documento' => '1005678901', 'nombre' => 'Laura Cristina Pérez', 'telefono' => '3005678901', 'correo' => 'laura@hotmail.com', 'limite_credito' => 800000],
            ['documento' => '1003456789', 'nombre' => 'Miguel Ángel Torres',  'telefono' => '3123456789', 'correo' => null,                'limite_credito' => 200000],
            ['documento' => '1007654321', 'nombre' => 'Sandra Patricia López','telefono' => '3217654321', 'correo' => 'sandra@gmail.com',  'limite_credito' => 400000],
            ['documento' => '1002345678', 'nombre' => 'Roberto Mejía',        'telefono' => '3002345678', 'correo' => null,                'limite_credito' => 150000],
            ['documento' => '9001234567', 'nombre' => 'Empresa XYZ S.A.S.',   'telefono' => '6045551234', 'correo' => 'compras@xyz.com',   'limite_credito' => 2000000],
            ['documento' => '1008765432', 'nombre' => 'Diana Vargas',         'telefono' => '3168765432', 'correo' => 'diana@yahoo.com',   'limite_credito' => 350000],
        ];
        $clientes = [];
        foreach ($clientesDatos as $cd) {
            $clientes[$cd['documento']] = Cliente::firstOrCreate(
                ['documento' => $cd['documento']],
                array_merge($cd, ['activo' => true])
            );
        }

        // ══════════════════════════════════════════════════
        // PRODUCTOS + VARIANTES
        // ══════════════════════════════════════════════════
        $productosDatos = [
            [
                'nombre'      => 'Chanel N°5',
                'categoria'   => 'Perfumes Mujer',
                'precio_venta'=> 280000,
                'stock_minimo'=> 3,
                'descripcion' => 'Fragancia clásica femenina - Eau de Parfum',
                'tallas'      => ['50ml', '100ml'],
            ],
            [
                'nombre'      => 'Dior Sauvage',
                'categoria'   => 'Perfumes Hombre',
                'precio_venta'=> 320000,
                'stock_minimo'=> 3,
                'descripcion' => 'Fragancia masculina fresca y amaderada - EDT',
                'tallas'      => ['60ml', '100ml'],
            ],
            [
                'nombre'      => 'Dolce & Gabbana Light Blue',
                'categoria'   => 'Perfumes Mujer',
                'precio_venta'=> 240000,
                'stock_minimo'=> 2,
                'descripcion' => 'Fragancia mediterránea fresca y frutal',
                'tallas'      => ['50ml', '100ml'],
            ],
            [
                'nombre'      => 'Carolina Herrera 212',
                'categoria'   => 'Perfumes Mujer',
                'precio_venta'=> 230000,
                'stock_minimo'=> 2,
                'descripcion' => 'Fragancia urbana y moderna - EDT',
                'tallas'      => ['60ml', '100ml'],
            ],
            [
                'nombre'      => 'Acqua di Gio Armani',
                'categoria'   => 'Perfumes Hombre',
                'precio_venta'=> 295000,
                'stock_minimo'=> 2,
                'descripcion' => 'Fragancia acuática y fresca para hombre',
                'tallas'      => ['75ml', '150ml'],
            ],
            [
                'nombre'      => 'Crema Hidratante Nivea',
                'categoria'   => 'Cremas y Lociones',
                'precio_venta'=> 38000,
                'stock_minimo'=> 5,
                'descripcion' => 'Crema corporal hidratación profunda',
                'tallas'      => ['200ml', '400ml'],
            ],
            [
                'nombre'      => 'Labial MAC Retro Matte',
                'categoria'   => 'Maquillaje',
                'precio_venta'=> 95000,
                'stock_minimo'=> 4,
                'descripcion' => 'Labial de larga duración acabado matte',
                'tallas'      => ['Ruby Woo', 'Velvet Teddy', 'Chili'],
            ],
            [
                'nombre'      => 'Body Splash Victoria\'s Secret',
                'categoria'   => 'Cuidado Personal',
                'precio_venta'=> 75000,
                'stock_minimo'=> 4,
                'descripcion' => 'Loción perfumada corporal',
                'tallas'      => ['Bombshell', 'Love Spell', 'Pure Seduction'],
            ],
            [
                'nombre'      => 'Shampoo Pantene Restauración',
                'categoria'   => 'Cuidado Personal',
                'precio_venta'=> 28000,
                'stock_minimo'=> 6,
                'descripcion' => 'Shampoo para cabello dañado y maltratado',
                'tallas'      => ['400ml', '750ml'],
            ],
        ];

        $detallesCreados = []; // ['producto-talla' => DetalleProducto]

        foreach ($productosDatos as $pd) {
            $catId = isset($cats[$pd['categoria']]) ? $cats[$pd['categoria']]->id : null;

            $producto = Producto::firstOrCreate(
                ['nombre' => $pd['nombre']],
                [
                    'id_categoria' => $catId,
                    'descripcion'  => $pd['descripcion'],
                    'precio_venta' => $pd['precio_venta'],
                    'stock_minimo' => $pd['stock_minimo'],
                    'activo'       => true,
                ]
            );

            foreach ($pd['tallas'] as $talla) {
                $clave = $pd['nombre'] . '-' . $talla;
                $detalle = DetalleProducto::firstOrCreate(
                    ['id_producto' => $producto->id, 'talla' => $talla],
                    ['codigo_barras' => DetalleProducto::generarCodigoBarras($producto->id)]
                );
                $detallesCreados[$clave] = $detalle;
            }
        }

        // ══════════════════════════════════════════════════
        // ENTRADAS DE INVENTARIO (crear stock inicial)
        // ══════════════════════════════════════════════════
        // Solo crear si las variantes recién generadas no tienen lotes aún
        $primerDetalle = $detallesCreados['Chanel N°5-50ml'] ?? null;
        $hayLotes = $primerDetalle
            && LoteInventario::where('id_detalle_producto', $primerDetalle->id)->exists();

        if (!$hayLotes) {
            $entrada1 = EntradaMercancia::create([
                'numero_entrada'   => EntradaMercancia::generarNumero(),
                'tipo_origen'      => 'Propio',
                'id_local_externo' => null,
                'id_usuario'       => $admin->id,
                'observacion'      => 'Inventario inicial de apertura',
            ]);

            $stockInicial = [
                // [clave_detalle, cantidad, costo_unitario]
                ['Chanel N°5-50ml',                    8,  180000],
                ['Chanel N°5-100ml',                   5,  220000],
                ['Dior Sauvage-60ml',                  6,  200000],
                ['Dior Sauvage-100ml',                 4,  250000],
                ['Dolce & Gabbana Light Blue-50ml',    7,  150000],
                ['Dolce & Gabbana Light Blue-100ml',   4,  195000],
                ['Carolina Herrera 212-60ml',           6,  145000],
                ['Carolina Herrera 212-100ml',          3,  185000],
                ['Acqua di Gio Armani-75ml',           5,  185000],
                ['Acqua di Gio Armani-150ml',          3,  235000],
                ['Crema Hidratante Nivea-200ml',       12,  18000],
                ['Crema Hidratante Nivea-400ml',        8,  28000],
                ['Labial MAC Retro Matte-Ruby Woo',     8,  52000],
                ['Labial MAC Retro Matte-Velvet Teddy', 6,  52000],
                ['Labial MAC Retro Matte-Chili',        5,  52000],
                ['Body Splash Victoria\'s Secret-Bombshell',     10, 40000],
                ['Body Splash Victoria\'s Secret-Love Spell',     8, 40000],
                ['Body Splash Victoria\'s Secret-Pure Seduction', 7, 40000],
                ['Shampoo Pantene Restauración-400ml', 15,  14000],
                ['Shampoo Pantene Restauración-750ml', 10,  20000],
            ];

            foreach ($stockInicial as [$clave, $cantidad, $costo]) {
                if (!isset($detallesCreados[$clave])) continue;
                $detalle = $detallesCreados[$clave];

                $mov = HistorialMovimiento::create([
                    'id_detalle_producto' => $detalle->id,
                    'tipo_operacion'      => HistorialMovimiento::TIPO_ENTRADA,
                    'tipo_origen'         => 'Propio',
                    'cantidad'            => $cantidad,
                    'costo_unitario'      => $costo,
                    'id_usuario'          => $admin->id,
                    'id_entrada'          => $entrada1->id,
                    'observacion'         => 'Inventario inicial',
                ]);

                LoteInventario::create([
                    'id_detalle_producto'  => $detalle->id,
                    'fecha_ingreso'        => now()->subDays(30),
                    'cantidad_disponible'  => $cantidad,
                    'costo_unitario'       => $costo,
                    'precio_venta'         => null,
                    'tipo_origen'          => 'Propio',
                    'id_movimiento_origen' => $mov->id,
                    'id_entrada'           => $entrada1->id,
                ]);
            }

            // Segunda entrada (más reciente, lote más nuevo para FIFO)
            $entrada2 = EntradaMercancia::create([
                'numero_entrada'   => EntradaMercancia::generarNumero(),
                'tipo_origen'      => 'Propio',
                'id_local_externo' => null,
                'id_usuario'       => $admin->id,
                'observacion'      => 'Reabastecimiento quincenal',
            ]);

            $reabastecimiento = [
                ['Chanel N°5-50ml',           4, 182000],
                ['Dior Sauvage-100ml',         3, 252000],
                ['Crema Hidratante Nivea-200ml', 10, 18500],
                ['Shampoo Pantene Restauración-400ml', 20, 14500],
            ];

            foreach ($reabastecimiento as [$clave, $cantidad, $costo]) {
                if (!isset($detallesCreados[$clave])) continue;
                $detalle = $detallesCreados[$clave];

                $mov = HistorialMovimiento::create([
                    'id_detalle_producto' => $detalle->id,
                    'tipo_operacion'      => HistorialMovimiento::TIPO_ENTRADA,
                    'tipo_origen'         => 'Propio',
                    'cantidad'            => $cantidad,
                    'costo_unitario'      => $costo,
                    'id_usuario'          => $almacenista->id,
                    'id_entrada'          => $entrada2->id,
                    'observacion'         => 'Reabastecimiento quincenal',
                ]);

                LoteInventario::create([
                    'id_detalle_producto'  => $detalle->id,
                    'fecha_ingreso'        => now()->subDays(10),
                    'cantidad_disponible'  => $cantidad,
                    'costo_unitario'       => $costo,
                    'precio_venta'         => null,
                    'tipo_origen'          => 'Propio',
                    'id_movimiento_origen' => $mov->id,
                    'id_entrada'           => $entrada2->id,
                ]);
            }
        }

        // ══════════════════════════════════════════════════
        // GASTOS
        // ══════════════════════════════════════════════════
        $hayGastos = GastoExterno::exists();
        if (!$hayGastos) {
            $gastosDatos = [
                ['descripcion' => 'Arriendo local CC Oviedo',         'categoria' => 'Arriendo',    'valor' => 1500000, 'tipo' => 'Local',    'dias' => 25],
                ['descripcion' => 'Servicio eléctrico y agua',        'categoria' => 'Servicios',   'valor' => 280000,  'tipo' => 'Local',    'dias' => 20],
                ['descripcion' => 'Publicidad redes sociales',        'categoria' => 'Publicidad',  'valor' => 150000,  'tipo' => 'Externo',  'dias' => 15],
                ['descripcion' => 'Bolsas y empaques',                'categoria' => 'Otros',       'valor' => 85000,   'tipo' => 'Local',    'dias' => 12],
                ['descripcion' => 'Transporte mercancía proveedor',   'categoria' => 'Transporte',  'valor' => 120000,  'tipo' => 'Externo',  'dias' => 8],
                ['descripcion' => 'Caja chica semana',                'categoria' => 'Caja Chica',  'valor' => 50000,   'tipo' => 'Local',    'dias' => 3],
            ];

            foreach ($gastosDatos as $gd) {
                GastoExterno::create([
                    'descripcion' => $gd['descripcion'],
                    'categoria'   => $gd['categoria'],
                    'valor'       => $gd['valor'],
                    'tipo'        => $gd['tipo'],
                    'id_usuario'  => $admin->id,
                    'created_at'  => now()->subDays($gd['dias']),
                    'updated_at'  => now()->subDays($gd['dias']),
                ]);
            }
        }

        // ══════════════════════════════════════════════════
        // VENTAS DE EJEMPLO
        // ══════════════════════════════════════════════════
        $hayVentas = Venta::count() > 1;
        if (!$hayVentas) {
            $this->crearVenta([
                'usuario'     => $vendedor,
                'cliente'     => $clientes['1001234567'],   // Ana María
                'metodo_pago' => 'Efectivo',
                'es_credito'  => false,
                'dias_atras'  => 5,
                'items'       => [
                    ['clave' => 'Chanel N°5-50ml',          'cantidad' => 1, 'precio' => 280000],
                    ['clave' => 'Labial MAC Retro Matte-Ruby Woo', 'cantidad' => 1, 'precio' => 95000],
                ],
            ], $detallesCreados);

            $this->crearVenta([
                'usuario'     => $vendedor,
                'cliente'     => null,                       // Público general
                'metodo_pago' => 'Efectivo',
                'es_credito'  => false,
                'dias_atras'  => 4,
                'items'       => [
                    ['clave' => 'Crema Hidratante Nivea-200ml', 'cantidad' => 2, 'precio' => 38000],
                    ['clave' => 'Shampoo Pantene Restauración-400ml', 'cantidad' => 1, 'precio' => 28000],
                ],
            ], $detallesCreados);

            $this->crearVenta([
                'usuario'     => $vendedor,
                'cliente'     => $clientes['9001234567'],   // Empresa XYZ
                'metodo_pago' => 'Transferencia',
                'es_credito'  => false,
                'dias_atras'  => 3,
                'items'       => [
                    ['clave' => 'Dior Sauvage-60ml',   'cantidad' => 2, 'precio' => 320000],
                    ['clave' => 'Chanel N°5-100ml',    'cantidad' => 2, 'precio' => 440000],
                ],
            ], $detallesCreados);

            // Venta a crédito (el método de pago es Efectivo — el crédito es la condición, no el método)
            $ventaCredito = $this->crearVenta([
                'usuario'          => $vendedor,
                'cliente'          => $clientes['1005678901'],  // Laura
                'metodo_pago'      => 'Efectivo',
                'es_credito'       => true,
                'dias_credito'     => 30,
                'dias_atras'       => 2,
                'items'            => [
                    ['clave' => 'Acqua di Gio Armani-75ml',   'cantidad' => 1, 'precio' => 295000],
                    ['clave' => 'Body Splash Victoria\'s Secret-Bombshell', 'cantidad' => 2, 'precio' => 75000],
                ],
            ], $detallesCreados);

            // Abono parcial a la venta crédito
            if ($ventaCredito) {
                PagoCredito::create([
                    'id_venta'   => $ventaCredito->id,
                    'id_usuario' => $vendedor->id,
                    'monto'      => 100000,
                    'metodo'     => 'Efectivo',
                    'observacion'=> 'Abono inicial',
                    'created_at' => now()->subDays(1),
                    'updated_at' => now()->subDays(1),
                ]);
                $ventaCredito->actualizarEstadoCredito();
            }

            // Venta mixto
            $this->crearVenta([
                'usuario'     => $vendedor,
                'cliente'     => $clientes['1007654321'],   // Sandra
                'metodo_pago' => 'Mixto',
                'es_credito'  => false,
                'dias_atras'  => 1,
                'items'       => [
                    ['clave' => 'Carolina Herrera 212-60ml', 'cantidad' => 1, 'precio' => 230000],
                    ['clave' => 'Labial MAC Retro Matte-Velvet Teddy', 'cantidad' => 1, 'precio' => 95000],
                ],
            ], $detallesCreados);

            // Venta de HOY (efectivo)
            $this->crearVenta([
                'usuario'     => $vendedor,
                'cliente'     => null,
                'metodo_pago' => 'Efectivo',
                'es_credito'  => false,
                'dias_atras'  => 0,
                'items'       => [
                    ['clave' => 'Shampoo Pantene Restauración-750ml', 'cantidad' => 2, 'precio' => 28000],
                    ['clave' => 'Crema Hidratante Nivea-400ml',       'cantidad' => 1, 'precio' => 55000],
                ],
            ], $detallesCreados);

            // Venta a tarjeta de HOY
            $this->crearVenta([
                'usuario'     => $vendedor,
                'cliente'     => $clientes['1009876543'],  // Jorge
                'metodo_pago' => 'Tarjeta',
                'es_credito'  => false,
                'dias_atras'  => 0,
                'items'       => [
                    ['clave' => 'Dior Sauvage-100ml', 'cantidad' => 1, 'precio' => 320000],
                ],
            ], $detallesCreados);

            // Segunda venta a crédito (en mora - vence en el pasado)
            $ventaMora = $this->crearVenta([
                'usuario'      => $vendedor,
                'cliente'      => $clientes['1003456789'],  // Miguel
                'metodo_pago'  => 'Transferencia',
                'es_credito'   => true,
                'dias_credito' => -20,  // ya venció
                'dias_atras'   => 35,
                'items'        => [
                    ['clave' => 'Chanel N°5-50ml', 'cantidad' => 1, 'precio' => 280000],
                ],
            ], $detallesCreados);
            if ($ventaMora) {
                $ventaMora->actualizarEstadoCredito();
            }
        }

        // ══════════════════════════════════════════════════
        // CUADRE DE CAJA DE HOY (abierto)
        // ══════════════════════════════════════════════════
        $cajaHoyAbierta = CuadreCaja::where('estado', 'Abierto')
            ->whereDate('fecha_apertura', today())
            ->exists();

        if (!$cajaHoyAbierta) {
            CuadreCaja::create([
                'id_usuario'    => $vendedor->id,
                'fecha_apertura'=> now()->setTime(8, 0, 0),
                'saldo_inicial' => 200000,
                'estado'        => CuadreCaja::ESTADO_ABIERTO,
                'observaciones' => 'Apertura normal turno mañana',
            ]);
        }

        $this->command->info('✅ Datos de demo cargados correctamente.');
        $this->command->info('');
        $this->command->info('  Usuarios disponibles:');
        $this->command->info('  • admin / admin123   → Administrador (acceso total)');
        $this->command->info('  • maria / vendedor123 → Vendedor');
        $this->command->info('  • carlos / almacen123 → Almacenista');
        $this->command->info('');
        $this->command->info('  URL: http://localhost/tienda-laravel/public');
    }

    // ──────────────────────────────────────────────────────
    // Helper: crea una venta con su historial y movimientos
    // ──────────────────────────────────────────────────────
    private function crearVenta(array $cfg, array $detallesCreados): ?Venta
    {
        $total = collect($cfg['items'])->sum(fn($i) => $i['cantidad'] * $i['precio']);
        $esCredito = $cfg['es_credito'];
        $diasAtras = $cfg['dias_atras'] ?? 0;
        $fecha = now()->subDays($diasAtras);

        $diasCredito = $cfg['dias_credito'] ?? 30;
        $fechaVenc = $esCredito ? $fecha->copy()->addDays($diasCredito) : null;

        $venta = Venta::create([
            'id_usuario'        => $cfg['usuario']->id,
            'id_cliente'        => $cfg['cliente']?->id,
            'es_credito'        => $esCredito,
            'metodo_pago'       => $cfg['metodo_pago'],
            'fecha_vencimiento' => $fechaVenc,
            'saldo_pendiente'   => $esCredito ? $total : 0,
            'estado_credito'    => $esCredito ? Venta::ESTADO_PARCIAL : Venta::ESTADO_PAGADO,
            'total'             => $total,
            'anulada'           => false,
            'created_at'        => $fecha,
            'updated_at'        => $fecha,
        ]);

        foreach ($cfg['items'] as $item) {
            if (!isset($detallesCreados[$item['clave']])) continue;

            $detalle = $detallesCreados[$item['clave']];
            $cantidadPendiente = $item['cantidad'];

            $lotes = LoteInventario::where('id_detalle_producto', $detalle->id)
                ->where('cantidad_disponible', '>', 0)
                ->orderBy('fecha_ingreso')
                ->orderBy('id')
                ->get();

            foreach ($lotes as $lote) {
                if ($cantidadPendiente <= 0) break;

                $usar = min($cantidadPendiente, $lote->cantidad_disponible);

                HistorialVenta::create([
                    'id_venta'       => $venta->id,
                    'id_lote'        => $lote->id,
                    'cantidad'       => $usar,
                    'precio_venta'   => $item['precio'],
                    'costo_unitario' => $lote->costo_unitario,
                    'created_at'     => $fecha,
                    'updated_at'     => $fecha,
                ]);

                $lote->decrement('cantidad_disponible', $usar);

                HistorialMovimiento::create([
                    'id_detalle_producto' => $detalle->id,
                    'tipo_operacion'      => HistorialMovimiento::TIPO_VENTA,
                    'tipo_origen'         => $lote->tipo_origen,
                    'cantidad'            => $usar,
                    'costo_unitario'      => $lote->costo_unitario,
                    'id_usuario'          => $cfg['usuario']->id,
                    'observacion'         => "Venta #{$venta->id}",
                    'created_at'          => $fecha,
                    'updated_at'          => $fecha,
                ]);

                $cantidadPendiente -= $usar;
            }

            if ($cantidadPendiente > 0) {
                $this->command->warn("⚠ Stock insuficiente para {$item['clave']} en venta #{$venta->id}");
            }
        }

        return $venta;
    }
}
