import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import JsBarcode from 'jsbarcode';
import { Html5Qrcode } from 'html5-qrcode';
import { jsPDF } from 'jspdf';
window.Chart = Chart;
window.JsBarcode = JsBarcode;
window.Html5Qrcode = Html5Qrcode;
window.jsPDF = jsPDF;

window.Alpine = Alpine;

// ─── Toast Notifications ─────────────────────────────────────────────────────

window.showToast = function(message, type = 'success', duration = 4000) {
    const container = document.getElementById('toast-container') || createToastContainer();

    const icons = {
        success: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`,
        error: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`,
        warning: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,
        info: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        ${icons[type] || icons.info}
        <span class="flex-1">${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-2 opacity-70 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'all 0.3s ease-out';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

// ─── Confirmar Eliminación ────────────────────────────────────────────────────

window.confirmarEliminar = function(formId, mensaje = '¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.') {
    if (confirm(mensaje)) {
        document.getElementById(formId).submit();
    }
};

// ─── Separador de miles para inputs ──────────────────────────────────────────

window.fmtMiles = function(n) {
    const num = parseInt(String(n ?? '').replace(/\D/g, ''), 10);
    return (isNaN(num) || num === 0) ? '' : new Intl.NumberFormat('es-CO').format(num);
};

// Actualiza un input type="text" con miles y mantiene la variable Alpine sincronizada
window.applyMiles = function(event, setter) {
    const digits = event.target.value.replace(/\D/g, '');
    const n = parseInt(digits, 10);
    const val = isNaN(n) ? 0 : n;
    setter(val);
    event.target.value = val ? window.fmtMiles(val) : '';
};

// ─── Formatear Números como Moneda ───────────────────────────────────────────

window.formatMoney = function(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

// ─── URL Base del sistema (funciona en subdirectorio XAMPP) ──────────────────

function getBaseUrl() {
    return document.querySelector('meta[name="base-url"]')?.content
        || window.location.origin;
}

// ─── Alpine.js Data Components ───────────────────────────────────────────────

document.addEventListener('alpine:init', () => {

    // POS - Punto de Venta
    Alpine.data('pos', (buscarUrl, urlClienteRapido, localesExternos, puedeVenderPrestamo, cuentas) => ({
        // Si Blade pasa la URL úsala, si no, constrúyela desde el meta tag
        buscarUrl: buscarUrl || (getBaseUrl() + '/api/productos/buscar'),
        urlClienteRapido: urlClienteRapido || '',
        localesExternos: localesExternos || [],
        puedeVenderPrestamo: puedeVenderPrestamo || false,
        busqueda: '',
        resultados: [],
        buscando: false,
        carrito: [],
        clienteId: '',
        clientesExtra: [],   // clientes creados en el POS, se añaden al <select>
        cuentas: cuentas || [],
        // pagoSeleccionado: id de la cuenta bancaria elegida, o el literal 'Mixto'
        pagoSeleccionado: cuentas?.[0] ? String(cuentas[0].id) : 'Mixto',
        esCredito: false,
        diasCredito: 30,
        observaciones: '',
        efectivoPagado: 0,

        // ── Modal nuevo cliente ──────────────────────────────
        modalCliente: false,
        guardandoCliente: false,
        erroresCliente: {},
        nuevoCliente: { documento: '', nombre: '', telefono: '', correo: '', limite_credito: '' },

        abrirModalCliente() {
            this.nuevoCliente = { documento: '', nombre: '', telefono: '', correo: '', limite_credito: '' };
            this.erroresCliente = {};
            this.modalCliente = true;
            this.$nextTick(() => this.$refs.inputDocumento?.focus());
        },

        cerrarModalCliente() {
            this.modalCliente = false;
        },

        async guardarCliente() {
            if (!this.nuevoCliente.documento || !this.nuevoCliente.nombre) {
                this.erroresCliente = {
                    documento: !this.nuevoCliente.documento ? 'El documento es obligatorio.' : '',
                    nombre:    !this.nuevoCliente.nombre    ? 'El nombre es obligatorio.'    : '',
                };
                return;
            }
            this.guardandoCliente = true;
            this.erroresCliente = {};
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const body = new FormData();
                body.append('_token', token);
                Object.entries(this.nuevoCliente).forEach(([k, v]) => { if (v) body.append(k, v); });

                const res = await fetch(this.urlClienteRapido, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });

                const data = await res.json();

                if (!res.ok) {
                    // Errores de validación 422
                    this.erroresCliente = data.errors || {};
                    return;
                }

                // Añadir al listado del <select> y seleccionar automáticamente
                this.clientesExtra.push(data);
                this.clienteId = String(data.id);
                showToast(`Cliente "${data.nombre}" creado y seleccionado.`, 'success');
                this.cerrarModalCliente();
            } catch {
                showToast('Error de conexión. Intenta de nuevo.', 'error');
            } finally {
                this.guardandoCliente = false;
            }
        },
        // ────────────────────────────────────────────────────

        get total() {
            return this.carrito.reduce((sum, item) => sum + (item.cantidad * item.precio), 0);
        },

        get cambio() {
            return Math.max(0, this.efectivoPagado - this.total);
        },

        get idCuentaBancariaSeleccionada() {
            return this.pagoSeleccionado !== 'Mixto' ? this.pagoSeleccionado : '';
        },

        get esEfectivoSeleccionado() {
            if (this.pagoSeleccionado === 'Mixto') return false;
            const cuenta = this.cuentas.find(c => String(c.id) === String(this.pagoSeleccionado));
            return cuenta?.tipo === 'Efectivo';
        },

        async buscarProducto() {
            if (this.busqueda.length < 2) {
                this.resultados = [];
                return;
            }
            this.buscando = true;
            try {
                const url = `${this.buscarUrl}?q=${encodeURIComponent(this.busqueda)}`;
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();

                // Coincidencia exacta por código de barras → agregar directo sin mostrar dropdown
                const exacto = data.find(p => p.codigo_barras === this.busqueda.trim());
                if (exacto) {
                    if (exacto.stock === 0) {
                        // Sin stock: mostrar dropdown para que el usuario vea la opción préstamo
                        this.resultados = data;
                        showToast('Sin stock — solo disponible como préstamo', 'warning', 3000);
                    } else {
                        this.agregarProducto(exacto);
                    }
                    return;
                }

                this.resultados = data;
            } catch (e) {
                console.error('Error búsqueda:', e);
                showToast('Error al buscar productos', 'error');
            } finally {
                this.buscando = false;
            }
        },

        agregarProducto(producto) {
            if (producto.stock === 0) {
                showToast('Sin stock disponible', 'warning');
                return;
            }
            const existente = this.carrito.find(i => i.id === producto.id && !i.esDirecto);
            if (existente) {
                if (existente.cantidad < producto.stock) {
                    existente.cantidad++;
                } else {
                    showToast('Stock insuficiente', 'warning');
                }
            } else {
                this.carrito.push({
                    id:           producto.id,
                    producto:     producto.producto,
                    talla:        producto.talla,
                    precio:       producto.precio,
                    precioBase:   producto.precio,
                    costo:        producto.costo || 0,
                    cantidad:     1,
                    stock:        producto.stock,
                    esDirecto:    false,
                    costoDirecto: 0,
                });
            }
            this.busqueda = '';
            this.resultados = [];
        },

        agregarDirecto(producto) {
            // Los directos siempre se agregan como ítem nuevo (pueden coexistir con stock normal)
            this.carrito.push({
                id:              producto.id,
                producto:        producto.producto,
                talla:           producto.talla,
                precio:          producto.precio,
                precioBase:      producto.precio,
                costo:           0,
                cantidad:        1,
                stock:           0,
                esDirecto:       true,
                costoDirecto:    0,
                localExternoId:  '',
            });
            this.busqueda = '';
            this.resultados = [];
            showToast(`${producto.producto} agregado como préstamo — ingresa el costo y el local`, 'warning', 4000);
        },

        quitarProducto(index) {
            this.carrito.splice(index, 1);
        },

        cambiarCantidad(index, delta) {
            const item = this.carrito[index];
            const nuevaCantidad = item.cantidad + delta;
            if (nuevaCantidad <= 0) {
                this.carrito.splice(index, 1);
            } else if (item.esDirecto || nuevaCantidad <= item.stock) {
                item.cantidad = nuevaCantidad;
            } else {
                showToast('Stock insuficiente', 'warning');
            }
        },

        limpiarCarrito() {
            if (confirm('¿Limpiar el carrito?')) {
                this.carrito = [];
                this.busqueda = '';
                this.resultados = [];
            }
        },

        prepararFormulario() {
            if (this.carrito.length === 0) {
                showToast('El carrito está vacío', 'warning');
                return false;
            }
            const sinLocal = this.carrito.find(i => i.esDirecto && !i.localExternoId);
            if (sinLocal) {
                showToast(`Selecciona el local externo de "${sinLocal.producto}"`, 'warning');
                return false;
            }
            // Aviso (no bloqueante) si hay préstamos sin costo — se puede cuadrar después
            const sinCosto = this.carrito.filter(i => i.esDirecto && !i.costoDirecto);
            if (sinCosto.length > 0) {
                showToast(`${sinCosto.length} préstamo(s) sin costo — recuerda cuadrar después en Cuentas Externos`, 'info', 6000);
            }
            return true;
        },
    }));

    // ─── POS Móvil ───────────────────────────────────────────────────────────────
    Alpine.data('posMovil', (buscarUrl, urlClienteRapido, localesExternos, puedeVenderPrestamo, cuentas) => ({
        buscarUrl: buscarUrl || (getBaseUrl() + '/api/productos/buscar'),
        urlClienteRapido: urlClienteRapido || '',
        localesExternos: localesExternos || [],
        puedeVenderPrestamo: puedeVenderPrestamo || false,

        // Carrito
        carrito: [],
        clienteId: '',
        clientesExtra: [],
        cuentas: cuentas || [],
        pagoSeleccionado: cuentas?.[0] ? String(cuentas[0].id) : 'Mixto',
        esCredito: false,
        diasCredito: 30,
        observaciones: '',
        efectivoPagado: 0,

        // Scanner
        escaner: null,
        scannerActivo: false,
        scannerModal: false,
        procesandoScan: false,
        busqueda: '',
        resultados: [],
        buscando: false,

        // Modal cliente
        modalCliente: false,
        guardandoCliente: false,
        erroresCliente: {},
        nuevoCliente: { documento: '', nombre: '', telefono: '', correo: '', limite_credito: '' },

        get total() {
            return this.carrito.reduce((sum, i) => sum + i.cantidad * i.precio, 0);
        },
        get cambio() {
            return Math.max(0, this.efectivoPagado - this.total);
        },

        get idCuentaBancariaSeleccionada() {
            return this.pagoSeleccionado !== 'Mixto' ? this.pagoSeleccionado : '';
        },

        get esEfectivoSeleccionado() {
            if (this.pagoSeleccionado === 'Mixto') return false;
            const cuenta = this.cuentas.find(c => String(c.id) === String(this.pagoSeleccionado));
            return cuenta?.tipo === 'Efectivo';
        },

        // ── Scanner ──────────────────────────────────────────────────────────────

        async abrirScanner() {
            this.scannerModal = true;
            this.resultados = [];
            this.busqueda = '';
            await this.$nextTick();
            this.iniciarScanner();
        },

        iniciarScanner() {
            if (this.scannerActivo) return;
            this.escaner = new window.Html5Qrcode('qr-reader');
            this.escaner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 260, height: 120 } },
                async (codigo) => {
                    if (this.procesandoScan) return;
                    this.procesandoScan = true;
                    this.busqueda = codigo;
                    await this.buscarPorCodigo(codigo);
                    setTimeout(() => { this.procesandoScan = false; }, 2000);
                },
                () => {}
            ).then(() => {
                this.scannerActivo = true;
            }).catch((err) => {
                showToast('No se pudo acceder a la cámara: ' + err, 'error');
                this.cerrarScanner();
            });
        },

        cerrarScanner() {
            if (this.escaner && this.scannerActivo) {
                this.escaner.stop().catch(() => {});
                this.escaner = null;
                this.scannerActivo = false;
            }
            this.scannerModal = false;
        },

        async buscarPorCodigo(codigo) {
            this.buscando = true;
            try {
                const res = await fetch(`${this.buscarUrl}?q=${encodeURIComponent(codigo)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                const exacto = data.find(p => p.codigo_barras === codigo.trim());
                if (exacto) {
                    if (exacto.stock === 0) {
                        this.resultados = data;
                        showToast('Sin stock — solo disponible como préstamo', 'warning', 3000);
                        // Mantener modal abierto para que elija préstamo
                        this.detenerEscanerTemporal();
                    } else {
                        this.agregarProducto(exacto);
                        this.cerrarScanner();
                        showToast(`${exacto.producto} - ${exacto.talla} agregado`, 'success', 2000);
                    }
                } else if (data.length > 0) {
                    this.resultados = data;
                    this.detenerEscanerTemporal();
                } else {
                    showToast('Producto no encontrado', 'warning');
                }
            } catch {
                showToast('Error al buscar producto', 'error');
            } finally {
                this.buscando = false;
            }
        },

        async buscarManual() {
            if (this.busqueda.length < 2) { this.resultados = []; return; }
            await this.buscarPorCodigo(this.busqueda);
        },

        detenerEscanerTemporal() {
            if (this.escaner && this.scannerActivo) {
                this.escaner.stop().catch(() => {});
                this.scannerActivo = false;
            }
        },

        reanudarScanner() {
            this.resultados = [];
            this.busqueda = '';
            this.iniciarScanner();
        },

        // ── Carrito ──────────────────────────────────────────────────────────────

        agregarProducto(producto) {
            if (producto.stock === 0) {
                showToast('Sin stock disponible', 'warning');
                return;
            }
            const existente = this.carrito.find(i => i.id === producto.id && !i.esDirecto);
            if (existente) {
                if (existente.cantidad < producto.stock) {
                    existente.cantidad++;
                } else {
                    showToast('Stock insuficiente', 'warning');
                }
            } else {
                this.carrito.push({
                    id: producto.id, producto: producto.producto, talla: producto.talla,
                    precio: producto.precio, precioBase: producto.precio,
                    costo: producto.costo || 0, cantidad: 1, stock: producto.stock,
                    esDirecto: false, costoDirecto: 0,
                });
            }
        },

        agregarDirecto(producto) {
            this.carrito.push({
                id: producto.id, producto: producto.producto, talla: producto.talla,
                precio: producto.precio, precioBase: producto.precio,
                costo: 0, cantidad: 1, stock: 0,
                esDirecto: true, costoDirecto: 0, localExternoId: '',
            });
            this.cerrarScanner();
            showToast(`${producto.producto} agregado como préstamo`, 'warning', 4000);
        },

        quitarProducto(index) {
            this.carrito.splice(index, 1);
        },

        cambiarCantidad(index, delta) {
            const item = this.carrito[index];
            const nueva = item.cantidad + delta;
            if (nueva <= 0) {
                this.carrito.splice(index, 1);
            } else if (item.esDirecto || nueva <= item.stock) {
                item.cantidad = nueva;
            } else {
                showToast('Stock insuficiente', 'warning');
            }
        },

        limpiarCarrito() {
            if (confirm('¿Limpiar el carrito?')) {
                this.carrito = [];
            }
        },

        prepararFormulario() {
            if (this.carrito.length === 0) {
                showToast('El carrito está vacío', 'warning');
                return false;
            }
            const sinLocal = this.carrito.find(i => i.esDirecto && !i.localExternoId);
            if (sinLocal) {
                showToast(`Selecciona el local externo de "${sinLocal.producto}"`, 'warning');
                return false;
            }
            const sinCosto = this.carrito.filter(i => i.esDirecto && !i.costoDirecto);
            if (sinCosto.length > 0) {
                showToast(`${sinCosto.length} préstamo(s) sin costo — cuadra después en Cuentas Externos`, 'info', 5000);
            }
            return true;
        },

        // ── Modal Cliente ────────────────────────────────────────────────────────

        abrirModalCliente() {
            this.nuevoCliente = { documento: '', nombre: '', telefono: '', correo: '', limite_credito: '' };
            this.erroresCliente = {};
            this.modalCliente = true;
            this.$nextTick(() => this.$refs.inputDocMovil?.focus());
        },
        cerrarModalCliente() { this.modalCliente = false; },

        async guardarCliente() {
            if (!this.nuevoCliente.documento || !this.nuevoCliente.nombre) {
                this.erroresCliente = {
                    documento: !this.nuevoCliente.documento ? 'El documento es obligatorio.' : '',
                    nombre:    !this.nuevoCliente.nombre    ? 'El nombre es obligatorio.'    : '',
                };
                return;
            }
            this.guardandoCliente = true;
            this.erroresCliente = {};
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const body = new FormData();
                body.append('_token', token);
                Object.entries(this.nuevoCliente).forEach(([k, v]) => { if (v) body.append(k, v); });
                const res = await fetch(this.urlClienteRapido, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });
                const data = await res.json();
                if (!res.ok) { this.erroresCliente = data.errors || {}; return; }
                this.clientesExtra.push(data);
                this.clienteId = String(data.id);
                showToast(`Cliente "${data.nombre}" creado y seleccionado.`, 'success');
                this.cerrarModalCliente();
            } catch {
                showToast('Error de conexión. Intenta de nuevo.', 'error');
            } finally {
                this.guardandoCliente = false;
            }
        },
    }));

    // ─── POS Móvil Préstamo (crédito forzado, documento opcional) ────────────────
    Alpine.data('posMovilPrestamo', (buscarUrl, urlClienteRapido, preloadBarcode, localesExternos) => ({
        buscarUrl: buscarUrl || (getBaseUrl() + '/api/productos/buscar'),
        urlClienteRapido: urlClienteRapido || '',
        preloadBarcode: preloadBarcode || '',
        localesExternos: localesExternos || [],

        carrito: [],
        clienteId: '',
        clientesExtra: [],
        metodoPago: 'Crédito',
        esCredito: true,
        diasCredito: 30,
        observaciones: '',

        escaner: null,
        scannerActivo: false,
        scannerModal: false,
        procesandoScan: false,
        busqueda: '',
        resultados: [],
        buscando: false,

        modalCliente: false,
        guardandoCliente: false,
        erroresCliente: {},
        nuevoCliente: { documento: '', nombre: '', telefono: '', correo: '', limite_credito: '' },

        get total() { return this.carrito.reduce((sum, i) => sum + i.cantidad * i.precio, 0); },

        async init() {
            if (this.preloadBarcode) {
                await this.buscarPorCodigo(this.preloadBarcode);
            }
        },

        async abrirScanner() {
            this.scannerModal = true; this.resultados = []; this.busqueda = '';
            await this.$nextTick(); this.iniciarScanner();
        },
        iniciarScanner() {
            if (this.scannerActivo) return;
            this.escaner = new window.Html5Qrcode('qr-prestamo-reader');
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
            this.buscando = true;
            try {
                const res = await fetch(`${this.buscarUrl}?q=${encodeURIComponent(codigo)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                const exacto = data.find(p => p.codigo_barras === codigo.trim());
                if (exacto) {
                    if (exacto.stock > 0) {
                        this.agregarProducto(exacto);
                        this.cerrarScanner();
                        showToast(`${exacto.producto} agregado`, 'success', 2000);
                    } else {
                        this.resultados = data.length > 0 ? data : [exacto];
                        this.detenerEscanerTemporal();
                    }
                } else if (data.length > 0) { this.resultados = data; this.detenerEscanerTemporal(); }
                else { showToast('Producto no encontrado', 'warning'); }
            } catch { showToast('Error al buscar producto', 'error'); }
            finally { this.buscando = false; }
        },
        async buscarManual() { if (this.busqueda.length < 2) { this.resultados = []; return; } await this.buscarPorCodigo(this.busqueda); },
        agregarProducto(producto) {
            if (producto.stock === 0) {
                showToast('Sin stock disponible', 'warning');
                return;
            }
            const existente = this.carrito.find(i => i.id === producto.id && !i.esDirecto);
            if (existente) {
                if (existente.cantidad < producto.stock) {
                    existente.cantidad++;
                } else {
                    showToast('Stock insuficiente', 'warning');
                }
            } else {
                this.carrito.push({ id: producto.id, producto: producto.producto, talla: producto.talla,
                    precio: producto.precio, precioBase: producto.precio, costo: producto.costo || 0,
                    cantidad: 1, stock: producto.stock, esDirecto: false, costoDirecto: 0, localExternoId: '' });
            }
        },
        agregarDirecto(producto) {
            this.carrito.push({ id: producto.id, producto: producto.producto, talla: producto.talla,
                precio: producto.precio, precioBase: producto.precio,
                costo: 0, cantidad: 1, stock: 0,
                esDirecto: true, costoDirecto: 0, localExternoId: '' });
            this.busqueda = ''; this.resultados = [];
            showToast(`${producto.producto} agregado — ingresa el costo y el local`, 'warning', 4000);
        },
        quitarProducto(index) { this.carrito.splice(index, 1); },
        cambiarCantidad(index, delta) {
            const item = this.carrito[index]; const nueva = item.cantidad + delta;
            if (nueva <= 0) {
                this.carrito.splice(index, 1);
            } else if (item.esDirecto || nueva <= item.stock) {
                item.cantidad = nueva;
            } else {
                showToast('Stock insuficiente', 'warning');
            }
        },
        limpiarCarrito() { if (confirm('¿Limpiar el carrito?')) this.carrito = []; },
        prepararFormulario() {
            if (!this.clienteId) { showToast('Debes seleccionar un cliente para el préstamo', 'warning'); return false; }
            if (this.carrito.length === 0) { showToast('El carrito está vacío', 'warning'); return false; }
            const sinLocal = this.carrito.find(i => i.esDirecto && !i.localExternoId);
            if (sinLocal) { showToast(`Selecciona el local externo de "${sinLocal.producto}"`, 'warning'); return false; }
            return true;
        },
        abrirModalCliente() {
            this.nuevoCliente = { documento: '', nombre: '', telefono: '', correo: '', limite_credito: '' };
            this.erroresCliente = {}; this.modalCliente = true;
            this.$nextTick(() => this.$refs.inputNombrePrestamo?.focus());
        },
        cerrarModalCliente() { this.modalCliente = false; },
        async guardarCliente() {
            if (!this.nuevoCliente.nombre) { this.erroresCliente = { nombre: 'El nombre es obligatorio.' }; return; }
            this.guardandoCliente = true; this.erroresCliente = {};
            try {
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const body = new FormData();
                body.append('_token', token);
                Object.entries(this.nuevoCliente).forEach(([k, v]) => { if (v) body.append(k, v); });
                const res = await fetch(this.urlClienteRapido, {
                    method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body,
                });
                const data = await res.json();
                if (!res.ok) { this.erroresCliente = data.errors || {}; return; }
                this.clientesExtra.push(data); this.clienteId = String(data.id);
                showToast(`Cliente "${data.nombre}" creado y seleccionado.`, 'success');
                this.cerrarModalCliente();
            } catch { showToast('Error de conexión. Intenta de nuevo.', 'error'); }
            finally { this.guardandoCliente = false; }
        },
    }));

    // ─── Traslado Móvil (escáner + bodegas) ──────────────────────────────────────
    Alpine.data('trasladoMovil', (buscarUrl, bodegas, bodegaActivaId, preloadBarcode) => ({
        buscarUrl: buscarUrl || '',
        bodegas: bodegas || [],
        idBodegaOrigen: bodegaActivaId ? String(bodegaActivaId) : '',
        idBodegaDestino: '',
        preloadBarcode: preloadBarcode || '',
        items: [],
        observacion: '',

        escaner: null,
        scannerActivo: false,
        scannerModal: false,
        procesandoScan: false,
        busqueda: '',
        resultados: [],
        buscando: false,

        get totalItems() { return this.items.reduce((sum, i) => sum + i.cantidad, 0); },

        async init() {
            if (this.preloadBarcode && this.idBodegaOrigen) {
                await this.buscarPorCodigo(this.preloadBarcode);
            }
        },

        cambiarOrigen() { this.items = []; this.resultados = []; },

        async abrirScanner() {
            if (!this.idBodegaOrigen) { showToast('Selecciona primero la bodega de origen', 'warning'); return; }
            this.scannerModal = true; this.resultados = []; this.busqueda = '';
            await this.$nextTick(); this.iniciarScanner();
        },
        iniciarScanner() {
            if (this.scannerActivo) return;
            this.escaner = new window.Html5Qrcode('qr-traslado-reader');
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
            if (!codigo) return;
            this.buscando = true;
            try {
                const url = `${this.buscarUrl}?q=${encodeURIComponent(codigo)}&id_bodega=${encodeURIComponent(this.idBodegaOrigen)}`;
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                if (!res.ok) throw new Error();
                const data = await res.json();
                const exacto = data.find(p => p.codigo_barras === codigo.trim());
                if (exacto) { this.agregarItem(exacto); this.cerrarScanner(); showToast(`${exacto.nombre} agregado`, 'success', 2000); }
                else if (data.length > 0) { this.resultados = data; this.detenerEscanerTemporal(); }
                else { showToast('Producto no encontrado en esta bodega', 'warning'); }
            } catch { showToast('Error al buscar producto', 'error'); }
            finally { this.buscando = false; }
        },
        async buscarManual() { if (this.busqueda.length < 2) { this.resultados = []; return; } await this.buscarPorCodigo(this.busqueda); },
        agregarItem(producto) {
            const existente = this.items.find(i => i.id === producto.id);
            if (existente) {
                existente.cantidad < producto.stock_bodega ? existente.cantidad++ : showToast('Sin más stock en esta bodega', 'warning');
            } else {
                this.items.push({ id: producto.id, nombre: producto.nombre, talla: producto.talla,
                    codigo_barras: producto.codigo_barras, stock_bodega: producto.stock_bodega, cantidad: 1 });
            }
        },
        quitarItem(index) { this.items.splice(index, 1); },
        cambiarCantidad(index, delta) {
            const item = this.items[index]; const nueva = item.cantidad + delta;
            if (nueva <= 0) this.items.splice(index, 1);
            else if (nueva <= item.stock_bodega) item.cantidad = nueva;
            else showToast('Sin más stock disponible', 'warning');
        },
        prepararFormulario() {
            if (!this.idBodegaOrigen) { showToast('Selecciona la bodega de origen', 'warning'); return false; }
            if (!this.idBodegaDestino) { showToast('Selecciona la bodega de destino', 'warning'); return false; }
            if (this.idBodegaOrigen === this.idBodegaDestino) { showToast('Origen y destino no pueden ser la misma bodega', 'warning'); return false; }
            if (this.items.length === 0) { showToast('Agrega al menos un producto para trasladar', 'warning'); return false; }
            return true;
        },
    }));

    // ─── Consulta de Tallas (POS Móvil) ────────────────────────────────────────
    Alpine.data('consultaMovil', (buscarUrl, bodegaActivaId, ventaUrl, prestamoUrl, trasladoUrl) => ({
        buscarUrl,
        bodegaActivaId,
        ventaUrl,
        prestamoUrl,
        trasladoUrl,

        escaner: null,
        scannerActivo: false,
        procesandoScan: false,
        buscando: false,
        busqueda: '',

        resultado: null,   // { producto: {id, nombre}, tallas: [{id, talla, codigo_barras, stock, precio, otras_bodegas}] }
        sinResultado: false,

        modalTipo: false,
        modalOtraBodega: false,
        tallaSeleccionada: null,

        init() {
            this.$nextTick(() => this.iniciarScanner());
        },

        iniciarScanner() {
            if (this.scannerActivo) return;
            this.sinResultado = false;
            this.escaner = new window.Html5Qrcode('qr-consulta-reader');
            this.escaner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 260, height: 120 } },
                async (codigo) => {
                    if (this.procesandoScan) return;
                    this.procesandoScan = true;
                    await this.buscar(codigo);
                    setTimeout(() => { this.procesandoScan = false; }, 2000);
                },
                () => {}
            ).then(() => {
                this.scannerActivo = true;
            }).catch(() => {
                showToast('No se pudo acceder a la cámara', 'error');
            });
        },

        detenerScanner() {
            if (this.escaner && this.scannerActivo) {
                this.escaner.stop().catch(() => {});
                this.escaner = null;
                this.scannerActivo = false;
            }
        },

        async buscarManual() {
            if (this.busqueda.length < 2) return;
            this.detenerScanner();
            await this.buscar(this.busqueda);
        },

        async buscar(codigo) {
            this.buscando = true;
            this.sinResultado = false;
            try {
                const url = `${this.buscarUrl}?q=${encodeURIComponent(codigo)}&id_bodega=${this.bodegaActivaId || ''}`;
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.encontrado) {
                    this.resultado = data;
                    this.detenerScanner();
                } else {
                    this.sinResultado = true;
                    showToast('Producto no encontrado', 'warning');
                }
            } catch {
                showToast('Error de conexión', 'error');
            }
            this.buscando = false;
        },

        resetear() {
            this.resultado = null;
            this.sinResultado = false;
            this.busqueda = '';
            this.$nextTick(() => this.iniciarScanner());
        },

        irAVender(talla) {
            this.tallaSeleccionada = talla;
            this.modalTipo = true;
        },

        abrirOpcionesSinStock(talla) {
            this.tallaSeleccionada = talla;
            this.modalOtraBodega = true;
        },

        irContado() {
            this.detenerScanner();
            window.location.href = this.ventaUrl + '?item=' + this.tallaSeleccionada.id;
        },

        irCredito() {
            this.detenerScanner();
            window.location.href = this.prestamoUrl + '?item=' + this.tallaSeleccionada.id;
        },

        irTraslado() {
            this.detenerScanner();
            window.location.href = this.trasladoUrl + '?item=' + this.tallaSeleccionada.id;
        },
    }));

    // Sidebar colapso + tooltips
    Alpine.data('sidebar', () => ({
        abierto: localStorage.getItem('sidebarAbierto') !== 'false',
        tooltip: { visible: false, text: '', top: 0, left: 0 },

        toggle() {
            this.abierto = !this.abierto;
            localStorage.setItem('sidebarAbierto', this.abierto);
            if (this.abierto) this.tooltip.visible = false;
        },

        mostrarTooltip(event) {
            if (this.abierto) return;
            const el = event.currentTarget;
            const text = el.dataset.tooltip;
            if (!text) return;
            const rect = el.getBoundingClientRect();
            this.tooltip = {
                visible: true,
                text,
                top: Math.round(rect.top + rect.height / 2),
                left: Math.round(rect.right + 10),
            };
        },

        ocultarTooltip() {
            this.tooltip.visible = false;
        },
    }));

    // Input numérico con formato de miles (separador de miles COP)
    Alpine.data('moneyInput', (initial = '') => ({
        raw: '',

        init() {
            const n = parseInt(String(initial ?? '').replace(/\D/g, ''), 10);
            this.raw = isNaN(n) ? '' : String(n);
            this.$nextTick(() => {
                if (this.$refs.display) {
                    this.$refs.display.value = this.raw ? this.fmt(parseInt(this.raw)) : '';
                }
            });
        },

        fmt(n) {
            return new Intl.NumberFormat('es-CO').format(n);
        },

        update(e) {
            const digits = e.target.value.replace(/\D/g, '');
            const n = parseInt(digits, 10);
            if (!digits || isNaN(n)) {
                this.raw = '';
                e.target.value = '';
            } else {
                this.raw = String(n);
                e.target.value = this.fmt(n);
            }
        },
    }));

    // Cuadre de caja - desglose de billetes
    Alpine.data('desgloseBilletes', () => ({
        denominaciones: [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 200, 100, 50],
        cantidades: {},
        transferencias: 0,

        get total() {
            return this.denominaciones.reduce((sum, denom) => {
                return sum + (denom * (parseInt(this.cantidades[denom]) || 0));
            }, 0);
        },

        init() {
            this.denominaciones.forEach(d => { this.cantidades[d] = 0; });
        },
    }));
});

Alpine.start();

// ─── Auto-dismiss Flash Messages ─────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const flashes = document.querySelectorAll('[data-flash]');
    flashes.forEach(el => {
        const type = el.dataset.flash;
        const message = el.dataset.message;
        if (message) {
            showToast(message, type);
        }
        el.remove();
    });
});
