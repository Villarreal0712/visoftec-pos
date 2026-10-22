@extends('layouts.app')

@section('title', 'Categorías')
@section('page-title', 'Gestión de Categorías')

@section('content')
<div class="max-w-4xl mx-auto space-y-5" x-data="categoriasPage()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Categorías disponibles para clasificar productos</p>
        @can('productos.editar')
        <button @click="abrirCrear()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nueva Categoría
        </button>
        @endcan
    </div>

    @if(session('success'))
    <div class="alert-success">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="alert-error">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Tabla --}}
    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th class="text-center">Productos</th>
                        <th class="text-center">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categorias as $categoria)
                    <tr>
                        <td class="font-medium text-gray-900">{{ $categoria->nombre }}</td>
                        <td class="text-gray-500 text-sm">{{ $categoria->descripcion ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge badge-gray">{{ $categoria->productos_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($categoria->activo)
                                <span class="badge badge-success">Activa</span>
                            @else
                                <span class="badge badge-danger">Inactiva</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                @can('productos.editar')
                                <button @click="abrirEditar({{ $categoria->id }}, '{{ addslashes($categoria->nombre) }}', '{{ addslashes($categoria->descripcion ?? '') }}', {{ $categoria->activo ? 'true' : 'false' }})"
                                    class="btn btn-secondary btn-sm">
                                    Editar
                                </button>
                                <form method="POST" action="{{ route('categorias.destroy', $categoria) }}"
                                      onsubmit="return confirm('¿Eliminar la categoría «{{ $categoria->nombre }}»? Solo es posible si no tiene productos asignados.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-10 text-gray-400">
                            No hay categorías creadas aún
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal crear / editar --}}
    @can('productos.editar')
    <div x-show="modalAbierto" x-cloak x-transition class="modal-overlay" @click.self="cerrar()">
        <div class="modal-container" @click.stop>
            <div class="modal-header">
                <h3 class="text-base font-bold" x-text="editandoId ? 'Editar Categoría' : 'Nueva Categoría'"></h3>
                <button @click="cerrar()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Formulario crear --}}
            <form x-show="!editandoId" method="POST" action="{{ route('categorias.store') }}">
                @csrf
                <div class="modal-body space-y-4">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" x-model="form.nombre" class="form-input" required
                               placeholder="Ej: Ropa, Calzado, Accesorios...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <input type="text" name="descripcion" x-model="form.descripcion" class="form-input"
                               placeholder="Descripción breve (opcional)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="cerrar()" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Crear Categoría</button>
                </div>
            </form>

            {{-- Formulario editar --}}
            <form x-show="editandoId" method="POST" :action="`/categorias/${editandoId}`">
                @csrf @method('PUT')
                <div class="modal-body space-y-4">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" x-model="form.nombre" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <input type="text" name="descripcion" x-model="form.descripcion" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="activo" value="1" x-model="form.activo" class="rounded border-gray-300 text-blue-600">
                            <span class="text-sm text-gray-700">Categoría activa</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="cerrar()" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

</div>
@endsection

@push('scripts')
<script>
function categoriasPage() {
    return {
        modalAbierto: false,
        editandoId: null,
        form: { nombre: '', descripcion: '', activo: true },

        abrirCrear() {
            this.editandoId = null;
            this.form = { nombre: '', descripcion: '', activo: true };
            this.modalAbierto = true;
        },
        abrirEditar(id, nombre, descripcion, activo) {
            this.editandoId = id;
            this.form = { nombre, descripcion, activo };
            this.modalAbierto = true;
        },
        cerrar() {
            this.modalAbierto = false;
            this.editandoId = null;
        },
    };
}
</script>
@endpush
