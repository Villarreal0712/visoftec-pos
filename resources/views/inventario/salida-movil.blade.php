@extends('layouts.mobile')

@section('title', 'Salida Móvil')

@section('content')
<div
    x-data="salidaMovil(
        '{{ route('inventario.salida.buscar') }}',
        '{{ $bodegaDefecto }}',
        @js($carritoInicial),
        @js(old('tipo_operacion', 'Merma')),
        @js(old('observacion', '')),
        @js((string) old('id_local_externo', ''))
    )"
    class="flex flex-col min-h-screen pb-36"
>

    {{-- ══════ BARRA SUPERIOR ══════ --}}
    <header class="sticky top-0 z-30 bg-amber-600 text-white px-4 py-3 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('ventas.movil') }}"
               class="text-amber-100 hover:text-white p-1.5 rounded-lg hover:bg-amber-700 transition-colors mr-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <span class="text-2xl">📤</span>
            <div>
                <p class="text-sm font-bold leading-tight">Salida de Inventario</p>
                <p class="text-xs text-amber-100 leading-tight">{{ auth()->user()->nombre }}</p>
            </div>
        </div>
        <span x-show="items.length > 0"
            class="bg-white text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full"
            x-text="totalItems + ' uds.'">
        </span>
    </header>

    @if($errors->any())
    <div class="mx-3 mt-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-3 py-2">
        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
    @endif

    {{-- ══════ FORMULARIO ══════ --}}
    <form id="form-salida-movil" method="POST" action="{{ route('inventario.salida.store') }}" class="flex flex-col flex-1 px-3 pt-3 gap-3">
        @csrf
        <input type="hidden" name="_from_movil" value="1">
        <input type="hidden" name="id_bodega" :value="idBodega">
        <input type="hidden" name="tipo_operacion" :value="tipo">
        <input type="hidden" name="observacion" :value="observacion">
        <input type="hidden" name="id_local_externo" :value="tipo === 'Transferencia' ? idLocal : ''">

        <template x-for="(item, idx) in items" :key="item.id">
            <span>
                <input type="hidden" :name="'items[' + idx + '][id_detalle_producto]'" :value="item.id">
                <input type="hidden" :name="'items[' + idx + '][cantidad]'" :value="item.cantidad">
            </span>
        </template>

        {{-- ── DATOS DE LA SALIDA ───────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-4 space-y-3">
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1.5">🏬 Bodega</label>
                <select x-model="idBodega" @change="cambiarBodega()"
                    class="w-full text-sm border border-amber-300 rounded-xl px-3 py-3 bg-white focus:ring-2 focus:ring-amber-300 font-medium text-gray-900">
                    @foreach($bodegas as $bodega)
                    <option value="{{ $bodega->id }}">{{ $bodega->nombre }}{{ $bodega->id_sucursal === null ? ' (Acopio)' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1.5">Tipo de salida</label>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="t in tipos" :key="t.valor">
                        <button type="button" @click="tipo = t.valor"
                            class="py-2.5 px-2 text-xs font-bold rounded-xl border-2 transition-colors"
                            :class="tipo === t.valor ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-gray-200 text-gray-500'"
                            x-text="t.texto"></button>
                    </template>
                </div>
            </div>

            <div x-show="tipo === 'Transferencia'" x-cloak>
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1.5">Local externo</label>
                <select x-model="idLocal"
                    class="w-full text-sm border border-gray-200 rounded-xl px-3 py-3 bg-white focus:ring-2 focus:ring-amber-300">
                    <option value="">— Selecciona el local —</option>
                    @foreach($localesExternos as $local)
                    <option value="{{ $local->id }}">{{ $local->nombre_local }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wide block mb-1.5">Motivo *</label>
                <input type="text" x-model="observacion" maxlength="200" placeholder="Ej: frasco roto, conteo físico..."
                    class="w-full text-sm border rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-300"
                    :class="observacion.trim() ? 'border-gray-200' : 'border-amber-300'">
            </div>
        </div>

        {{-- ── ESCÁNER + BÚSQUEDA ───────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 space-y-2">
            <button type="button" @click="abrirScanner()"
                class="w-full py-5 rounded-2xl font-bold text-lg flex items-center justify-center gap-3 shadow-md transition-colors select-none text-white
                    bg-amber-500 hover:bg-amber-600 active:bg-amber-700">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.24M16.57 9H14m2.86-4.93A9 9 0 105.4 18.6M15 15l3 3"/>
                    <rect x="3" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="3" y="16" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                    <rect x="16" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/>
                </svg>
                Escanear con cámara
            </button>

            <div class="flex gap-2">
                <input type="text" x-model="busqueda"
                    @input.debounce.400ms="buscarManual()"
                    @keydown.enter.prevent="buscarManual()"
                    @keydown.escape="resultados = []"
                    placeholder="O escribe nombre / código..."
                    autocomplete="off"
                    class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-300">
                <button type="button" @click="buscarManual()"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl text-gray-600 active:bg-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>

            <div x-show="resultados.length > 0 && !scannerModal" class="border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white">
                <div class="flex items-center justify-between px-3 py-1.5 bg-gray-50 border-b border-gray-100">
                    <span class="text-xs text-gray-500 font-medium" x-text="resultados.length + ' resultado(s)'"></span>
                    <button type="button" @click="resultados = []; busqueda = ''" class="text-xs text-gray-400 hover:text-gray-600">Cerrar</button>
                </div>
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="border-b border-gray-50 last:border-0 px-3 py-2.5 flex items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 leading-tight" x-text="producto.nombre"></p>
                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                <span x-show="producto.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="'T ' + producto.talla"></span>
                                <span class="text-xs font-semibold text-amber-600" x-text="'Stock: ' + producto.stock_bodega"></span>
                            </div>
                        </div>
                        <button type="button"
                            @click="agregarItem(producto); resultados = []; busqueda = ''"
                            class="px-3 py-1.5 bg-amber-500 text-white text-xs font-bold rounded-xl active:bg-amber-600 flex-shrink-0">
                            + Agregar
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── LISTA DE ÍTEMS ───────────────────────────────────────────────── --}}
        <template x-if="items.length === 0">
            <div class="bg-white rounded-2xl border border-dashed border-amber-200 py-10 text-center shadow-sm">
                <span class="text-5xl block mb-2">📤</span>
                <p class="text-sm font-medium text-gray-400">Sin productos aún</p>
                <p class="text-xs text-gray-300 mt-1">Escanea o busca un producto de esta bodega</p>
            </div>
        </template>

        <template x-if="items.length > 0">
            <div class="space-y-2">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-sm font-bold text-gray-700">Ítems a sacar (<span x-text="items.length"></span>)</h2>
                    <button type="button" @click="items = []" class="text-xs text-red-400 hover:text-red-600 font-medium">Limpiar</button>
                </div>

                <template x-for="(item, index) in items" :key="item.id">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
                        <div class="flex items-start gap-2 mb-2.5">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 leading-tight" x-text="item.nombre"></p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span x-show="item.talla" class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded" x-text="'T' + item.talla"></span>
                                    <span class="text-xs text-amber-600 font-semibold" x-text="'Disponible: ' + item.stock_bodega"></span>
                                </div>
                            </div>
                            <button type="button" @click="quitarItem(index)"
                                class="flex-shrink-0 w-8 h-8 rounded-xl bg-red-50 hover:bg-red-100 text-red-400 flex items-center justify-center active:bg-red-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">Cantidad a sacar:</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="cambiarCantidad(index, -1)"
                                    class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 active:bg-gray-300 flex items-center justify-center text-gray-600 font-bold text-lg select-none">−</button>
                                <span class="w-10 text-center text-base font-extrabold text-gray-900" x-text="item.cantidad"></span>
                                <button type="button" @click="cambiarCantidad(index, 1)"
                                    class="w-9 h-9 rounded-full bg-amber-100 hover:bg-amber-200 active:bg-amber-300 flex items-center justify-center text-amber-700 font-bold text-lg select-none">+</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </form>

    {{-- ══════ BARRA FIJA INFERIOR ══════ --}}
    <div class="fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-gray-200 shadow-2xl safe-area-bottom">
        <div class="px-3 py-2">
            {{-- Confirmación en dos toques (el visor móvil puede bloquear confirm()) --}}
            <template x-if="confirmando">
                <div class="mb-2 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 text-xs text-amber-800 font-medium"
                     x-text="'¿Sacar ' + totalItems + ' unidad(es) de ' + nombreBodega + '? Toca de nuevo para confirmar.'"></div>
            </template>
            <div class="flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-400 truncate">
                        <span class="font-medium text-gray-600" x-text="nombreBodega"></span>
                        <span x-show="items.length > 0" x-text="' · ' + items.length + ' prod. · ' + totalItems + ' uds.'"></span>
                    </p>
                </div>
                <button type="button" @click="enviar()"
                    :disabled="items.length === 0 || enviando"
                    class="flex-shrink-0 px-6 py-4 rounded-2xl font-bold text-base text-white shadow-lg transition-colors active:scale-95
                        disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100 flex items-center gap-2"
                    :class="confirmando ? 'bg-red-600 hover:bg-red-500' : 'bg-amber-500 hover:bg-amber-400'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="confirmando ? 'Confirmar' : 'Registrar'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- ══════ MODAL SCANNER ══════ --}}
    <div x-show="scannerModal" x-cloak
         class="fixed inset-0 z-50 flex flex-col bg-black"
         style="display:none">

        <div class="flex items-center justify-between px-4 py-3 bg-black/80 text-white flex-shrink-0">
            <div>
                <p class="font-bold text-base">Escanear código</p>
                <p class="text-xs text-gray-400">Apunta la cámara al código de barras</p>
            </div>
            <button type="button" @click="cerrarScanner()"
                class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white active:bg-white/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="relative flex-1 flex items-center justify-center overflow-hidden bg-black">
            <div id="qr-salida-reader" class="w-full max-w-sm"></div>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="border-2 border-amber-400 rounded-xl w-72 h-28"></div>
            </div>
        </div>

        <div class="bg-black/90 px-4 py-3 flex-shrink-0 space-y-2">
            <div class="flex gap-2">
                <input type="text" x-model="busqueda"
                    placeholder="O escribe el código manualmente..."
                    @keydown.enter.prevent="buscarPorCodigo(busqueda)"
                    class="flex-1 text-sm bg-white/10 border border-white/20 text-white placeholder-gray-500 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-amber-400">
                <button type="button" @click="buscarPorCodigo(busqueda)"
                    class="px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-white rounded-xl font-bold text-sm active:bg-amber-600">
                    Buscar
                </button>
            </div>

            <div x-show="resultados.length > 0" class="space-y-1.5 max-h-52 overflow-y-auto">
                <template x-for="producto in resultados" :key="producto.id">
                    <div class="bg-white/10 rounded-xl px-3 py-2.5 flex items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white leading-tight" x-text="producto.nombre"></p>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span x-show="producto.talla" class="text-xs text-gray-400" x-text="'T' + producto.talla"></span>
                                <span class="text-xs text-amber-300 font-semibold" x-text="'Stock: ' + producto.stock_bodega"></span>
                            </div>
                        </div>
                        <button type="button"
                            @click="agregarItem(producto); cerrarScanner()"
                            class="px-3 py-2 bg-amber-500 text-white text-xs font-bold rounded-xl active:bg-amber-600 flex-shrink-0">
                            + Agregar
                        </button>
                    </div>
                </template>
                <button type="button" @click="reanudarScanner()"
                    class="w-full py-2 text-xs text-amber-300 hover:text-amber-200 font-medium">
                    ↺ Escanear otro código
                </button>
            </div>

            <div x-show="buscando" class="flex items-center justify-center gap-2 py-2">
                <span class="text-xs text-gray-400">Buscando...</span>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #qr-salida-reader { width: 100% !important; border: none !important; }
    #qr-salida-reader video { width: 100% !important; height: 100% !important; object-fit: cover !important; }
    #qr-salida-reader__scan_region { min-height: 250px; }
    #qr-salida-reader__dashboard { display: none !important; }
    .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom, 8px); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('salidaMovil', (buscarUrl, bodegaDefecto, carritoInicial, tipoInicial, obsInicial, localInicial) => ({
        buscarUrl,
        bodegas: @js($bodegas->map(fn($b) => ['id' => (string) $b->id, 'nombre' => $b->nombre])->values()),
        idBodega: String(bodegaDefecto),
        tipo: tipoInicial || 'Merma',
        tipos: [
            { valor: 'Merma',         texto: 'Merma / Daño' },
            { valor: 'Ajuste',        texto: 'Ajuste' },
            { valor: 'Salida',        texto: 'Devolución' },
            { valor: 'Transferencia', texto: 'A local externo' },
        ],
        observacion: obsInicial || '',
        idLocal: localInicial || '',
        items: carritoInicial || [],
        confirmando: false,
        enviando: false,

        escaner: null,
        scannerActivo: false,
        scannerModal: false,
        procesandoScan: false,
        busqueda: '',
        resultados: [],
        buscando: false,

        get totalItems() { return this.items.reduce((s, i) => s + i.cantidad, 0); },
        get nombreBodega() { return this.bodegas.find(b => b.id === this.idBodega)?.nombre ?? ''; },

        cambiarBodega() {
            if (this.items.length > 0) showToast('Cambiaste de bodega: se vació la lista', 'info');
            this.items = []; this.resultados = []; this.confirmando = false;
        },

        async abrirScanner() {
            this.scannerModal = true; this.resultados = []; this.busqueda = '';
            await this.$nextTick(); this.iniciarScanner();
        },
        iniciarScanner() {
            if (this.scannerActivo) return;
            this.escaner = new window.Html5Qrcode('qr-salida-reader');
            this.escaner.start(
                { facingMode: 'environment' }, { fps: 10, qrbox: { width: 260, height: 120 } },
                async (codigo) => {
                    if (this.procesandoScan) return;
                    this.procesandoScan = true; this.busqueda = codigo;
                    await this.buscarPorCodigo(codigo);
                    setTimeout(() => { this.procesandoScan = false; }, 2000);
                }, () => {}
            ).then(() => { this.scannerActivo = true; })
             .catch(err => { showToast('No se pudo acceder a la cámara: ' + err, 'error'); this.cerrarScanner(); });
        },
        cerrarScanner() {
            if (this.escaner && this.scannerActivo) { this.escaner.stop().catch(() => {}); this.escaner = null; this.scannerActivo = false; }
            this.scannerModal = false;
        },
        detenerEscanerTemporal() {
            if (this.escaner && this.scannerActivo) { this.escaner.stop().catch(() => {}); this.scannerActivo = false; }
        },
        reanudarScanner() { this.resultados = []; this.busqueda = ''; this.iniciarScanner(); },

        async buscarPorCodigo(codigo) {
            codigo = (codigo || '').trim();
            if (!codigo) return;
            this.buscando = true;
            try {
                const url = `${this.buscarUrl}?q=${encodeURIComponent(codigo)}&id_bodega=${encodeURIComponent(this.idBodega)}`;
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                if (!res.ok) throw new Error();
                const data = await res.json();
                const exacto = data.find(p => p.codigo_barras === codigo);
                if (exacto) {
                    this.agregarItem(exacto);
                    this.resultados = []; this.busqueda = '';
                    if (this.scannerModal) this.cerrarScanner();
                } else if (data.length > 0) {
                    this.resultados = data; this.detenerEscanerTemporal();
                } else {
                    showToast('Ese producto no tiene stock en esta bodega', 'warning');
                }
            } catch { showToast('Error al buscar producto', 'error'); }
            finally { this.buscando = false; }
        },
        async buscarManual() {
            if (this.busqueda.trim().length < 2) { this.resultados = []; return; }
            await this.buscarPorCodigo(this.busqueda);
        },

        agregarItem(p) {
            this.confirmando = false;
            const existente = this.items.find(i => i.id === p.id);
            if (existente) {
                existente.cantidad < existente.stock_bodega ? existente.cantidad++ : showToast('Sin más stock en esta bodega', 'warning');
            } else {
                this.items.push({ id: p.id, nombre: p.nombre, talla: p.talla,
                    codigo_barras: p.codigo_barras, stock_bodega: p.stock_bodega, cantidad: 1 });
                showToast(`${p.nombre} agregado`, 'success', 1500);
            }
        },
        quitarItem(index) { this.items.splice(index, 1); this.confirmando = false; },
        cambiarCantidad(index, delta) {
            this.confirmando = false;
            const item = this.items[index]; const nueva = item.cantidad + delta;
            if (nueva <= 0) this.items.splice(index, 1);
            else if (nueva <= item.stock_bodega) item.cantidad = nueva;
            else showToast('Sin más stock disponible', 'warning');
        },

        enviar() {
            if (this.items.length === 0) { showToast('Agrega al menos un producto', 'warning'); return; }
            if (!this.observacion.trim()) { showToast('Escribe el motivo de la salida', 'warning'); return; }
            if (this.tipo === 'Transferencia' && !this.idLocal) { showToast('Selecciona el local externo', 'warning'); return; }
            if (!this.confirmando) { this.confirmando = true; return; }
            this.enviando = true;
            this.$nextTick(() => document.getElementById('form-salida-movil').submit());
        },
    }));
});
</script>
@endpush
@endsection
