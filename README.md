# Sistema Tienda — Laravel 11

Sistema completo de gestión de tienda migrado de C# Windows Forms a una aplicación web moderna con **Laravel 11 + Livewire 3 + Alpine.js + Tailwind CSS**.

---

## Stack Tecnológico

| Componente | Tecnología | Versión |
|---|---|---|
| Framework Backend | Laravel | 11.x |
| Base de Datos | MySQL (XAMPP) | 5.7+ / MariaDB 10.3+ |
| Frontend JS | Alpine.js | 3.x |
| CSS Framework | Tailwind CSS | 3.x |
| Gráficos | Chart.js | 4.x |
| Autenticación | Laravel Breeze | 2.x |
| Permisos | Spatie Laravel Permission | 6.x |
| PDF | DomPDF | 2.x |
| Imágenes | Intervention Image | 3.x |

---

## Requisitos del Sistema

- **PHP**: 8.2 o superior
- **MySQL**: 5.7+ o MariaDB 10.3+
- **Composer**: 2.x
- **Node.js**: 18+ y npm 9+
- **XAMPP**: 8.2+
- **Extensiones PHP requeridas**:
  - pdo_mysql, openssl, mbstring, tokenizer
  - xml, ctype, json, fileinfo, gd

---

## Instalación en XAMPP (Paso a Paso)

### 1. Copiar el proyecto a XAMPP

```bash
# Copiar la carpeta tienda-laravel a:
C:/xampp/htdocs/tienda-laravel
```

### 2. Crear la base de datos

1. Abrir XAMPP → iniciar **Apache** y **MySQL**
2. Ir a `http://localhost/phpmyadmin`
3. Crear nueva base de datos: `tienda_db` (cotejamiento: `utf8mb4_unicode_ci`)

### 3. Configurar el archivo .env

```bash
# En la terminal, dentro de la carpeta del proyecto:
cd C:/xampp/htdocs/tienda-laravel
copy .env.example .env
```

Editar `.env` con los datos de tu XAMPP:

```env
APP_NAME="Sistema Tienda"
APP_URL=http://localhost/tienda-laravel/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tienda_db
DB_USERNAME=root
DB_PASSWORD=          # Dejar vacío si XAMPP no tiene contraseña
```

### 4. Instalar dependencias PHP

```bash
composer install
```

### 5. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 6. Publicar configuración de Spatie Permission

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 7. Ejecutar las migraciones y seeders

```bash
php artisan migrate --seed
```

Este comando crea todas las tablas y carga:
- 3 roles (Administrador, Vendedor, Almacenista) con permisos
- 3 usuarios de prueba
- 5 clientes de ejemplo
- 10 productos con variantes de talla
- Stock inicial con lotes FIFO
- 2 locales externos de ejemplo

### 8. Crear el enlace simbólico para archivos públicos

```bash
php artisan storage:link
```

> **En Windows con XAMPP**, si el comando falla, crea el enlace manualmente:
> ```cmd
> mklink /D "C:\xampp\htdocs\tienda-laravel\public\storage" "C:\xampp\htdocs\tienda-laravel\storage\app\public"
> ```

### 9. Compilar los assets (CSS y JavaScript)

```bash
npm install
npm run build
```

---

## Acceso al Sistema

### Opción A — Sin Virtual Host (simple)

Acceder directamente en el navegador:
```
http://localhost/tienda-laravel/public
```

### Opción B — Con Virtual Host (recomendado)

#### Configurar Apache en XAMPP:

1. Editar `C:/xampp/apache/conf/extra/httpd-vhosts.conf` y agregar:

```apache
<VirtualHost *:80>
    ServerName tienda.local
    DocumentRoot "C:/xampp/htdocs/tienda-laravel/public"
    <Directory "C:/xampp/htdocs/tienda-laravel/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "C:/xampp/logs/tienda-error.log"
    CustomLog "C:/xampp/logs/tienda-access.log" common
</VirtualHost>
```

