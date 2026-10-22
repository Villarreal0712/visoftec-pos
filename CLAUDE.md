# VISOFTEC POS — Contexto de Desarrollo

## Proyecto
Sistema POS (Punto de Venta) para comercio minorista, desarrollado en Laravel 11.
- **Copia maestra (este equipo):** `C:\Users\CLINICANU-864.CLINICADELNORTE\Downloads\app` — desde 2026-10-22 es la versión unificada; santorini y jcfa tienen exactamente este código. Hacer los cambios aquí y desplegar a ambos.
- **Base de datos local:** MySQL `tienda_db` (XAMPP, usuario `root` sin contraseña)
- Otro equipo de desarrollo (histórico): `C:\xampp\htdocs\tienda-laravel`, carpeta Hostinger `C:\Users\jhvil\Documents\tienda_hostinger\`

### Instalaciones
| Sitio | Hosting | Acceso | Notas |
|-------|---------|--------|-------|
| `vigitecpost.com` | Hostinger | SSH `ssh hostinger` | **PRODUCCIÓN VIGITEC — no tocar.** Código distinto (otra rama) |
| `santorini.vigitecpost.com` | Hostinger | SSH `ssh hostinger` | Ensayo/pruebas de VISOFTEC. PHP 8.4 (`/opt/alt/php84/usr/bin/php`) |
| `jcfa.visoftec.com` | Hosting Latinoamérica (cPanel) | **Solo SFTP** `sftp latam` (puerto 2200, sin shell) | **PRODUCCIÓN** tienda de calzado real. PHP 8.5 |

Los alias `hostinger` y `latam` están en `~\.ssh\config` (misma llave `hostinger_ed25519`).

## Stack Técnico
- **Backend:** Laravel 11, PHP, Spatie Permission
- **Frontend:** Alpine.js, Tailwind CSS, Vite
- **Librerías JS:** Chart.js, JsBarcode, html5-qrcode (cámara para POS móvil)
- **Compilar assets:** `C:\xampp\php\php.exe` para PHP, `npm run build` para Vite

## Usuarios Demo
| Usuario | Rol | Contraseña |
|---------|-----|-----------|
| admin@vigitec.com | Administrador | password |
| svillarreal | Vendedor | (ver en DB) |

## Arquitectura de Permisos (Spatie)
- Roles: Administrador, Vendedor, Cajero
- Permiso especial: `ventas.movil` → accede al POS Móvil (`/pos-movil`)
- Permiso especial: `ventas.prestamo` → puede hacer ventas directas (préstamo externo)
- **IMPORTANTE:** `model_has_roles.model_type` debe ser `App\Models\User` (un solo backslash). Fix histórico: `UPDATE model_has_roles SET model_type = CONCAT('App', CHAR(92), 'Models', CHAR(92), 'User')`

## Modelos Principales
| Modelo | Tabla | Notas |
|--------|-------|-------|
| Producto | productos | Producto base con precio_venta y stock_minimo |
| DetalleProducto | detalle_productos | Variantes (talla/presentación) con codigo_barras y precio_venta |
| LoteInventario | lotes_inventario | FIFO: costo_unitario, precio_venta, cantidad_disponible |
| Venta | ventas | es_credito, saldo_pendiente, estado_credito |
| PagoCredito | pagos_credito | Abonos a ventas a crédito. `id_sucursal` = sucursal donde se RECIBIÓ el abono |
| Sucursal | sucursales | Puntos de venta. Ventas, cajas y gastos van por sucursal |
| Bodega | bodegas | Ubicaciones de stock (lotes por bodega). `id_sucursal = null` → bodega de acopio |
| Traslado / DetalleTraslado | traslados | Movimiento de stock entre bodegas |
| EntradaMercancia | entradas_mercancia | Entradas tipo carrito |
| CuentaBancaria / MovimientoCuentaBancaria | cuentas_bancarias | Cuentas para pagos no-efectivo |
| GastoExterno | gastos_externos | Gastos (en efectivo o desde cuenta bancaria) |
| PrestamoExterno | prestamos_externos | Ventas directas (mercancía ajena) |
| LocalExterno | locales_externos | Proveedores de préstamo |
| CuadreCaja | cuadres_caja | Apertura/cierre de caja |
| HistorialMovimiento | historial_movimientos | Trazabilidad de inventario (incluye `id_bodega`) |
| HistorialVenta | historial_ventas | Detalle de ítems por venta |
| Configuracion | configuracion | Datos del comercio, ancho_ticket |

## Lógica de Negocio Clave

### Inventario FIFO
Las ventas descuentan stock del lote más antiguo primero (`LoteInventario` ordenado por `fecha_ingreso ASC`). Ver `VentaController::store()`.

### Venta Directa (Préstamo)
Cuando `es_directo = true`, se crea un lote con `cantidad_disponible = 0` y se registra un `PrestamoExterno`. La mercancía es de un local externo, no del inventario propio.

### Código de Barras
Formato: `CONCAT(id_producto, LPAD(talla_index, 2, '0'))` donde talla 36=01, 45=10 (para zapatos). Para bar demo: `10101` = producto 1, variante 1.

### Validación de Stock en POS
En `agregarProducto()` y `buscarProducto()` de Alpine.js: si `stock === 0`, bloquea el agregar y solo permite como préstamo (si tiene permiso `ventas.prestamo`).

### Sucursales y bodegas
- Usuario sin sucursal → se usa la sucursal 1 (`auth()->user()->id_sucursal ?? 1`). Mantener este respaldo en todo filtro por sucursal (Ventas, Reportes, Cuadre).
- Inventario abre por defecto en la bodega del usuario; `?bodega=` (vacío) = todas las bodegas.
- Export de inventario: una columna de stock por bodega + "Stock total".

### Cuadre de caja
- Abonos: cuentan en la sucursal **donde se recibe el dinero** (`pagos_credito.id_sucursal`), no en la de la venta. Regla confirmada por el usuario 2026-10-22.
- Un cuadre **cerrado** muestra los totales guardados al cerrarlo (no se recalcula si luego se anula una venta). Uno abierto se calcula en vivo.
- `cuadres_caja.todas_sucursales = 1` → cuadre de antes de manejar sucursales; su detalle incluye todas.
- Solo los gastos pagados en efectivo restan del efectivo esperado.

### Salida de inventario (`/inventario/salida` y `/pos-movil/salida`)
Tipo carrito: varios productos, se elige bodega, motivo obligatorio. Descuenta FIFO solo de esa bodega con `lockForUpdate()`. Tipos: Salida, Merma, Ajuste, Transferencia (a local externo).

## Archivos Clave

### Backend
- `app/Http/Controllers/VentaController.php` — POS, ticket, anulación, `createMovil()`
- `app/Http/Controllers/ClienteController.php` — Clientes, abonos, `registrarAbonoMixto()`, `ticketAbonoMixto()`
- `app/Http/Controllers/InventarioController.php` — Entradas/salidas
- `app/Http/Controllers/CuadreCajaController.php` — Cuadre de caja
- `routes/web.php` — Todas las rutas con middleware de permisos

### Frontend JS
- `resources/js/app.js` — Alpine.js components:
  - `pos()` — POS escritorio completo
  - `posMovil()` — POS móvil con cámara (html5-qrcode)
  - `sidebar()` — Sidebar colapsable
  - `desgloseBilletes()` — Cuadre de caja
  - `moneyInput()` — Input con formato miles COP
  - `modalAbono()` — Modal de abonos (en show.blade.php como función local)

### Vistas Importantes
- `resources/views/ventas/create.blade.php` — POS escritorio
- `resources/views/ventas/pos-movil.blade.php` — POS móvil (standalone, con cámara)
- `resources/views/ventas/ticket.blade.php` — Ticket de venta
- `resources/views/ventas/ticket-abono.blade.php` — Comprobante abono individual
- `resources/views/ventas/ticket-abono-mixto.blade.php` — Comprobante abono global (múltiples ventas)
- `resources/views/clientes/show.blade.php` — Perfil cliente + modal abonos mixto/por venta
- `resources/views/layouts/app.blade.php` — Layout principal con sidebar
- `resources/views/layouts/mobile.blade.php` — Layout standalone para móvil
- `resources/views/ventas/pos-hub.blade.php` — Menú del POS móvil (Vender, Entrada, Salida, Préstamo…)
- `resources/views/inventario/salida.blade.php` / `salida-movil.blade.php` — Salida tipo carrito
- `resources/views/components/selector-cliente.blade.php` — Selector de cliente con búsqueda (`<x-selector-cliente>`), usado en los 3 POS

### Convenciones de vistas
- Valores PHP dentro de JS/Alpine: usar `@js(...)`, nunca `'{{ ... }}'` (un apóstrofo rompe la página).
- Modales con `x-show`: agregar `x-cloak` (el estilo `[x-cloak]` está en ambos layouts).

## Funcionalidades Implementadas

### POS Móvil (`/pos-movil`)
- Layout standalone sin sidebar (layout: `mobile.blade.php`)
- Botón de escaneo → modal fullscreen con cámara trasera (html5-qrcode)
- Detección automática de código de barras, agrega al carrito
- Lock `procesandoScan` para evitar múltiples lecturas del mismo código
- Búsqueda manual también disponible dentro del modal
- Soporte completo de préstamos, crédito, método de pago
- Protegido por permiso `ventas.movil`
- **Nota:** Requiere HTTPS para cámara en móvil. En localhost funciona. En Hostinger funciona. Para pruebas locales con celular: usar Chrome flags `chrome://flags` → "insecure origins treated as secure" → agregar `http://192.168.78.106`

