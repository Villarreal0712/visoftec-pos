@extends('layouts.app')

@php $editando = isset($rol); @endphp
@section('title', $editando ? 'Editar Rol: ' . $rol->name : 'Nuevo Rol')
@section('page-title', $editando ? 'Editar Rol: ' . $rol->name : 'Nuevo Rol')

@section('content')
<div class="max-w-4xl mx-auto space-y-5"
     x-data="matrizPermisos({{ json_encode($permisosActivos) }})">

    {{-- Botón volver --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('roles.index') }}" class="btn-secondary">← Volver</a>
        @if($editando && $rol->name === 'Administrador')
        <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-700 text-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            El Administrador tiene acceso total por defecto. Los permisos aquí son informativos.
        </div>
        @endif
    </div>

    <form method="POST"
          action="{{ $editando ? route('roles.update', $rol) : route('roles.store') }}"
          class="space-y-5">
        @csrf
        @if($editando) @method('PUT') @endif

        @if($errors->any())
        <div class="alert-error">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
        </div>
        @endif

        {{-- Nombre del rol --}}
        <div class="card">
            <div class="card-body">
                <div class="form-group mb-0 max-w-sm">
                    <label class="form-label">Nombre del rol *</label>
                    <input type="text" name="name"
                           value="{{ old('name', $rol->name ?? '') }}"
                           class="form-input font-semibold" required
                           placeholder="Ej: Supervisor, Cajero...">
                </div>
            </div>
        </div>

        {{-- Matriz de permisos --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Permisos</h3>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-400">
                        <span x-text="totalActivos"></span> seleccionados
                    </span>
                    <button type="button" @click="seleccionarTodos()"
                        class="text-xs font-medium text-blue-600 hover:underline">
                        Seleccionar todo
                    </button>
                    <button type="button" @click="deseleccionarTodos()"
                        class="text-xs font-medium text-gray-500 hover:underline">
                        Quitar todo
                    </button>
                </div>
            </div>

            <div class="card-body grid sm:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
                @foreach($permisosPorModulo as $modulo => $info)
                @php $nombresModulo = collect($info['permisos'])->pluck('name')->toArray(); @endphp

                <div class="border border-gray-200 rounded-xl overflow-hidden"
                     x-data="{ modulo: {{ json_encode($nombresModulo) }} }">

                    {{-- Cabecera del módulo --}}
                    <div class="flex items-center justify-between px-3 py-2.5 bg-gray-50 border-b border-gray-200">
                        <span class="text-sm font-semibold text-gray-700">{{ $info['label'] }}</span>
                        <label class="flex items-center gap-1.5 cursor-pointer select-none">
                            <input type="checkbox"
                                :checked="moduloCompleto(modulo)"
                                :indeterminate="moduloParcial(modulo)"
                                @change="toggleModulo(modulo, $event.target.checked)"
                                class="rounded border-gray-300 text-blue-500 cursor-pointer">
                            <span class="text-xs text-gray-400">Todo</span>
                        </label>
                    </div>

                    {{-- Permisos del módulo --}}
                    <div class="p-3 space-y-1.5">
                        @foreach($info['permisos'] as $permiso)
                        <label class="flex items-center gap-2.5 cursor-pointer group">
                            <input type="checkbox"
                                name="permisos[]"
                                value="{{ $permiso['name'] }}"
                                x-model="activos"
                                class="rounded border-gray-300 text-blue-500 cursor-pointer flex-shrink-0">
                            <span class="text-sm text-gray-600 group-hover:text-gray-900 transition-colors select-none">
                                {{ $permiso['accion'] }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Guardar --}}
        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary px-8">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ $editando ? 'Guardar cambios' : 'Crear rol' }}
            </button>
            <a href="{{ route('roles.index') }}" class="btn-secondary">Cancelar</a>
        </div>

    </form>
</div>

@push('scripts')
<script>
function matrizPermisos(iniciales) {
    return {
        activos: iniciales,

        get totalActivos() {
            return this.activos.length;
        },

        moduloCompleto(permisos) {
            return permisos.every(p => this.activos.includes(p));
        },

        moduloParcial(permisos) {
            const total = permisos.length;
            const activos = permisos.filter(p => this.activos.includes(p)).length;
            return activos > 0 && activos < total;
        },

        toggleModulo(permisos, activar) {
            if (activar) {
                permisos.forEach(p => {
                    if (!this.activos.includes(p)) this.activos.push(p);
                });
            } else {
                this.activos = this.activos.filter(p => !permisos.includes(p));
            }
        },

        seleccionarTodos() {
            const todos = [...document.querySelectorAll('input[name="permisos[]"]')]
                .map(el => el.value);
            this.activos = [...new Set(todos)];
        },

        deseleccionarTodos() {
            this.activos = [];
        },
    };
}
</script>
@endpush

@endsection