2. Editar el archivo `hosts` de Windows (`C:/Windows/System32/drivers/etc/hosts`):

```
127.0.0.1    tienda.local
```

3. Reiniciar Apache en XAMPP.

4. Acceder en: `http://tienda.local`

---

## Credenciales de Usuarios de Prueba

| Rol | Usuario | Contraseña | Acceso |
|---|---|---|---|
| **Administrador** | `admin` | `admin123` | Acceso completo a todo el sistema |
| **Vendedor** | `vendedor1` | `vendedor123` | Ventas, clientes, créditos, caja |
| **Almacenista** | `almacenista1` | `almacen123` | Inventario, productos, entradas/salidas |

---

## Módulos del Sistema

### Punto de Venta (POS)
- Búsqueda de productos por nombre o código de barras en tiempo real
- Carrito de compras con ajuste de cantidades
- Ventas en efectivo, tarjeta, transferencia o crédito
- Cálculo automático de cambio
- Descuento FIFO de inventario por lotes
- Generación e impresión de ticket térmico

### Inventario
- Control por lotes con trazabilidad completa
- Sistema FIFO (primeras entradas, primeras salidas)
- Entradas propias y mercancía prestada
- Alertas de stock mínimo
- Historial de movimientos por producto

### Clientes y Créditos
- Registro completo de clientes
- Ventas a crédito con fecha de vencimiento
- Registro de abonos parciales
- Control de mora automático
- Estado de cuenta imprimible

### Cuadre de Caja
- Apertura con saldo inicial
- Cálculo automático: ventas + cobros − gastos
- Desglose de billetes y monedas
- Registro de diferencias con justificación
- Reporte imprimible

### Reportes
- Ventas por período con exportación a PDF
- Ganancias detalladas por producto
- Cartera de clientes morosos
- Movimientos de inventario
- Gráficos interactivos en el dashboard

---

## Estructura de Carpetas

```
tienda-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Controladores RESTful
│   │   │   ├── Auth/             # Autenticación
│   │   │   ├── DashboardController.php
│   │   │   ├── ProductoController.php
│   │   │   ├── VentaController.php
│   │   │   ├── ClienteController.php
│   │   │   ├── InventarioController.php
│   │   │   ├── GastoController.php
│   │   │   ├── CuadreCajaController.php
│   │   │   ├── UsuarioController.php
│   │   │   └── ReporteController.php
│   ├── Models/                   # Modelos Eloquent
│   └── Providers/
├── database/
│   ├── migrations/               # 13 migraciones
│   ├── seeders/                  # Datos de ejemplo
│   └── factories/
├── resources/
│   ├── views/
│   │   ├── layouts/app.blade.php # Layout principal
│   │   ├── auth/login.blade.php  # Login
│   │   ├── dashboard/            # Dashboard con gráficos
│   │   ├── ventas/               # POS + historial + ticket
│   │   ├── productos/            # CRUD de productos
│   │   ├── inventario/           # Control de stock
│   │   ├── clientes/             # Gestión + créditos
│   │   ├── gastos/               # Control de gastos
│   │   ├── cuadre-caja/          # Arqueo de caja
│   │   ├── reportes/             # Reportes + PDF
│   │   ├── usuarios/             # Gestión de usuarios
│   │   └── errors/               # Páginas de error
│   ├── css/app.css               # Estilos Tailwind
│   └── js/app.js                 # Alpine.js + Chart.js
├── routes/
│   ├── web.php                   # Rutas principales
│   └── auth.php                  # Rutas de autenticación
├── .env.example
├── composer.json
├── package.json
├── vite.config.js
└── tailwind.config.js
```

---

## Cómo Probar Cada Módulo

### 1. Login
```
URL: http://localhost/tienda-laravel/public/login
Usuario: admin | Contraseña: admin123
```

### 2. Dashboard
Muestra estadísticas del día, gráficos de ventas/ganancias, top productos.

