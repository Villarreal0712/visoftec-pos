@extends('layouts.app')

@section('title', 'Editar Producto')
@section('page-title', 'Editar Producto')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Editando: {{ $producto->nombre }}</h3>
            <a href="{{ route('productos.index') }}" class="btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            @if($errors->any())
            <div class="alert-error mb-5">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            </div>
            @endif

            <form method="POST" action="{{ route('productos.update', $producto) }}" enctype="multipart/form-data" class="space-y-6"
                  x-data="productoEditForm()">
                @csrf @method('PUT')

                {{-- Info básica --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">Información General</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group col-span-2">
                            <label class="form-label">Nombre del Producto *</label>
                            <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Categoría</label>
                            <div class="flex gap-2">
                                <select name="id_categoria" id="selectCategoriaEdit" class="form-select flex-1">
                                    <option value="">Sin categoría</option>
                                    @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('id_categoria', $producto->id_categoria) == $cat->id ? 'selected' : '' }}>
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
                                    <div id="errorNuevaCatEdit" class="hidden text-sm text-red-500"></div>
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
                            <input type="number" name="stock_minimo" value="{{ old('stock_minimo', $producto->stock_minimo) }}" class="form-input" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Imagen del Producto</label>
                            @if($producto->imagen)
                            <div class="mb-2">
                                <img src="{{ asset('storage/'.$producto->imagen) }}" class="w-20 h-20 object-cover rounded-lg border">
                                <label class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                    <input type="checkbox" name="eliminar_imagen" value="1"> Eliminar imagen actual
                                </label>
                            </div>
                            @endif
                            <input type="file" name="imagen" class="form-input" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="form-group col-span-2">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" rows="2" class="form-textarea">{{ old('descripcion', $producto->descripcion) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Variantes existentes --}}
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">Variantes / Tallas Actuales</h4>
                    <div class="space-y-3">
                        @foreach($producto->detalles as $detalle)
                        <div class="grid grid-cols-3 gap-3 items-start p-3 bg-blue-50 rounded-xl border border-blue-200">
                            <input type="hidden" name="detalles_existentes[{{ $detalle->id }}][id]" value="{{ $detalle->id }}">
                            <div class="form-group">
                                <label class="form-label text-xs">Talla/Variante</label>
                                <input type="text" name="detalles_existentes[{{ $detalle->id }}][talla]" value="{{ old('detalles_existentes.'.$detalle->id.'.talla', $detalle->talla) }}" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label text-xs">Código de Barras</label>
                                <input type="text" value="{{ $detalle->codigo_barras }}" class="form-input font-mono bg-gray-100" readonly>
                                <input type="hidden" name="detalles_existentes[{{ $detalle->id }}][codigo_barras]" value="{{ $detalle->codigo_barras }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label text-xs">Stock actual</label>
                                <input type="text" value="{{ $detalle->lotes->sum('cantidad_disponible') }} unidades" class="form-input bg-gray-100" readonly>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Nuevas variantes --}}
                    <div x-data="{ productoId: {{ $producto->id }}, baseCount: {{ $producto->detalles->count() }}, nuevas: [] }" class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm text-gray-500">Agregar nuevas variantes</p>
                            <button type="button" @click="nuevas.push({ talla: '' })" class="btn-secondary btn-sm">+ Nueva Variante</button>
                        </div>
                        <template x-for="(item, i) in nuevas" :key="i">
                            <div class="flex gap-3 items-end p-3 bg-emerald-50 rounded-xl border border-emerald-200 mb-2">
                                <div class="form-group flex-1">
                                    <label class="form-label text-xs">Talla/Variante</label>
                                    <input type="text" :name="'detalles_nuevos[' + i + '][talla]'" x-model="item.talla" class="form-input">
                                </div>
                                <div class="form-group flex-1">
                                    <label class="form-label text-xs">Código de barras</label>
                                    <input type="text" class="form-input font-mono bg-gray-50 cursor-not-allowed text-gray-400"
                                        :placeholder="String(productoId) + String(baseCount + i + 1).padStart(3, '0')"
                                        readonly tabindex="-1">
                                </div>
                                <button type="button" @click="nuevas.splice(i, 1)" class="mb-0.5 p-2 text-red-400 hover:text-red-600 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex gap-3 pt-2 border-t border-gray-100">
                    <button type="submit" class="btn-primary flex-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Guardar Cambios
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
function productoEditForm() {
    return {
        modalCategoria: false,
        nuevaCat: { nombre: '', descripcion: '' },

        async guardarCategoria() {
            const errEl = document.getElementById('errorNuevaCatEdit');
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

                const select = document.getElementById('selectCategoriaEdit');
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
