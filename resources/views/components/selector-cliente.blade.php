{{--
    Buscador de clientes para los POS. Reemplaza al <select> nativo, que solo salta por la
    primera letra: aquí se filtra por varias palabras (en cualquier orden, sin importar
    tildes ni mayúsculas) contra nombre y documento.

    Debe ir dentro de un componente Alpine que tenga `clienteId` y `clientesExtra`
    (pos(), posMovil(), ...): lee y escribe esas propiedades del componente padre.
--}}
@props([
    'clientes',
    'placeholder' => '— Sin cliente —',
    'movil' => false,
    'requerido' => false,
])

@php
    $lista = $clientes->map(fn($c) => ['id' => (string) $c->id, 'nombre' => $c->nombre, 'documento' => (string) $c->documento])->values();
@endphp

<div class="relative flex-1 min-w-0"
     x-data="{
        clientesBase: @js($lista),
        abierto: false,
        q: '',
        activo: 0,
        limite: 50,
        norm(s) { return String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); },
        get todos() {
            return this.clientesBase.concat((this.clientesExtra || []).map(c => ({ id: String(c.id), nombre: c.nombre, documento: String(c.documento ?? '') })));
        },
        get seleccionado() { return this.todos.find(c => c.id === String(this.clienteId)) || null; },
        get coincidencias() {
            const palabras = this.norm(this.q).split(/\s+/).filter(Boolean);
            if (!palabras.length) return this.todos;
            return this.todos.filter(c => {
                const texto = this.norm(c.nombre + ' ' + c.documento);
                return palabras.every(p => texto.includes(p));
            });
        },
        get visibles() { return this.coincidencias.slice(0, this.limite); },
        abrir() { this.q = ''; this.activo = 0; this.abierto = true; },
        cerrar() { this.abierto = false; this.q = ''; },
        elegir(c) { this.clienteId = c ? c.id : ''; this.cerrar(); this.$refs.entrada.blur(); },
        mover(d) {
            if (!this.abierto) return this.abrir();
            const n = this.visibles.length;
            if (!n) return;
            this.activo = (this.activo + d + n) % n;
            this.$nextTick(() => this.$refs.lista?.querySelector('[data-activo=true]')?.scrollIntoView({ block: 'nearest' }));
        },
     }"
     @click.outside="cerrar()">

    <input type="hidden" name="id_cliente" :value="clienteId">

    <div class="relative">
        <input type="text" x-ref="entrada" autocomplete="off"
            :value="abierto ? q : (seleccionado ? seleccionado.nombre + (seleccionado.documento ? ' (' + seleccionado.documento + ')' : '') : '')"
            @input="q = $event.target.value; activo = 0; abierto = true"
            @focus="abrir()"
            @keydown.arrow-down.prevent="mover(1)"
            @keydown.arrow-up.prevent="mover(-1)"
            @keydown.enter.prevent="if (!abierto) abrir(); else if (visibles[activo]) elegir(visibles[activo])"
            @keydown.escape.prevent="cerrar(); $refs.entrada.blur()"
            @keydown.tab="cerrar()"
            :placeholder="abierto ? 'Escribe nombre o documento…' : @js($placeholder)"
            @class([
                'w-full bg-white focus:ring-2 pr-8 border',
                'text-sm rounded-xl px-3 py-2' => $movil,
                'text-xs rounded-lg px-2 py-1.5' => !$movil,
                'focus:ring-amber-300' => $requerido,
                'focus:ring-blue-300' => !$requerido,
            ])
            @if($requerido)
                :class="!clienteId ? 'border-red-300' : 'border-gray-200'"
            @else
                :class="'border-gray-200'"
            @endif
        >
        {{-- Quitar cliente --}}
        <button type="button" x-show="clienteId && !abierto" x-cloak @click="elegir(null)"
            title="Quitar cliente"
            class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-400 hover:text-gray-600">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div x-show="abierto" x-cloak x-ref="lista"
        class="absolute z-40 mt-1 w-full max-h-64 bg-white border border-gray-200 rounded-xl shadow-lg overflow-y-auto">
        @unless($requerido)
        <button type="button" @mousedown.prevent="elegir(null)"
            class="w-full text-left px-3 py-2 text-gray-500 hover:bg-gray-50 border-b border-gray-100 {{ $movil ? 'text-sm' : 'text-xs' }}">
            {{ $placeholder }}
        </button>
        @endunless
        <template x-for="(c, i) in visibles" :key="c.id">
            <button type="button" @mousedown.prevent="elegir(c)" @mouseenter="activo = i"
                :data-activo="i === activo"
                :class="i === activo ? 'bg-blue-50' : ''"
                class="w-full text-left px-3 py-2 flex justify-between gap-2 {{ $movil ? 'text-sm' : 'text-xs' }}">
                <span class="truncate text-gray-800" x-text="c.nombre"></span>
                <span class="flex-shrink-0 text-gray-400" x-text="c.documento"></span>
            </button>
        </template>
        <p x-show="coincidencias.length === 0" class="px-3 py-3 text-gray-400 text-center {{ $movil ? 'text-sm' : 'text-xs' }}">
            Ningún cliente coincide con "<span x-text="q"></span>"
        </p>
        <p x-show="coincidencias.length > limite" class="px-3 py-2 text-gray-400 text-center border-t border-gray-100 {{ $movil ? 'text-sm' : 'text-xs' }}">
            <span x-text="coincidencias.length - limite"></span> más… sigue escribiendo para filtrar
        </p>
    </div>
</div>
