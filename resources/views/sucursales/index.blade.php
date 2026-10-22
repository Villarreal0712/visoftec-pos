@extends('layouts.app')

@section('title', 'Sucursales')
@section('page-title', 'Sucursales')

@section('breadcrumbs')
    <li class="flex items-center gap-2"><span>/</span><span class="text-gray-800 font-medium">Sucursales</span></li>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-5" x-data="{
    modalEditar: false,
    suc: {},
    abrirModal(id, nombre, direccion, telefono, idBodega) {
        this.suc = { id, nombre, direccion, telefono, id_bodega_default: idBodega };
        this.modalEditar = true;
    }
}">

    <div class="grid md:grid-cols-2 gap-5">

        {{-- Formulario crear --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Nueva sucursal</h3>
            </div>
            <form method="POST" action="{{ route('sucursales.store') }}">
                @csrf
                <div class="card-body space-y-3">

                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" required
                            value="{{ old('nombre') }}"
                            class="form-input @error('nombre') border-red-400 @enderror"
                            placeholder="Ej: Sede Centro">
                        @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion"
                            value="{{ old('direccion') }}"
                            class="form-input" placeholder="Dirección de la sucursal">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono"
                            value="{{ old('telefono') }}"
                            class="form-input" placeholder="3001234567">
                    </div>

                    <p class="text-xs text-gray-400">Se creará automáticamente una bodega "Principal" para esta sucursal.</p>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn-primary w-full">Crear sucursal</button>
                </div>
            </form>
        </div>

        {{-- Lista --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sucursales registradas</h3>
                <span class="text-xs text-gray-400">{{ $sucursales->count() }} en total</span>
            </div>
            @if($sucursales->isNotEmpty())
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 grid grid-cols-2 gap-2">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Local</p>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Bodega asignada</p>
            </div>
            @endif
            @if($sucursales->isEmpty())
            <div class="card-body text-center py-8 text-gray-400 text-sm">
                Aún no hay sucursales registradas.
            </div>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($sucursales as $suc)
                <div class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-gray-50/60 transition-colors">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 text-sm truncate">{{ $suc->nombre }}</p>
                            @if(!$suc->activo)
                            <span class="text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded flex-shrink-0">Inactiva</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                            <svg class="w-3 h-3 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span class="font-medium text-blue-600">
                                {{ $suc->bodegaDefault?->nombre ?? '— sin bodega —' }}
                            </span>
                            @if($suc->direccion)
                            <span class="text-gray-300">·</span>
                            <span class="text-gray-400">{{ Str::limit($suc->direccion, 25) }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button type="button"
                            @click="abrirModal({{ $suc->id }}, @js($suc->nombre), @js($suc->direccion), @js($suc->telefono), {{ $suc->id_bodega_default ?? 'null' }})"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                            title="Editar sucursal">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Editar
                        </button>
                        <form method="POST" action="{{ route('sucursales.toggle', $suc) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="p-1.5 rounded-lg transition-colors {{ $suc->activo ? 'text-gray-400 hover:text-amber-500 hover:bg-amber-50' : 'text-gray-400 hover:text-emerald-500 hover:bg-emerald-50' }}"
                                title="{{ $suc->activo ? 'Desactivar' : 'Activar' }}">
                                @if($suc->activo)
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

    <div class="text-center">
        <a href="{{ route('bodegas.index') }}" class="text-sm text-blue-600 hover:underline">
            Gestionar bodegas →
        </a>
    </div>

{{-- Modal editar (dentro del x-data) --}}
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
            <div>
                <h2 class="text-base font-bold text-gray-900">Editar sucursal</h2>
                <p class="text-xs text-gray-400 mt-0.5">Aquí asignas qué bodega usa este local</p>
            </div>
            <button @click="modalEditar = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" :action="`{{ url('sucursales') }}/${suc.id}`">
            @csrf @method('PUT')
            <div class="px-6 py-5 space-y-3">

                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" required :value="suc.nombre" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" :value="suc.direccion" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" :value="suc.telefono" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Bodega por defecto</label>
                    <select name="id_bodega_default" class="form-input"
                            x-init="$el.value = suc.id_bodega_default ?? ''"
                            x-effect="$el.value = suc.id_bodega_default ?? ''">
                        <option value="">— Sin asignar —</option>
                        @foreach($bodegas as $b)
                        <option value="{{ $b->id }}">
                            {{ $b->nombre }}{{ $b->sucursal ? ' ('.$b->sucursal->nombre.')' : ' (sin local)' }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Puedes asignar cualquier bodega, aunque sea de otro local.</p>
                </div>

            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-between gap-3">
                <button type="button" @click="modalEditar = false" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary px-6">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>
</div>{{-- cierre x-data --}}

@endsection
