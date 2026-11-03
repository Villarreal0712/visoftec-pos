<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Devoluciones parciales de ventas y saldo a favor de clientes.
 *
 * - Una devolución no cambia ventas.total (el dinero sí entró ese día y el cuadre cerrado no
 *   se toca): suma a ventas.total_devuelto y a historial_ventas.cantidad_devuelta.
 * - Lo devuelto primero descuenta la deuda de la venta (si es a crédito); el resto queda como
 *   saldo a favor del cliente o se le reembolsa (reembolsos_cliente, que el cuadre descuenta).
 * - clientes.saldo_favor es el saldo vigente; movimientos_saldo_cliente es su historial.
 * - El saldo se usa en el POS (ventas.monto_saldo_favor) o para abonar a créditos
 *   (pagos_credito.metodo = 'Saldo a favor'); ninguno de los dos es dinero que entra a caja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('saldo_favor', 18, 2)->default(0)->after('limite_credito');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('total_devuelto', 18, 2)->default(0)->after('total');
            $table->decimal('monto_saldo_favor', 18, 2)->nullable()->after('monto_transferencia')
                  ->comment('Parte de la venta pagada con saldo a favor del cliente');
        });

        Schema::table('historial_ventas', function (Blueprint $table) {
            $table->integer('cantidad_devuelta')->default(0)->after('cantidad');
        });

        DB::statement("ALTER TABLE ventas MODIFY metodo_pago ENUM('Efectivo','Tarjeta','Transferencia','Crédito','Mixto','Saldo a favor') NOT NULL DEFAULT 'Efectivo'");
        DB::statement("ALTER TABLE pagos_credito MODIFY metodo ENUM('Efectivo','Tarjeta','Transferencia','Saldo a favor') NOT NULL DEFAULT 'Efectivo'");

        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_venta')->constrained('ventas')->restrictOnDelete();
            $table->foreignId('id_cliente')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->foreignId('id_sucursal')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->decimal('total', 18, 2)->comment('Valor de lo devuelto');
            $table->decimal('monto_deuda', 18, 2)->default(0)->comment('Parte que descontó la deuda de la venta');
            $table->decimal('monto_saldo_favor', 18, 2)->default(0)->comment('Parte que quedó como saldo a favor');
            $table->decimal('monto_reembolso', 18, 2)->default(0)->comment('Parte que se le devolvió en dinero');
            $table->string('motivo', 255)->nullable();
            $table->timestamps();

            $table->index('id_venta');
        });

        Schema::create('detalle_devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_devolucion')->constrained('devoluciones')->cascadeOnDelete();
            $table->foreignId('id_historial_venta')->constrained('historial_ventas')->restrictOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio_venta', 18, 2);
            $table->decimal('costo_unitario', 18, 2);
            $table->timestamps();
        });

        Schema::create('reembolsos_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('id_devolucion')->nullable()->constrained('devoluciones')->nullOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->foreignId('id_sucursal')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->decimal('monto', 18, 2);
            $table->foreignId('id_cuenta_bancaria')->nullable()->constrained('cuentas_bancarias')->nullOnDelete()
                  ->comment('De dónde salió el dinero; NULL = efectivo del cajón');
            $table->string('observacion', 255)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('movimientos_saldo_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->constrained('clientes')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->decimal('monto', 18, 2)->comment('Positivo = aumenta el saldo a favor, negativo = lo usa');
            $table->decimal('saldo_resultante', 18, 2);
            $table->foreignId('id_venta')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('id_devolucion')->nullable()->constrained('devoluciones')->nullOnDelete();
            $table->foreignId('id_pago_credito')->nullable()->constrained('pagos_credito')->nullOnDelete();
            $table->foreignId('id_reembolso')->nullable()->constrained('reembolsos_cliente')->nullOnDelete();
            $table->foreignId('id_usuario')->nullable()->constrained('users')->nullOnDelete();
            $table->string('observacion', 255)->nullable();
            $table->timestamps();

            $table->index('id_cliente');
        });

        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->decimal('reembolsos', 18, 2)->nullable()->after('gastos');
        });
    }

    public function down(): void
    {
        Schema::table('cuadres_caja', function (Blueprint $table) {
            $table->dropColumn('reembolsos');
        });
        Schema::dropIfExists('movimientos_saldo_cliente');
        Schema::dropIfExists('reembolsos_cliente');
        Schema::dropIfExists('detalle_devoluciones');
        Schema::dropIfExists('devoluciones');

        DB::statement("ALTER TABLE pagos_credito MODIFY metodo ENUM('Efectivo','Tarjeta','Transferencia') NOT NULL DEFAULT 'Efectivo'");
        DB::statement("ALTER TABLE ventas MODIFY metodo_pago ENUM('Efectivo','Tarjeta','Transferencia','Crédito','Mixto') NOT NULL DEFAULT 'Efectivo'");

        Schema::table('historial_ventas', function (Blueprint $table) {
            $table->dropColumn('cantidad_devuelta');
        });
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['total_devuelto', 'monto_saldo_favor']);
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('saldo_favor');
        });
    }
};
