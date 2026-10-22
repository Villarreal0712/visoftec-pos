<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Resetear caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permisos = [
            // Productos
            'productos.ver', 'productos.crear', 'productos.editar', 'productos.eliminar',
            // Inventario
            'inventario.ver', 'inventario.entradas', 'inventario.salidas', 'inventario.ajustes',
            // Clientes
            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
            // Ventas
            'ventas.ver', 'ventas.crear', 'ventas.anular', 'ventas.prestamo', 'ventas.movil',
            // Créditos
            'creditos.ver', 'creditos.gestionar',
            // Gastos
            'gastos.ver', 'gastos.crear', 'gastos.editar', 'gastos.eliminar',
            // Cuadre de caja
            'caja.ver', 'caja.abrir', 'caja.cerrar',
            // Reportes
            'reportes.ver', 'reportes.exportar',
            // Usuarios
            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            // Roles
            'roles.ver', 'roles.gestionar',
            // Configuración
            'configuracion.ver', 'configuracion.editar',
            // Cuentas con locales externos (préstamos)
            'prestamos.ver', 'prestamos.liquidar',
            // Cuentas bancarias
            'cuentas.ver', 'cuentas.gestionar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Cuenta "Efectivo" por defecto — siempre debe existir para que el selector de pago
        // del POS nunca quede vacío, incluso si el admin aún no ha creado cuentas bancarias.
        \App\Models\CuentaBancaria::firstOrCreate(
            ['nombre' => 'Efectivo'],
            ['tipo' => \App\Models\CuentaBancaria::TIPO_EFECTIVO, 'activo' => true]
        );

        // Crear roles y asignar permisos

        // Administrador - acceso total
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all()); // incluye ventas.prestamo

        // Vendedor - puede vender y ver clientes
        $vendedor = Role::firstOrCreate(['name' => 'Vendedor', 'guard_name' => 'web']);
        $vendedor->syncPermissions([
            'productos.ver',
            'inventario.ver',
            'clientes.ver', 'clientes.crear', 'clientes.editar',
            'ventas.ver', 'ventas.crear', 'ventas.movil',
            'creditos.ver', 'creditos.gestionar',
            'caja.ver', 'caja.abrir', 'caja.cerrar',
            'reportes.ver',
            'prestamos.ver', 'prestamos.liquidar',
            'cuentas.ver',
        ]);

        // Almacenista - gestiona inventario
        $almacenista = Role::firstOrCreate(['name' => 'Almacenista', 'guard_name' => 'web']);
        $almacenista->syncPermissions([
            'productos.ver', 'productos.crear', 'productos.editar',
            'inventario.ver', 'inventario.entradas', 'inventario.salidas', 'inventario.ajustes',
            'clientes.ver',
            'ventas.ver',
            'reportes.ver',
        ]);
    }
}
