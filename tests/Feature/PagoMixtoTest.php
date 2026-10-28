<?php

namespace Tests\Feature;

use App\Models\Bodega;
use App\Models\Cliente;
use App\Models\CuadreCaja;
use App\Models\CuentaBancaria;
use App\Models\DetalleProducto;
use App\Models\GastoExterno;
use App\Models\LoteInventario;
use App\Models\PagoCredito;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pago mixto (efectivo + transferencia) de punta a punta: venta, saldo de la cuenta,
 * anulación, cuadre de caja (abierto, cerrado, impreso) y ticket.
 *
 * Usa MySQL/MariaDB (algunas migraciones usan SQL de MySQL), no el sqlite de phpunit.xml:
 *   DB_CONNECTION=mysql DB_DATABASE=visoftec_test DB_USERNAME=root vendor/bin/phpunit --filter PagoMixto
 * La base visoftec_test se borra y recrea en cada corrida — NUNCA apuntar a una base real.
 */
class PagoMixtoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private CuentaBancaria $nequi;
    private CuentaBancaria $efectivo;
    private DetalleProducto $zapato;   // $100.000
    private CuadreCaja $caja;

    protected function setUp(): void
    {
        parent::setUp();

        // Protección: este test borra la base completa
        $this->assertStringContainsString('test', config('database.connections.' . config('database.default') . '.database'),
            'PagoMixtoTest solo debe correr sobre una base de pruebas');

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

        $producto = Producto::create(['nombre' => 'Zapato', 'precio_venta' => 100000, 'stock_minimo' => 0, 'activo' => true]);
        $this->zapato = DetalleProducto::create(['id_producto' => $producto->id, 'talla' => '40', 'codigo_barras' => '10105', 'precio_venta' => 100000]);
        LoteInventario::create([
            'id_detalle_producto' => $this->zapato->id, 'id_bodega' => $bodega->id, 'fecha_ingreso' => now()->subDay(),
            'cantidad_disponible' => 20, 'costo_unitario' => 50000, 'tipo_origen' => 'Propio',
        ]);

        $this->caja = CuadreCaja::create([
            'id_usuario' => $this->admin->id, 'id_sucursal' => $sucursal->id,
            'fecha_apertura' => now()->subHour(), 'saldo_inicial' => 50000, 'estado' => CuadreCaja::ESTADO_ABIERTO,
        ]);

        $this->actingAs($this->admin);
    }

    /** Envía una venta de 1 zapato ($100.000) con los campos de pago dados. */
    private function vender(array $pago, int $cantidad = 1)
    {
        return $this->post(route('ventas.store'), $pago + [
            'items' => [[
                'id_detalle_producto' => $this->zapato->id, 'cantidad' => $cantidad,
                'precio_venta' => 100000, 'es_directo' => 0,
            ]],
        ]);
    }

    private function mixto(float $efectivo, ?int $cuenta = null): array
    {
        return ['metodo_pago' => 'Mixto', 'id_cuenta_bancaria' => '', 'monto_efectivo' => $efectivo, 'id_cuenta_mixto' => $cuenta ?? $this->nequi->id];
    }

    private function stock(): int
    {
        return (int) LoteInventario::where('id_detalle_producto', $this->zapato->id)->sum('cantidad_disponible');
    }

    public function test_venta_mixta_guarda_el_reparto_y_solo_la_transferencia_entra_a_la_cuenta(): void
    {
        $this->vender($this->mixto(60000))->assertRedirect();

        $venta = Venta::latest('id')->firstOrFail();
        $this->assertSame('Mixto', $venta->metodo_pago);
        $this->assertEquals(100000, $venta->total);
        $this->assertEquals(60000, $venta->monto_efectivo);
        $this->assertEquals(40000, $venta->monto_transferencia);
        $this->assertSame($this->nequi->id, $venta->id_cuenta_bancaria);
        $this->assertEquals(40000, $this->nequi->fresh()->saldo_actual, 'A Nequi solo entra la parte por transferencia');
        $this->assertEquals(40000, $this->nequi->movimientos()->first()->monto);
        $this->assertSame(19, $this->stock());
        $this->assertStringContainsString('efectivo $60.000 + Nequi $40.000', $venta->descripcionPago());
    }

    public function test_reparto_invalido_no_crea_venta_ni_toca_stock_ni_saldo(): void
    {
        foreach ([
            'efectivo 0'         => $this->mixto(0),
            'efectivo = total'   => $this->mixto(100000),
            'efectivo > total'   => $this->mixto(150000),
            'sin cuenta'         => ['metodo_pago' => 'Mixto', 'monto_efectivo' => 60000, 'id_cuenta_mixto' => ''],
        ] as $caso => $pago) {
            $this->vender($pago)->assertSessionHas('error');
            $this->assertSame(0, Venta::count(), "No debe crear venta: $caso");
        }

        // Una cuenta tipo Efectivo no sirve como "cuenta de la transferencia"
        $this->vender($this->mixto(60000, $this->efectivo->id))->assertSessionHasErrors('id_cuenta_mixto');

        $this->assertSame(0, Venta::count());
        $this->assertSame(20, $this->stock());
        $this->assertEquals(0, $this->nequi->fresh()->saldo_actual);
    }

    public function test_credito_ignora_los_campos_de_mixto(): void
    {
        $cliente = Cliente::create(['documento' => '123', 'nombre' => 'Ana', 'limite_credito' => 0]);

        $this->vender($this->mixto(60000) + ['es_credito' => 1, 'dias_credito' => 30, 'id_cliente' => $cliente->id])->assertRedirect();

        $venta = Venta::latest('id')->firstOrFail();
        $this->assertSame(Venta::METODO_CREDITO, $venta->metodo_pago);
        $this->assertNull($venta->monto_efectivo);
        $this->assertNull($venta->id_cuenta_bancaria);
        $this->assertEquals(0, $this->nequi->fresh()->saldo_actual);
    }

    public function test_anular_venta_mixta_solo_revierte_la_transferencia(): void
    {
        $this->vender($this->mixto(60000));
        $this->vender(['id_cuenta_bancaria' => $this->nequi->id]);   // transferencia normal de $100.000
        $this->assertEquals(140000, $this->nequi->fresh()->saldo_actual);

        $mixta = Venta::where('metodo_pago', 'Mixto')->firstOrFail();
        $this->delete(route('ventas.destroy', $mixta))->assertRedirect();

        $this->assertTrue($mixta->fresh()->anulada);
        $this->assertEquals(100000, $this->nequi->fresh()->saldo_actual, 'Solo se retiran los $40.000 de la mixta');
        $this->assertSame(19, $this->stock());
    }

    public function test_transferencia_y_efectivo_normales_no_cambian(): void
    {
        $this->vender(['id_cuenta_bancaria' => $this->nequi->id]);
        $this->vender(['id_cuenta_bancaria' => $this->efectivo->id]);

        $this->assertSame(['Transferencia', 'Efectivo'], Venta::orderBy('id')->pluck('metodo_pago')->all());
        $this->assertNull(Venta::first()->monto_transferencia);
        $this->assertEquals(100000, $this->nequi->fresh()->saldo_actual);
    }

    public function test_cuadre_abierto_reparte_efectivo_y_transferencias(): void
    {
        $this->vender($this->mixto(60000));                           // 60k cajón + 40k Nequi
        $this->vender(['id_cuenta_bancaria' => $this->efectivo->id]); // 100k cajón
        $this->vender(['id_cuenta_bancaria' => $this->nequi->id]);    // 100k Nequi
        // Mixta vieja, sin reparto guardado: se sigue contando toda como transferencia
        Venta::create(['id_usuario' => $this->admin->id, 'id_sucursal' => $this->caja->id_sucursal, 'metodo_pago' => 'Mixto',
                       'total' => 30000, 'saldo_pendiente' => 0, 'estado_credito' => 'Pagado', 'es_credito' => false]);
        // Gasto en efectivo 5k y gasto desde Nequi 7k (este no sale del cajón)
        GastoExterno::create(['descripcion' => 'Aseo', 'valor' => 5000, 'id_usuario' => $this->admin->id, 'id_sucursal' => $this->caja->id_sucursal]);
        GastoExterno::create(['descripcion' => 'Internet', 'valor' => 7000, 'id_usuario' => $this->admin->id, 'id_sucursal' => $this->caja->id_sucursal, 'id_cuenta_bancaria' => $this->nequi->id]);
        // Abono a crédito por Nequi 20k
        $cliente = Cliente::create(['documento' => '9', 'nombre' => 'Luis', 'limite_credito' => 0]);
        $credito = Venta::create(['id_usuario' => $this->admin->id, 'id_sucursal' => $this->caja->id_sucursal, 'id_cliente' => $cliente->id,
                                  'metodo_pago' => 'Crédito', 'es_credito' => true, 'total' => 50000, 'saldo_pendiente' => 50000, 'estado_credito' => 'Parcial',
                                  'created_at' => now()->subDays(3)]);
        PagoCredito::create(['id_venta' => $credito->id, 'id_usuario' => $this->admin->id, 'id_sucursal' => $this->caja->id_sucursal,
                             'monto' => 20000, 'metodo' => 'Transferencia', 'id_cuenta_bancaria' => $this->nequi->id]);

        $r = $this->get(route('cuadre-caja.show', $this->caja))->assertOk();

        $this->assertEquals(130000, $r->viewData('ventasMixto'));
        $this->assertEquals(60000, $r->viewData('mixtoEfectivo'));
        $this->assertEquals(70000, $r->viewData('mixtoElectronico'));   // 40k nueva + 30k vieja
        $this->assertEquals(5000, $r->viewData('gastosEfectivo'));
        $this->assertEquals(0, $r->viewData('pagosCreditoEfectivo'));
        $this->assertEquals(20000, $r->viewData('pagosCreditoElectronico'));

        // Cajón: 50k inicial + 100k efectivo + 60k de la mixta − 5k gasto = 205k
        // Transferencias: 100k + 70k mixto + 20k abono = 190k   →   suma = totalSistema
        $r->assertSee('$205.000')->assertSee('$190.000');
        $this->assertEquals(205000 + 190000, $r->viewData('totalSistema'));

        // Desglose por cuenta: Nequi = 40k (mixta) + 100k (transf.) + 20k (abono) − 7k (gasto)
        $nequi = collect($r->viewData('desglosePorCuenta'))->firstWhere('nombre', 'Nequi');
        $this->assertEquals(153000, $nequi['total']);

        $this->get(route('cuadre-caja.imprimir', $this->caja))->assertOk()->assertSee('efectivo $60.000 + Nequi $40.000');
    }

    public function test_cerrar_cuadre_con_mixta_y_ver_cerrado_e_impreso(): void
    {
        $this->vender($this->mixto(60000));

        $this->put(route('cuadre-caja.cerrar', $this->caja), [
            'efectivo_contado' => 110000, 'transferencia_contada' => 40000,
        ])->assertRedirect();

        $caja = $this->caja->fresh();
        $this->assertSame(CuadreCaja::ESTADO_CERRADO, $caja->estado);
        $this->assertEquals(100000, $caja->ventas_mixto);
        $this->assertEquals(150000, $caja->total_sistema);
        $this->assertEquals(0, $caja->diferencia, '50k inicial + 60k efectivo = 110k cajón; 40k Nequi');

        $this->get(route('cuadre-caja.show', $caja))->assertOk();
        $this->get(route('cuadre-caja.imprimir', $caja))->assertOk();
    }

    public function test_ticket_y_detalle_muestran_el_reparto(): void
    {
        $this->vender($this->mixto(60000));
        $venta = Venta::latest('id')->firstOrFail();

        $this->get(route('ventas.ticket', $venta))->assertOk()->assertSee('$60.000')->assertSee('$40.000')->assertSee('Nequi');
        $this->get(route('ventas.show', $venta))->assertOk()->assertSee('efectivo $60.000 + Nequi $40.000');
        $this->get(route('ventas.index'))->assertOk();
        $this->get(route('ventas.create'))->assertOk()->assertSee('montoEfectivoMixto', false);
    }
}
