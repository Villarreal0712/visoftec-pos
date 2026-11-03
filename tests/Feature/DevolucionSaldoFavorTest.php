<?php

namespace Tests\Feature;

use App\Models\Bodega;
use App\Models\Cliente;
use App\Models\CuadreCaja;
use App\Models\CuentaBancaria;
use App\Models\DetalleProducto;
use App\Models\Devolucion;
use App\Models\HistorialVenta;
use App\Models\LiquidacionPrestamo;
use App\Models\LocalExterno;
use App\Models\LoteInventario;
use App\Models\MovimientoSaldoCliente;
use App\Models\PagoCredito;
use App\Models\PrestamoExterno;
use App\Models\Producto;
use App\Models\ReembolsoCliente;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Devoluciones parciales y saldo a favor: devolver ítems de una venta (a crédito ya pagada,
 * con deuda, de contado, prestada), usar el saldo en el POS y en abonos, reembolsarlo, y que
 * el cuadre de caja solo cuente el dinero que de verdad entró o salió.
 *
 * Igual que PagoMixtoTest: corre sobre MySQL/MariaDB en la base visoftec_test, que se BORRA.
 */
class DevolucionSaldoFavorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected CuentaBancaria $nequi;
    protected CuentaBancaria $efectivo;
    protected DetalleProducto $tenis;   // $100.000
    protected CuadreCaja $caja;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertStringContainsString('test', config('database.connections.' . config('database.default') . '.database'),
            'DevolucionSaldoFavorTest solo debe correr sobre una base de pruebas');

        $this->seed(RolesPermissionsSeeder::class);

        $sucursal = Sucursal::create(['nombre' => 'Principal', 'activo' => true]);
        $bodega   = Bodega::create(['nombre' => 'Bodega Local', 'id_sucursal' => $sucursal->id, 'activo' => true]);

        $this->admin = User::create([
            'nombre' => 'Admin', 'usuario' => 'admin', 'password' => 'secreto123', 'activo' => true,
            'id_sucursal' => $sucursal->id, 'id_bodega' => $bodega->id,
        ]);
        $this->admin->assignRole('Administrador');

        $this->efectivo = CuentaBancaria::where('tipo', CuentaBancaria::TIPO_EFECTIVO)->firstOrFail();
        $this->nequi    = CuentaBancaria::create(['nombre' => 'Nequi', 'tipo' => CuentaBancaria::TIPO_BANCO, 'saldo_actual' => 0, 'activo' => true]);

        $producto = Producto::create(['nombre' => 'Tenis', 'precio_venta' => 100000, 'stock_minimo' => 0, 'activo' => true]);
        $this->tenis = DetalleProducto::create(['id_producto' => $producto->id, 'talla' => '40', 'codigo_barras' => '10105', 'precio_venta' => 100000]);
        LoteInventario::create([
            'id_detalle_producto' => $this->tenis->id, 'id_bodega' => $bodega->id, 'fecha_ingreso' => now()->subDay(),
            'cantidad_disponible' => 20, 'costo_unitario' => 50000, 'tipo_origen' => 'Propio',
        ]);

        $this->caja = CuadreCaja::create([
            'id_usuario' => $this->admin->id, 'id_sucursal' => $sucursal->id,
            'fecha_apertura' => now()->subHour(), 'saldo_inicial' => 50000, 'estado' => CuadreCaja::ESTADO_ABIERTO,
        ]);

        $this->cliente = Cliente::create(['documento' => '123', 'nombre' => 'Ana', 'limite_credito' => 0]);

        $this->actingAs($this->admin);
    }

    // ── Ayudas ────────────────────────────────────────────────────────────────

    protected function vender(array $pago, int $cantidad = 1): Venta
    {
        $this->post(route('ventas.store'), $pago + [
            'items' => [[
                'id_detalle_producto' => $this->tenis->id, 'cantidad' => $cantidad,
                'precio_venta' => 100000, 'es_directo' => 0,
            ]],
        ])->assertSessionHasNoErrors();

        return Venta::latest('id')->firstOrFail();
    }

    protected function venderCredito(int $cantidad): Venta
    {
        // El POS siempre manda la cuenta seleccionada, aunque en crédito se ignore
        return $this->vender(['es_credito' => 1, 'dias_credito' => 30, 'id_cliente' => $this->cliente->id,
                              'id_cuenta_bancaria' => $this->efectivo->id], $cantidad);
    }

    protected function abonar(Venta $venta, float $monto, array $medio = null)
    {
        return $this->post(route('ventas.abonar', $venta), ['monto' => $monto] + ($medio ?? ['id_cuenta_bancaria' => $this->efectivo->id]));
    }

    /** Devuelve $cantidad unidades de la primera línea de la venta. */
    protected function devolver(Venta $venta, int $cantidad, string $destino = 'saldo', array $extra = [])
    {
        $linea = $venta->historialVentas()->firstOrFail();
        return $this->post(route('ventas.devolucion', $venta), $extra + [
            'items'   => [['id_historial_venta' => $linea->id, 'cantidad' => $cantidad]],
            'destino' => $destino,
        ]);
    }

    protected function darSaldo(float $monto): void
    {
        $this->cliente->moverSaldoFavor($monto, MovimientoSaldoCliente::TIPO_DEVOLUCION, [], 'Saldo inicial de prueba');
    }

    protected function stock(): int
    {
        return (int) LoteInventario::where('id_detalle_producto', $this->tenis->id)->sum('cantidad_disponible');
    }

    protected function cuadre(): \Illuminate\Testing\TestResponse
    {
        return $this->get(route('cuadre-caja.show', $this->caja))->assertOk();
    }

    // ── Devoluciones ──────────────────────────────────────────────────────────

    /** El caso que motivó la función: crédito de 3 pares ya pagado, devuelven 1. */
    public function test_credito_pagado_devuelven_un_par_y_queda_saldo_a_favor(): void
    {
        $venta = $this->venderCredito(3);
        $this->abonar($venta, 300000)->assertRedirect();
        $this->assertEquals(350000, $this->cuadre()->viewData('totalSistema'));

        $this->devolver($venta, 1)->assertRedirect(route('ventas.show', $venta))->assertSessionHas('success');

        $venta->refresh();
        $this->assertEquals(300000, $venta->total, 'El total original no cambia');
        $this->assertEquals(100000, $venta->total_devuelto);
        $this->assertEquals(0, $venta->saldo_pendiente);
        $this->assertSame(Venta::ESTADO_PAGADO, $venta->estado_credito);
        $this->assertSame(18, $this->stock(), '20 − 3 vendidos + 1 devuelto');
        $this->assertSame(1, HistorialVenta::first()->cantidad_devuelta);

        $this->assertEquals(100000, $this->cliente->fresh()->saldo_favor);
        $dev = Devolucion::firstOrFail();
        $this->assertEquals([100000, 0, 100000, 0], [(float) $dev->total, (float) $dev->monto_deuda, (float) $dev->monto_saldo_favor, (float) $dev->monto_reembolso]);
        $this->assertSame(MovimientoSaldoCliente::TIPO_DEVOLUCION, $this->cliente->movimientosSaldo()->first()->tipo);

        // No salió dinero: el cuadre sigue igual
        $this->assertEquals(350000, $this->cuadre()->viewData('totalSistema'));

        $this->get(route('ventas.show', $venta))->assertOk()->assertSee('Devoluciones')->assertSee('Saldo a favor');
        $this->get(route('clientes.show', $this->cliente))->assertOk()->assertSee('$100.000');
        $this->get(route('inventario.historial', $this->tenis))->assertOk()->assertSee("Devolución venta #{$venta->id}");
        $this->get(route('reportes.movimientos'))->assertOk()->assertSee('Devolución de venta');
    }

    public function test_devolucion_primero_descuenta_la_deuda_y_el_resto_va_a_saldo(): void
    {
        $venta = $this->venderCredito(3);
        $this->abonar($venta, 150000);

        $this->devolver($venta, 2)->assertSessionHas('success');   // $200.000, debía $150.000

        $venta->refresh();
        $this->assertEquals(0, $venta->saldo_pendiente);
        $this->assertSame(Venta::ESTADO_PAGADO, $venta->estado_credito);
        $dev = Devolucion::firstOrFail();
        $this->assertEquals(150000, $dev->monto_deuda);
        $this->assertEquals(50000, $dev->monto_saldo_favor);
        $this->assertEquals(50000, $this->cliente->fresh()->saldo_favor);
    }

    public function test_devolucion_de_credito_sin_abonos_solo_baja_la_deuda(): void
    {
        $venta = $this->venderCredito(3);

        $this->devolver($venta, 1)->assertSessionHas('success');

        $venta->refresh();
        $this->assertEquals(200000, $venta->saldo_pendiente);
        $this->assertSame(Venta::ESTADO_PARCIAL, $venta->estado_credito);
        $this->assertEquals(0, $this->cliente->fresh()->saldo_favor);
        $this->assertSame(0, MovimientoSaldoCliente::count());
    }

    public function test_reembolso_en_efectivo_lo_descuenta_el_cuadre(): void
    {
        $venta = $this->vender(['id_cuenta_bancaria' => $this->efectivo->id], 2);   // sin cliente

        // Sin cliente no se puede dejar saldo a favor
        $this->devolver($venta, 1, 'saldo')->assertSessionHas('error');
        $this->assertSame(0, Devolucion::count());
        $this->assertSame(18, $this->stock());

        $this->devolver($venta, 1, 'reembolso', ['id_cuenta_bancaria' => $this->efectivo->id])->assertSessionHas('success');

        $this->assertEquals(100000, ReembolsoCliente::firstOrFail()->monto);
        $r = $this->cuadre();
        $this->assertEquals(100000, $r->viewData('reembolsos'));
        $this->assertEquals(50000 + 200000 - 100000, $r->viewData('totalSistema'));
        $r->assertSee('Reembolsos a clientes');

        // Al cerrar, el reembolso queda guardado
        $this->put(route('cuadre-caja.cerrar', $this->caja), ['efectivo_contado' => 150000])->assertRedirect();
        $caja = $this->caja->fresh();
        $this->assertEquals(100000, $caja->reembolsos);
        $this->assertEquals(0, $caja->diferencia);
        $this->get(route('cuadre-caja.imprimir', $caja))->assertOk()->assertSee('Reembolsos a clientes');
    }

    public function test_reembolso_desde_nequi_baja_la_cuenta_y_no_el_cajon(): void
    {
        $venta = $this->vender(['id_cuenta_bancaria' => $this->nequi->id]);
        $this->assertEquals(100000, $this->nequi->fresh()->saldo_actual);

        $this->devolver($venta, 1, 'reembolso', ['id_cuenta_bancaria' => $this->nequi->id])->assertSessionHas('success');

        $this->assertEquals(0, $this->nequi->fresh()->saldo_actual);
        $r = $this->cuadre();
        $this->assertEquals(0, $r->viewData('reembolsos'));
        $this->assertEquals(150000, $r->viewData('totalSistema'));
    }

    public function test_no_se_devuelve_mas_de_lo_vendido_ni_se_anula_despues(): void
    {
        $venta = $this->venderCredito(3);

        $this->devolver($venta, 4)->assertSessionHas('error');
        $this->devolver($venta, 0)->assertSessionHas('error');
        $this->devolver($venta, 2)->assertSessionHas('success');
        $this->devolver($venta, 2)->assertSessionHas('error');
        $this->devolver($venta, 1)->assertSessionHas('success');

        $this->assertSame(20, $this->stock());
        $this->assertEquals(300000, $venta->fresh()->total_devuelto);

        // Con devoluciones registradas ya no se anula
        $this->delete(route('ventas.destroy', $venta))->assertSessionHas('error');
        $this->assertFalse($venta->fresh()->anulada);

        // Una línea de otra venta no se acepta
        $otra = $this->venderCredito(1);
        $this->post(route('ventas.devolucion', $venta), [
            'items' => [['id_historial_venta' => $otra->historialVentas()->first()->id, 'cantidad' => 1]], 'destino' => 'saldo',
        ])->assertSessionHas('error');
    }

    public function test_devolver_un_prestamo_baja_la_deuda_con_el_local_y_no_el_stock(): void
    {
        $local = LocalExterno::create(['nombre_local' => 'Calzado Vecino', 'activo' => true]);
        $this->post(route('ventas.store'), [
            'id_cuenta_bancaria' => $this->efectivo->id, 'id_cliente' => $this->cliente->id,
            'items' => [['id_detalle_producto' => $this->tenis->id, 'cantidad' => 2, 'precio_venta' => 100000,
                         'es_directo' => 1, 'costo_directo' => 60000, 'id_local_externo' => $local->id]],
        ])->assertSessionHasNoErrors();
        $venta = Venta::latest('id')->firstOrFail();

        $this->devolver($venta, 1)->assertSessionHas('success');
        $prestamo = PrestamoExterno::firstOrFail();
        $this->assertSame(1, $prestamo->cantidad);
        $this->assertSame(PrestamoExterno::ESTADO_PENDIENTE, $prestamo->estado);
        $this->assertSame(20, $this->stock(), 'La mercancía prestada no entra al inventario propio');
        $this->assertEquals(100000, $this->cliente->fresh()->saldo_favor);

        // Si ya se le pagó al local, no se puede devolver el resto
        LiquidacionPrestamo::create(['id_local_externo' => $local->id, 'monto' => 60000, 'id_usuario' => $this->admin->id]);
        $this->devolver($venta, 1)->assertSessionHas('error');

        LiquidacionPrestamo::query()->delete();
        $this->devolver($venta, 1)->assertSessionHas('success');
        $this->assertSame(PrestamoExterno::ESTADO_ANULADO, $prestamo->fresh()->estado);
    }

    // ── Uso del saldo a favor ─────────────────────────────────────────────────

    public function test_pagar_parte_de_una_venta_con_saldo_a_favor(): void
    {
        $this->darSaldo(150000);

        $venta = $this->vender(['id_cuenta_bancaria' => $this->nequi->id, 'id_cliente' => $this->cliente->id, 'monto_saldo_favor' => 30000]);

        $this->assertSame(Venta::METODO_TRANSFERENCIA, $venta->metodo_pago);
        $this->assertEquals(30000, $venta->monto_saldo_favor);
        $this->assertEquals(70000, $this->nequi->fresh()->saldo_actual, 'A Nequi solo entra lo que se pagó con dinero');
        $this->assertEquals(120000, $this->cliente->fresh()->saldo_favor);
        $this->assertEquals(70000, $this->cuadre()->viewData('ventasTransferencia'));
        $this->assertStringContainsString('Saldo a favor $30.000', $venta->descripcionPago());
        $this->get(route('ventas.ticket', $venta))->assertOk()->assertSee('Saldo a favor $30.000');

        // Al anular, el cliente recupera el saldo y Nequi devuelve solo lo que recibió
        $this->delete(route('ventas.destroy', $venta))->assertRedirect();
        $this->assertEquals(150000, $this->cliente->fresh()->saldo_favor);
        $this->assertEquals(0, $this->nequi->fresh()->saldo_actual);
    }

    public function test_pagar_toda_la_venta_con_saldo_a_favor_no_mueve_dinero(): void
    {
        $this->darSaldo(100000);

        $venta = $this->vender(['id_cuenta_bancaria' => $this->efectivo->id, 'id_cliente' => $this->cliente->id, 'monto_saldo_favor' => 100000]);

        $this->assertSame(Venta::METODO_SALDO_FAVOR, $venta->metodo_pago);
        $this->assertNull($venta->id_cuenta_bancaria);
        $this->assertEquals(0, $this->cliente->fresh()->saldo_favor);
        $r = $this->cuadre();
        $this->assertEquals(0, $r->viewData('ventasEfectivo'));
        $this->assertEquals(50000, $r->viewData('totalSistema'));
    }

    public function test_saldo_a_favor_insuficiente_o_en_credito_no_crea_venta(): void
    {
        $this->darSaldo(20000);

        foreach ([
            'más que el saldo'    => ['id_cuenta_bancaria' => $this->efectivo->id, 'id_cliente' => $this->cliente->id, 'monto_saldo_favor' => 50000],
            'más que el total'    => ['id_cuenta_bancaria' => $this->efectivo->id, 'id_cliente' => $this->cliente->id, 'monto_saldo_favor' => 150000],
            'sin cliente'         => ['id_cuenta_bancaria' => $this->efectivo->id, 'monto_saldo_favor' => 10000],
            'en venta a crédito'  => ['es_credito' => 1, 'dias_credito' => 30, 'id_cliente' => $this->cliente->id, 'id_cuenta_bancaria' => $this->efectivo->id, 'monto_saldo_favor' => 10000],
        ] as $caso => $pago) {
            $this->post(route('ventas.store'), $pago + ['items' => [[
                'id_detalle_producto' => $this->tenis->id, 'cantidad' => 1, 'precio_venta' => 100000, 'es_directo' => 0,
            ]]])->assertSessionHas('error');
            $this->assertSame(0, Venta::count(), "No debe crear venta: $caso");
        }

        $this->assertSame(20, $this->stock());
        $this->assertEquals(20000, $this->cliente->fresh()->saldo_favor);
    }

    public function test_abonar_con_saldo_a_favor_no_cuenta_en_caja_y_se_revierte_al_anular(): void
    {
        $venta = $this->venderCredito(1);
        $this->darSaldo(60000);

        $this->abonar($venta, 80000, ['usar_saldo_favor' => 1])->assertSessionHas('error');
        $this->assertSame(0, PagoCredito::count());

        $this->abonar($venta, 60000, ['usar_saldo_favor' => 1])->assertRedirect();

        $pago = PagoCredito::firstOrFail();
        $this->assertSame(Venta::METODO_SALDO_FAVOR, $pago->metodo);
        $this->assertEquals(40000, $venta->fresh()->saldo_pendiente);
        $this->assertEquals(0, $this->cliente->fresh()->saldo_favor);
        $r = $this->cuadre();
        $this->assertEquals(0, $r->viewData('pagosCredito'));
        $this->assertEquals(50000, $r->viewData('totalSistema'));

        $this->delete(route('abonos.destroy', $pago))->assertSessionHas('success');
        $this->assertEquals(60000, $this->cliente->fresh()->saldo_favor);
        $this->assertEquals(100000, $venta->fresh()->saldo_pendiente);
    }

    public function test_abono_global_con_saldo_a_favor(): void
    {
        $this->venderCredito(1);
        $this->venderCredito(1);
        $this->darSaldo(150000);

        $this->postJson(route('clientes.abonar-mixto', $this->cliente), ['monto_total' => 150000, 'usar_saldo_favor' => 1])->assertOk();

        $this->assertEquals(0, $this->cliente->fresh()->saldo_favor);
        $this->assertSame(2, PagoCredito::where('metodo', Venta::METODO_SALDO_FAVOR)->count());
        $this->assertEquals(50000, Venta::sum('saldo_pendiente'));
        $this->assertSame(2, MovimientoSaldoCliente::where('tipo', MovimientoSaldoCliente::TIPO_ABONO)->count());
        $this->getJson(route('clientes.creditos.json', $this->cliente))->assertJsonPath('saldo_favor', 0);
    }

    public function test_reembolsar_saldo_a_favor(): void
    {
        $this->darSaldo(100000);

        $this->post(route('clientes.reembolsar-saldo', $this->cliente), ['monto' => 150000, 'id_cuenta_bancaria' => $this->efectivo->id])
             ->assertSessionHas('error');
        $this->post(route('clientes.reembolsar-saldo', $this->cliente), ['monto' => 40000, 'id_cuenta_bancaria' => $this->efectivo->id])
             ->assertSessionHas('success');

        $this->assertEquals(60000, $this->cliente->fresh()->saldo_favor);
        $this->assertSame(1, ReembolsoCliente::count());
        $this->assertEquals(50000 - 40000, $this->cuadre()->viewData('totalSistema'));
        $this->get(route('clientes.show', $this->cliente))->assertOk()->assertSee('Reembolso')->assertSee('Devolver en dinero');
    }

    // ── Casos borde encontrados en la revisión ────────────────────────────────

    public function test_no_se_anula_un_abono_que_respalda_un_saldo_a_favor(): void
    {
        $venta = $this->venderCredito(2);
        $this->abonar($venta, 200000);
        $this->devolver($venta, 1)->assertSessionHas('success');   // $100.000 a saldo a favor

        $this->get(route('ventas.show', $venta))->assertOk()->assertDontSee(route('abonos.destroy', PagoCredito::firstOrFail()));
        $this->delete(route('abonos.destroy', PagoCredito::firstOrFail()))->assertSessionHas('error');
        $this->assertSame(1, PagoCredito::count());
        $this->assertEquals(100000, $this->cliente->fresh()->saldo_favor);
    }

    public function test_si_la_devolucion_solo_bajo_la_deuda_el_abono_si_se_puede_anular(): void
    {
        $venta = $this->venderCredito(3);
        $this->abonar($venta, 100000);
        $this->devolver($venta, 1)->assertSessionHas('success');   // debía 200k: todo a deuda

        $this->delete(route('abonos.destroy', PagoCredito::firstOrFail()))->assertSessionHas('success');
        $this->assertEquals(200000, $venta->fresh()->saldo_pendiente, '3 pares − 1 devuelto, sin abonos');
    }

    public function test_cantidad_vacia_o_invalida_muestra_error_visible(): void
    {
        $venta = $this->venderCredito(1);
        $linea = $venta->historialVentas()->firstOrFail();

        $this->post(route('ventas.devolucion', $venta), ['items' => [['id_historial_venta' => $linea->id, 'cantidad' => '']], 'destino' => 'saldo'])
             ->assertSessionHas('error');
        $this->post(route('ventas.devolucion', $venta), ['items' => [['id_historial_venta' => $linea->id, 'cantidad' => 1]], 'destino' => 'otro'])
             ->assertSessionHas('error');
        $this->post(route('clientes.reembolsar-saldo', $this->cliente), ['monto' => ''])->assertSessionHas('error');
        $this->assertSame(0, Devolucion::count());
    }

    public function test_desglose_por_cuenta_resta_reembolsos_desde_banco(): void
    {
        $venta = $this->vender(['id_cuenta_bancaria' => $this->nequi->id], 2);
        $this->devolver($venta, 1, 'reembolso', ['id_cuenta_bancaria' => $this->nequi->id]);

        $nequi = collect($this->cuadre()->viewData('desglosePorCuenta'))->firstWhere('nombre', 'Nequi');
        $this->assertEquals(100000, $nequi['total'], '200k de la venta − 100k reembolsados');
        $this->assertEquals(100000, $this->nequi->fresh()->saldo_actual);
    }

    public function test_saldo_a_favor_con_pago_mixto(): void
    {
        $this->darSaldo(30000);

        $venta = $this->vender(['metodo_pago' => 'Mixto', 'id_cuenta_bancaria' => '', 'monto_efectivo' => 20000,
                                'id_cuenta_mixto' => $this->nequi->id, 'id_cliente' => $this->cliente->id, 'monto_saldo_favor' => 30000]);

        $this->assertEquals(20000, $venta->monto_efectivo);
        $this->assertEquals(50000, $venta->monto_transferencia);
        $this->assertEquals(50000, $this->nequi->fresh()->saldo_actual);
        $this->assertSame('Saldo a favor $30.000 + Mixto: efectivo $20.000 + Nequi $50.000', $venta->descripcionPago());
        $r = $this->cuadre();
        $this->assertEquals(70000, $r->viewData('ventasMixto'));
        $this->assertEquals(20000, $r->viewData('mixtoEfectivo'));
    }

    public function test_producto_devuelto_del_todo_no_aparece_en_ganancias(): void
    {
        $venta = $this->vender(['id_cuenta_bancaria' => $this->efectivo->id, 'id_cliente' => $this->cliente->id], 2);
        $this->devolver($venta, 2)->assertSessionHas('success');

        $g = $this->get(route('reportes.ganancias'))->assertOk();
        $this->assertCount(0, $g->viewData('detalles'));
        $this->get(route('ventas.index', ['metodo_pago' => 'Saldo a favor']))->assertOk();
    }

    public function test_reportes_y_pantallas_cuentan_ventas_netas(): void
    {
        $venta = $this->vender(['id_cuenta_bancaria' => $this->efectivo->id, 'id_cliente' => $this->cliente->id], 3);
        $this->devolver($venta, 1)->assertSessionHas('success');

        $r = $this->get(route('reportes.ventas'))->assertOk();
        $this->assertEquals(200000, $r->viewData('totalVentas'));
        $this->assertEquals(100000, $r->viewData('totalGanancia'), '2 pares × (100k − 50k)');

        $g = $this->get(route('reportes.ganancias'))->assertOk();
        $this->assertEquals(200000, $g->viewData('totalIngresos'));

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('clientes.estado-cuenta', $this->cliente))->assertOk()->assertSee('Saldo a favor del cliente');

        // El POS ofrece el saldo del cliente
        $this->get(route('ventas.create'))->assertOk()->assertSee('usarSaldo', false);
        $this->get(route('ventas.movil.venta'))->assertOk()->assertSee('usarSaldo', false);
    }
}