### 3. Nueva Venta (POS)
```
Menú → Nueva Venta
1. Busca un producto (ej: "Camiseta")
2. Selecciona la variante de talla
3. Ajusta cantidad con + / −
4. Selecciona método de pago
5. Clic en "Registrar Venta"
6. Imprime el ticket
```

### 4. Entrada de Inventario
```
Menú → Inventario → Registrar Entrada
1. Selecciona el producto/talla
2. Ingresa cantidad y costo unitario
3. Selecciona tipo: Propio o Prestado
4. Guarda
```

### 5. Cuadre de Caja
```
Menú → Cuadre de Caja → Abrir Caja
1. Ingresa el saldo inicial
2. Durante el día se registran ventas y gastos
3. Al final: Menú → Cuadre → Ver → Cerrar Caja
4. Ingresa el total contado usando el desglose de billetes
5. El sistema calcula la diferencia
```

### 6. Reporte de Ventas con PDF
```
Menú → Reportes → Ventas
1. Selecciona fecha inicio y fin
2. Clic en "Generar"
3. Clic en "PDF" para descargar
```

---

## Solución de Problemas Comunes

### Error: "Class not found" después de instalar
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Las imágenes/archivos no cargan
```bash
php artisan storage:link
# O en Windows manualmente:
mklink /D "public\storage" "..\storage\app\public"
```

### Error de permisos en `storage/`
```bash
# En Windows, dar permisos completos al directorio:
icacls "C:\xampp\htdocs\tienda-laravel\storage" /grant Everyone:F /T
icacls "C:\xampp\htdocs\tienda-laravel\bootstrap\cache" /grant Everyone:F /T
```

### Error 500 en producción
Verificar que `APP_DEBUG=true` esté en `.env` para ver el error real.

### Las rutas no funcionan sin index.php en la URL
Verificar que `mod_rewrite` esté habilitado en Apache:
- XAMPP → Apache → Config → `httpd.conf`
- Buscar y descomentar: `LoadModule rewrite_module modules/mod_rewrite.so`
- Reiniciar Apache

### Error de permisos Spatie: "No se han publicado las migraciones"
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
php artisan db:seed --class=RolesPermissionsSeeder
```

### Assets CSS/JS no cargan
```bash
npm install
npm run build
# Los archivos quedan en public/build/
```

---

## Interfaz Visual

### Dashboard
- Sidebar colapsable con navegación por módulos
- 4 tarjetas de estadísticas en tiempo real
- Gráfico de barras: ventas de los últimos 7 días
- Gráfico de líneas: ventas y ganancias de los últimos 6 meses
- Top 5 productos más vendidos del mes
- Tabla de últimas 10 ventas

### Punto de Venta
- Barra de búsqueda con resultados en tiempo real
- Carrito visual con controles + / −
- Panel de pago con opciones de método
- Modo crédito con días de plazo
- Calculadora de cambio para efectivo

### Diseño General
- Paleta de colores: azul primario, grises neutros, verde éxito, rojo peligro
- Tipografía: Inter (Google Fonts)
- Responsive: funciona en desktop, tablet y móvil
- Toast notifications para feedback de acciones
- Confirmación antes de eliminar registros

---

## Seguridad Implementada

- ✅ Hashing de contraseñas con bcrypt
- ✅ Protección CSRF en todos los formularios
- ✅ Sistema de roles y permisos granulares (Spatie)
- ✅ Middleware de autenticación en todas las rutas protegidas
- ✅ Validación de inputs en backend (Form Requests)
- ✅ Protección contra SQL Injection (Eloquent ORM)
- ✅ Sanitización de entradas en formularios
- ✅ Prevención XSS (Blade escapa HTML automáticamente)
- ✅ Transacciones de base de datos para operaciones críticas

---

## Información del Proyecto

**Versión**: 1.0.0  
**Desarrollado con**: Laravel 11 + Tailwind CSS + Alpine.js  
**Base**: Migración desde sistema C# Windows Forms  
**Base de datos**: MySQL (XAMPP compatible)
