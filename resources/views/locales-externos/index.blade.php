@extends('layouts.app')

@section('title', 'Locales Externos')
@section('page-title', 'Locales Externos')

@section('breadcrumbs')
    <li class="flex items-center gap-2"><span>/</span><a href="{{ route('prestamos.index') }}" class="hover:text-blue-600">Cuentas Externos</a></li>
    <li class="flex items-center gap-2"><span>/</span><span class="text-gray-800 font-medium">Locales Externos</span></li>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-5" x-data="{ modalEditar: false, local: {} }">

    <div class="grid md:grid-cols-2 gap-5">

        {{-- Formulario crear --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Nuevo local externo</h3>
            </div>
            <form method="POST" action="{{ route('locales-externos.store') }}">
                @csrf
                <div class="card-body space-y-3">

                    <div class="form-group">
                        <label class="form-label">Nombre del local *</label>
                        <input type="text" name="nombre_local" required
                            value="{{ old('nombre_local') }}"
                            class="form-input @error('nombre_local') border-red-400 @enderror"
                            placeholder="Ej: Distribuidora Pérez">
                        @error('nombre_local')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contacto</label>
                        <input type="text" name="contacto"
                            value="{{ old('contacto') }}"
                            class="form-input" placeholder="Nombre de la persona">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono"
                            value="{{ old('telefono') }}"
                            class="form-input" placeholder="3001234567">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion"
                            value="{{ old('direccion') }}"
                            class="form-input" placeholder="Opcional">
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn-primary w-full">
                        Crear local
                    </button>
                </div>
            </form>
        </div>

        {{-- Lista --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Locales registrados</h3>
                <span class="text-xs text-gray-400">{{ $locales->count() }} en total</span>
            </div>
            @if($locales->isEmpty())
            <div class="card-body text-center py-8 text-gray-400 text-sm">
                Aún no hay locales registrados.
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($locales as $local)
                <div class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-gray-50/60 transition-colors">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 text-sm truncate">{{ $local->nombre_local }}</p>
                            @if(!$local->activo)
                            <span class="text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded flex-shrink-0">Inactivo</span>
                            @endif
                        </div>
                        @if($local->contacto || $local->telefono)
                        <p class="text-xs text-gray-400 truncate mt-0.5">
                            {{ implode(' · ', array_filter([$local->contacto, $local->telefono])) }}
                        </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        {{-- Editar --}}
                        <button type="button"
                            @click="local = {{ $local->toJson() }}; modalEditar = true"
                            class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"
                            title="Editar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        {{-- Activar/Desactivar --}}
                        <form method="POST" action="{{ route('locales-externos.toggle', $local) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="p-1.5 rounded-lg transition-colors {{ $local->activo ? 'text-gray-400 hover:text-amber-500 hover:bg-amber-50' : 'text-gray-400 hover:text-emerald-500 hover:bg-emerald-50' }}"
                                title="{{ $local->activo ? 'Desactivar' : 'Activar' }}">
                                @if($local->activo)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @endif
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Modal editar --}}
<div x-show="modalEditar"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-end="opacity-0"
     style="display:none">

    <div class="absolute inset-0 bg-black/50" @click="modalEditar = false"></div>

    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-end="opacity-0 scale-95">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900">Editar local</h2>
            <button @click="modalEditar = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" :action="`{{ url('locales-externos') }}/${local.id}`">
            @csrf @method('PUT')
            <div class="px-6 py-5 space-y-3">

                <div class="form-group">
                    <label class="form-label">Nombre del local *</label>
                    <input type="text" name="nombre_local" required
                        :value="local.nombre_local"
                        class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Contacto</label>
                    <input type="text" name="contacto"
                        :value="local.contacto"
                        class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono"
                        :value="local.telefono"
                        class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion"
                        :value="local.direccion"
                        class="form-input">
                </div>

            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-between gap-3">
                <button type="button" @click="modalEditar = false" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary px-6">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

@endsection
