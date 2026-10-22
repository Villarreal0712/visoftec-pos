@extends('layouts.app')

@section('title', 'Nuevo Producto')
@section('page-title', 'Crear Producto')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuevo Producto</h3>
            <a href="{{ route('productos.index') }}" class="btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            @if($errors->any())
            <div class="alert-error mb-5">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            </div>
            @endif

            <form method="POST" action="{{ route('productos.store') }}" enctype="multipart/form-data" class="space-y-6"
                  x-data="productoForm({{ $nextProductoId }})">
                @csrf

                {{-- Info básica --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">Información General</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group col-span-2">
                            <label class="form-label">Nombre del Producto *</label>
                            <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-input" required placeholder="Ej: Camiseta Básica Algodón">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Categoría</label>
                            <div class="flex gap-2">
                                <select name="id_categoria" id="selectCategoria" class="form-select flex-1">
                                    <option value="">Sin categoría</option>
                                    @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}" {{ old('id_categoria') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                                @can('productos.editar')
                                <button type="button" @click="modalCategoria = true"
                                    class="btn-secondary flex-shrink-0" title="Nueva categoría">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </button>
                                @endcan
                            </div>
                        </div>

                        {{-- Modal nueva categoría inline --}}
                        @can('productos.editar')
                        <div x-show="modalCategoria" x-cloak x-transition class="modal-overlay" @click.self="modalCategoria = false">
                            <div class="modal-container" @click.stop>
                                <div class="modal-header">
                                    <h3 class="text-base font-bold">Nueva Categoría</h3>
                                    <button type="button" @click="modalCategoria = false" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div class="modal-body space-y-4">
                                    <div class="form-group">
                                        <label class="form-label">Nombre *</label>
                                        <input type="text" x-model="nuevaCat.nombre" class="form-input" placeholder="Ej: Ropa, Calzado..." @keydown.enter.prevent="">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Descripción</label>
                                        <input type="text" x-model="nuevaCat.descripcion" class="form-input" placeholder="Opcional">
                                    </div>
                                    <div id="errorNuevaCat" class="hidden text-sm text-red-500"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" @click="modalCategoria = false" class="btn-secondary">Cancelar</button>
                                    <button type="button" @click="guardarCategoria()" class="btn-primary">Crear y Seleccionar</button>
                                </div>
                            </div>
                        </div>
                        @endcan
                        <div class="form-group">
                            <label class="form-label">Stock Mínimo</label>
                            <input type="number" name="stock_minimo" value="{{ old('stock_minimo', 5) }}" class="form-input" min="0">
                            <p class="text-xs text-gray-400 mt-1">Alerta cuando el stock baje de este valor</p>
                        </div>
                        <div class="form-group col-span-2">
                            <label class="form-label">Imagen del Producto</label>
                            <input type="file" name="imagen" class="form-input" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="form-group col-span-2">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" rows="2" class="form-textarea" placeholder="Descripción del producto (opcional)">{{ old('descripcion') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Variantes/Tallas --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Variantes / Tallas (SKUs)</h4>
                        <button type="button" @click="detalles.push({ talla: '', codigo_barras: '' })" class="btn-secondary btn-sm">
                            + Agregar Variante
                        </button>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(detalle, index) in detalles" :key="index">
                            <div class="flex gap-3 items-end p-3 bg-gray-50 rounded-xl border border-gray-200">
                                <div class="form-group flex-1">
                                    <label class="form-label">Talla/Variante</label>
                                    <input type="text" :name="'detalles[' + index + '][talla]'" x-model="detalle.talla" class="form-input" placeholder="S, M, L, 38, Única...">
                                </div>
                                <div class="form-group flex-1">
                                    <label class="form-label">Código de barras</label>
                                    <input type="text" class="form-input font-mono bg-gray-50 cursor-not-allowed text-gray-400"
                                        :placeholder="String(productoId) + String(index + 1).padStart(3, '0')"
                                        readonly tabindex="-1">
                                </div>
                                <button type="button" @click="if(detalles.length > 1) detalles.splice(index, 1)" class="mb-0.5 p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0" :disabled="detalles.length === 1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">El código de barras y el precio de venta se asignan automáticamente al crear y al registrar la entrada de mercancía.</p>
                </div>

                <div class="flex gap-3 pt-2 border-t border-gray-100">
                    <button type="submit" class="btn-primary flex-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Crear Producto
                    </button>
                    <a href="{{ route('productos.index') }}" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function productoForm(nextId) {
    return {
        productoId: nextId,
        detalles: [{ talla: '' }],
        modalCategoria: false,
        nuevaCat: { nombre: '', descripcion: '' },

        async guardarCategoria() {
            const errEl = document.getElementById('errorNuevaCat');
            errEl.classList.add('hidden');
            errEl.textContent = '';

            if (!this.nuevaCat.nombre.trim()) {
                errEl.textContent = 'El nombre es obligatorio.';
                errEl.classList.remove('hidden');
                return;
            }

            try {
                const res = await fetch('{{ route('categorias.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(this.nuevaCat),
                });

                if (!res.ok) {
                    const data = await res.json();
                    const msg = data.errors?.nombre?.[0] ?? 'Error al crear la categoría.';
                    errEl.textContent = msg;
                    errEl.classList.remove('hidden');
                    return;
                }

                const cat = await res.json();

                // Agregar la opción al select y seleccionarla
                const select = document.getElementById('selectCategoria');
                const opt = new Option(cat.nombre, cat.id, true, true);
                select.appendChild(opt);
                select.value = cat.id;

                this.nuevaCat = { nombre: '', descripcion: '' };
                this.modalCategoria = false;
            } catch (e) {
                errEl.textContent = 'Error de conexión.';
                errEl.classList.remove('hidden');
            }
        },
    };
}
</script>
@endpush