### Abono Mixto (`/clientes/{id}` → modal Abonar)
- Toggle "Por venta" / "Abono global" (aparece solo si hay > 1 crédito pendiente)
- Abono global: el usuario ingresa un monto total, preview muestra distribución FIFO por fecha de vencimiento
- Backend: `ClienteController::registrarAbonoMixto()` → crea un `PagoCredito` por cada venta pagada
- Genera comprobante consolidado imprimible (`ticket-abono-mixto.blade.php`)
- El cuadre de caja suma todos los `PagoCredito` del período → compatible sin cambios

### Export de Inventario
- Botón exportar usa regex para añadir `?exportar=1` a la URL actual sin duplicar params
- `onclick="var h=window.location.href.replace(/[?&]exportar=[^&]*/g,''); window.location.href=h+(h.indexOf('?')>=0?'&':'?')+'exportar=1'; return false;"`

## Bugs Corregidos (histórico)
1. **model_has_roles doble backslash** — `model_type` era `App\\Models\\User`. Fix: UPDATE con `CHAR(92)`
2. **Vite manifest en Hostinger** — Subir `public/build/` manualmente (build_assets.zip)
3. **cuadre-caja null user** — Registro huérfano con id_usuario inválido. Fix: operador null-safe `$cuadre->usuario?->nombre ?? '—'`
4. **salida.blade.php campo faltante** — Form enviaba `motivo` pero controller esperaba `tipo_operacion`
5. **Export button `URL is not a constructor`** — Eliminado URLSearchParams, reemplazado con regex directo
6. **detalle_productos INSERT columna `activo`** — No existe en esa tabla, solo en `productos`
7. **Scanner agrega múltiples veces** — Lock `procesandoScan = true` con timeout de 2 segundos
8. **Usuario sin sucursal no veía ventas** — filtros con `id_sucursal` null. Fix: `?? 1`
9. **Impresión del cuadre mostraba ventas de todas las sucursales** (jcfa) — ahora filtra por la sucursal del cuadre
10. **Ruta temporal `/setup-inicial`** — eliminada de `routes/web.php` (permitía correr Artisan por web)

