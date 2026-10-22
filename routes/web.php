<?php

use App\Http\Controllers\BodegaController;
use App\Http\Controllers\TrasladoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\CuadreCajaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LocalExternoController;
use App\Http\Controllers\PrestamoExternoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\CodigoBarrasController;
use App\Http\Controllers\CuentaBancariaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

// Ruta de inicio - redirige al dashboard o login
Route::get('/', fn() => redirect()->route('dashboard'));

// Rutas de autenticación (generadas por Breeze)
require __DIR__.'/auth.php';

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil del usuario
    Route::get('/perfil', [UsuarioController::class, 'perfil'])->name('perfil');
    Route::put('/perfil', [UsuarioController::class, 'actualizarPerfil'])->name('perfil.update');
    // Antes apuntaba a UsuarioController::cambiarPassword(Request, User $usuario); al no haber
    // {usuario} en esta ruta, Laravel resolvía un User vacío del contenedor y el cambio nunca
    // se guardaba. PasswordController::update() ya usa auth()->user() correctamente.
    Route::put('/perfil/password', [PasswordController::class, 'update'])->name('perfil.password');

    // === CATEGORÍAS ===
    Route::middleware(['permission:productos.editar'])->group(function () {
        Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
        Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
    });

    // === PRODUCTOS ===
    // Rutas estáticas ANTES del wildcard {producto}
    Route::middleware(['permission:productos.crear'])->group(function () {
        Route::get('/productos/crear', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
    });
    Route::middleware(['permission:productos.ver'])->group(function () {
        Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
        Route::get('/api/productos/buscar', [ProductoController::class, 'buscarPOS'])->name('productos.buscar-pos');
        Route::get('/productos/{producto}', [ProductoController::class, 'show'])->name('productos.show');
    });
    Route::middleware(['permission:productos.editar'])->group(function () {
        Route::get('/productos/{producto}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
    });
    Route::middleware(['permission:productos.eliminar'])->delete('/productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');

    // === INVENTARIO ===
    // Rutas estáticas ANTES del wildcard {detalle}
    Route::middleware(['permission:inventario.entradas'])->group(function () {
        Route::get('/inventario/entrada', [InventarioController::class, 'createEntrada'])->name('inventario.entrada');
        Route::post('/inventario/entrada', [InventarioController::class, 'storeEntrada'])->name('inventario.entrada.store');
        Route::get('/api/inventario/buscar', [InventarioController::class, 'buscarParaEntrada'])->name('inventario.buscar');
    });
    Route::middleware(['permission:inventario.salidas'])->group(function () {
        Route::get('/inventario/salida', [InventarioController::class, 'createSalida'])->name('inventario.salida');
        Route::post('/inventario/salida', [InventarioController::class, 'storeSalida'])->name('inventario.salida.store');
        Route::get('/api/inventario/salida/buscar', [TrasladoController::class, 'buscarProducto'])->name('inventario.salida.buscar');
        Route::middleware(['permission:ventas.movil'])
             ->get('/pos-movil/salida', [InventarioController::class, 'createSalidaMovil'])->name('ventas.movil.salida');
    });
    Route::middleware(['permission:inventario.ver'])->group(function () {
        Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::get('/inventario/historial/{detalle}', [InventarioController::class, 'historial'])->name('inventario.historial');
    });

    // === CLIENTES ===
    // Rutas estáticas ANTES del wildcard {cliente}
    Route::middleware(['permission:clientes.crear'])->group(function () {
        Route::get('/clientes/crear', [ClienteController::class, 'create'])->name('clientes.create');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
        Route::post('/api/clientes/rapido', [ClienteController::class, 'storeRapido'])->name('clientes.rapido');
    });
    Route::middleware(['permission:clientes.ver'])->group(function () {
        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
        Route::get('/clientes/{cliente}/estado-cuenta', [ClienteController::class, 'estadoCuenta'])->name('clientes.estado-cuenta');
        Route::get('/api/clientes/{cliente}/creditos', [ClienteController::class, 'creditosPendientesJson'])->name('clientes.creditos.json');
    });
    Route::middleware(['permission:clientes.editar'])->group(function () {
        Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
        Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    });
    Route::middleware(['permission:clientes.eliminar'])->delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');

    // Créditos - abonos
    Route::middleware(['permission:creditos.gestionar'])->group(function () {
        Route::post('/ventas/{venta}/abonar', [ClienteController::class, 'registrarAbono'])->name('ventas.abonar');
        Route::post('/clientes/{cliente}/abonar-mixto', [ClienteController::class, 'registrarAbonoMixto'])->name('clientes.abonar-mixto');
        Route::get('/clientes/{cliente}/ticket-abono-mixto', [ClienteController::class, 'ticketAbonoMixto'])->name('clientes.ticket-mixto');
        Route::get('/abonos/{pago}/ticket', [ClienteController::class, 'ticketAbono'])->name('abonos.ticket');
        Route::delete('/abonos/{pago}', [ClienteController::class, 'destroyAbono'])->name('abonos.destroy');
    });

    // === VENTAS ===
    // Rutas estáticas ANTES del wildcard {venta}
    Route::middleware(['permission:ventas.crear'])->group(function () {
        Route::get('/ventas/nueva', [VentaController::class, 'create'])->name('ventas.create');
        Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    });
    Route::middleware(['permission:ventas.movil'])->group(function () {
        Route::get('/pos-movil', [VentaController::class, 'hubMovil'])->name('ventas.movil');
        Route::get('/pos-movil/venta', [VentaController::class, 'createMovil'])->name('ventas.movil.venta');
        Route::get('/pos-movil/prestamo', [VentaController::class, 'createMovilPrestamo'])->name('ventas.movil.prestamo');
        Route::get('/pos-movil/traslado', [TrasladoController::class, 'createMovil'])->name('ventas.movil.traslado');
        Route::get('/pos-movil/consulta', [VentaController::class, 'consultaMovil'])->name('ventas.movil.consulta');
        Route::get('/pos-movil/historial', [VentaController::class, 'historialMovil'])->name('ventas.movil.historial');
    });
    Route::middleware(['permission:ventas.movil'])->get(
        '/api/inventario/tallas',
        [InventarioController::class, 'buscarTallas']
    )->name('inventario.buscar-tallas');
    Route::middleware(['permission:ventas.ver'])->group(function () {
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
        Route::get('/ventas/{venta}', [VentaController::class, 'show'])->name('ventas.show');
        Route::get('/ventas/{venta}/ticket', [VentaController::class, 'ticket'])->name('ventas.ticket');
    });
    Route::middleware(['permission:ventas.anular'])->delete('/ventas/{venta}', [VentaController::class, 'destroy'])->name('ventas.destroy');

    // === CUENTAS CON LOCALES EXTERNOS (PRÉSTAMOS) ===
    Route::middleware(['permission:prestamos.ver'])->group(function () {
        Route::get('/prestamos', [PrestamoExternoController::class, 'index'])->name('prestamos.index');
        Route::get('/prestamos/{localExterno}', [PrestamoExternoController::class, 'show'])->name('prestamos.show');
        Route::get('/locales-externos', [LocalExternoController::class, 'index'])->name('locales-externos.index');
    });
    Route::middleware(['permission:prestamos.liquidar'])->group(function () {
        Route::post('/prestamos/{localExterno}/liquidar', [PrestamoExternoController::class, 'liquidar'])->name('prestamos.liquidar');
        Route::patch('/prestamos/item/{prestamo}/costo', [PrestamoExternoController::class, 'actualizarCosto'])->name('prestamos.costo');
        Route::post('/locales-externos', [LocalExternoController::class, 'store'])->name('locales-externos.store');
        Route::put('/locales-externos/{localExterno}', [LocalExternoController::class, 'update'])->name('locales-externos.update');
        Route::patch('/locales-externos/{localExterno}/toggle', [LocalExternoController::class, 'toggleActivo'])->name('locales-externos.toggle');
    });

    // === CUENTAS BANCARIAS ===
    Route::middleware(['permission:cuentas.ver'])->group(function () {
        Route::get('/cuentas-bancarias', [CuentaBancariaController::class, 'index'])->name('cuentas-bancarias.index');
        Route::get('/cuentas-bancarias/{cuenta}', [CuentaBancariaController::class, 'show'])->name('cuentas-bancarias.show');
    });
    Route::middleware(['permission:cuentas.gestionar'])->group(function () {
        Route::post('/cuentas-bancarias', [CuentaBancariaController::class, 'store'])->name('cuentas-bancarias.store');
        Route::patch('/cuentas-bancarias/{cuenta}/toggle', [CuentaBancariaController::class, 'toggleActivo'])->name('cuentas-bancarias.toggle');
        Route::post('/cuentas-bancarias/{cuenta}/movimiento', [CuentaBancariaController::class, 'registrarMovimiento'])->name('cuentas-bancarias.movimiento');
    });

    // === GASTOS ===
    // Rutas estáticas ANTES del wildcard {gasto}
    Route::middleware(['permission:gastos.crear'])->group(function () {
        Route::get('/gastos/crear', [GastoController::class, 'create'])->name('gastos.create');
        Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');
    });
    Route::middleware(['permission:gastos.ver'])->group(function () {
        Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
        Route::get('/gastos/{gasto}', [GastoController::class, 'show'])->name('gastos.show');
    });
    Route::middleware(['permission:gastos.editar'])->group(function () {
        Route::get('/gastos/{gasto}/editar', [GastoController::class, 'edit'])->name('gastos.edit');
        Route::put('/gastos/{gasto}', [GastoController::class, 'update'])->name('gastos.update');
    });
    Route::middleware(['permission:gastos.eliminar'])->delete('/gastos/{gasto}', [GastoController::class, 'destroy'])->name('gastos.destroy');

    // === CUADRE DE CAJA ===
    // Rutas estáticas ANTES del wildcard {cuadreCaja}
    Route::middleware(['permission:caja.abrir'])->group(function () {
        Route::get('/cuadre-caja/abrir', [CuadreCajaController::class, 'create'])->name('cuadre-caja.create');
        Route::post('/cuadre-caja', [CuadreCajaController::class, 'store'])->name('cuadre-caja.store');
    });
    Route::middleware(['permission:caja.ver'])->group(function () {
        Route::get('/cuadre-caja', [CuadreCajaController::class, 'index'])->name('cuadre-caja.index');
        Route::get('/cuadre-caja/{cuadreCaja}', [CuadreCajaController::class, 'show'])->name('cuadre-caja.show');
        Route::get('/cuadre-caja/{cuadreCaja}/imprimir', [CuadreCajaController::class, 'imprimir'])->name('cuadre-caja.imprimir');
    });
    Route::middleware(['permission:caja.cerrar'])->put('/cuadre-caja/{cuadreCaja}/cerrar', [CuadreCajaController::class, 'cerrar'])->name('cuadre-caja.cerrar');

    // === CÓDIGOS DE BARRAS ===
    Route::middleware(['permission:inventario.ver'])->group(function () {
        Route::get('/codigos-barras', [CodigoBarrasController::class, 'generador'])->name('codigos-barras.generador');
        Route::get('/codigos-barras/entrada/{entrada}', [CodigoBarrasController::class, 'imprimirEntrada'])->name('codigos-barras.entrada');
    });
    Route::middleware(['permission:inventario.ver'])->post('/codigos-barras/imprimir', [CodigoBarrasController::class, 'imprimir'])->name('codigos-barras.imprimir');

    // === REPORTES ===
    Route::middleware(['permission:reportes.exportar'])->group(function () {
        Route::get('/reportes/ventas/pdf', [ReporteController::class, 'exportarVentasPDF'])->name('reportes.ventas.pdf');
        Route::get('/reportes/cartera/pdf', [ReporteController::class, 'exportarCarteraPDF'])->name('reportes.cartera.pdf');
    });
    Route::middleware(['permission:reportes.ver'])->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/ventas', [ReporteController::class, 'ventas'])->name('reportes.ventas');
        Route::get('/reportes/cartera', [ReporteController::class, 'cartera'])->name('reportes.cartera');
        Route::get('/reportes/ganancias', [ReporteController::class, 'ganancias'])->name('reportes.ganancias');
        Route::get('/reportes/movimientos', [ReporteController::class, 'movimientos'])->name('reportes.movimientos');
    });

    // === TRASLADOS ===
    Route::middleware(['permission:inventario.entradas'])->group(function () {
        Route::get('/traslados', [TrasladoController::class, 'index'])->name('traslados.index');
        Route::get('/traslados/nuevo', [TrasladoController::class, 'create'])->name('traslados.create');
        Route::post('/traslados', [TrasladoController::class, 'store'])->name('traslados.store');
        Route::get('/traslados/{traslado}', [TrasladoController::class, 'show'])->name('traslados.show');
        Route::get('/api/traslados/buscar-producto', [TrasladoController::class, 'buscarProducto'])->name('traslados.buscar-producto');
    });

    // === SUCURSALES Y BODEGAS ===
    Route::middleware(['permission:roles.gestionar'])->group(function () {
        Route::get('/sucursales', [SucursalController::class, 'index'])->name('sucursales.index');
        Route::post('/sucursales', [SucursalController::class, 'store'])->name('sucursales.store');
        Route::put('/sucursales/{sucursal}', [SucursalController::class, 'update'])->name('sucursales.update');
        Route::patch('/sucursales/{sucursal}/toggle', [SucursalController::class, 'toggleActivo'])->name('sucursales.toggle');

        Route::get('/bodegas', [BodegaController::class, 'index'])->name('bodegas.index');
        Route::post('/bodegas', [BodegaController::class, 'store'])->name('bodegas.store');
        Route::put('/bodegas/{bodega}', [BodegaController::class, 'update'])->name('bodegas.update');
        Route::patch('/bodegas/{bodega}/toggle', [BodegaController::class, 'toggleActivo'])->name('bodegas.toggle');
    });

    // === CONFIGURACIÓN DEL COMERCIO ===
    Route::middleware(['permission:roles.gestionar'])->group(function () {
        Route::get('/configuracion', [ConfiguracionController::class, 'edit'])->name('configuracion.edit');
        Route::put('/configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
    });

    // === ROLES ===
    Route::middleware(['permission:roles.ver'])->group(function () {
        Route::get('/roles', [RolController::class, 'index'])->name('roles.index');
    });
    Route::middleware(['permission:roles.gestionar'])->group(function () {
        Route::get('/roles/crear', [RolController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RolController::class, 'store'])->name('roles.store');
        Route::get('/roles/{rol}/editar', [RolController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{rol}', [RolController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{rol}', [RolController::class, 'destroy'])->name('roles.destroy');
    });

    // === USUARIOS ===
    // Rutas estáticas ANTES del wildcard {usuario}
    Route::middleware(['permission:usuarios.crear'])->group(function () {
        Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    });
    Route::middleware(['permission:usuarios.ver'])->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show'])->name('usuarios.show');
    });
    Route::middleware(['permission:usuarios.editar'])->group(function () {
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::put('/usuarios/{usuario}/password', [UsuarioController::class, 'cambiarPassword'])->name('usuarios.password');
    });
    Route::middleware(['permission:usuarios.eliminar'])->delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
});