## Comandos Útiles
```powershell
# Compilar assets (node_modules: correr "npm ci" la primera vez)
npm run build

# Ejecutar SQL
& "C:\xampp\mysql\bin\mysql.exe" -u root tienda_db -e "SHOW TABLES;"

# Tinker
& "C:\xampp\php\php.exe" artisan tinker

# Limpiar caché
& "C:\xampp\php\php.exe" artisan cache:clear
& "C:\xampp\php\php.exe" artisan config:clear
& "C:\xampp\php\php.exe" artisan route:clear
& "C:\xampp\php\php.exe" artisan view:clear
```

## Despliegue

Reglas generales:
- Pedir confirmación antes de tocar cualquier servidor. **Nunca** escribir en `~/domains/vigitecpost.com/`.
- Antes de subir: respaldo de los archivos que se reemplazan. Después: revisar el log.
- Orden: migraciones primero (solo agregan columnas, el código viejo sigue funcionando), luego el código.
- Si cambian vistas/JS/CSS: `npm run build` y subir el `.css`/`.js` nuevo de `public/build/assets/` **antes** que `manifest.json`.

### santorini (Hostinger, SSH)
- Código en `~/domains/santorini.vigitecpost.com/laravel/`; `public_html/` es el public (su `index.php` apunta a `../laravel`). El build va en `public_html/build/`.
- Subir con `scp archivo hostinger:domains/santorini.vigitecpost.com/laravel/<ruta>`
- Tiene caché de rutas/config: después de subir correr `artisan route:cache` (y `config:cache` si cambió config), `view:clear`.
- Migraciones: `ssh hostinger "cd ~/domains/santorini.vigitecpost.com/laravel && /opt/alt/php84/usr/bin/php artisan migrate --force"`
- Respaldos en `~/domains/santorini.vigitecpost.com/backups/`.

### jcfa (Hosting Latinoamérica, solo SFTP)
- Código en `/home/visoftec/jcfa.visoftec.com/` (estructura Laravel normal, el document root es `public/`).
- Subir con `sftp latam` (`put` en modo batch). No tiene caché de rutas/config: los cambios aplican al subir.
- **Migraciones sin shell:** subir un `routes/web.php` temporal con una ruta secreta `/tmp-artisan-<clave-aleatoria>/{status|pretend|migrate}` que llama `Artisan::call(...)`; el **usuario** la abre en su navegador (el hosting tiene anti-bot "One moment, please..." que bloquea curl); luego volver a subir el `web.php` original y verificar que la ruta ya no esté. Alternativa: SQL en phpMyAdmin + `INSERT` en la tabla `migrations`.
- HTTPS activo → la cámara del POS móvil funciona sin configuración extra.

### Pendientes
- Cron de `schedule:run` en santorini (hPanel) para la tarea diaria que marca créditos en mora (`routes/console.php`).
- jcfa: borrar del servidor `README-DESPLIEGUE.txt`, `BASE_DE_DATOS.sql` y `app.zip` (sobrantes de la instalación).
