@extends('layouts.app')

@section('title', 'Bodegas')
@section('page-title', 'Bodegas')

@section('breadcrumbs')
    <li class="flex items-center gap-2"><span>/</span><a href="{{ route('sucursales.index') }}" class="hover:text-blue-600">Sucursales</a></li>
    <li class="flex items-center gap-2"><span>/</span><span class="text-gray-800 font-medium">Bodegas</span></li>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-5" x-data="{ modalEditar: false, bodega: {} }">

    <div class="grid md:grid-cols-2 gap-5">

        {{-- Formulario crear --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Nueva bodega</h3>
            </div>
            <form method="POST" action="{{ route('bodegas.store') }}">
                @csrf
                <div class="card-body space-y-3">

                    <div class="form-group">
                        <label class="form-label">Sucursal <span class="text-gray-400 font-normal">(opcional)</span></label>
                        <select name="id_sucursal" class="form-input @error('id_sucursal') border-red-400 @enderror">
                            <option value="">— Sin sucursal (bodega de acopio) —</option>
                            @foreach($sucursales as $suc)
                            <option value="{{ $suc->id }}" {{ old('id_sucursal') == $suc->id ? 'selected' : '' }}>
                                {{ $suc->nombre }}
                            </option>
                            @endforeach
                        </select>
                        @error('id_sucursal')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" required
                            value="{{ old('nombre') }}"
                            class="form-input @error('nombre') border-red-400 @enderror"
                            placeholder="Ej: Bodega Norte">
                        @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <input type="text" name="descripcion"
                            value="{{ old('descripcion') }}"
                            class="form-input" placeholder="Ubicación o descripción">
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn-primary w-full">Crear bodega</button>
                </div>
            </form>
        </div>

        {{-- Lista agrupada por sucursal --}}
        <div class="space-y-4">
            @forelse($sucursales as $suc)
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ $suc->nombre }}</h3>
                        <p class="text-xs text-gray-400">{{ $suc->bodegas->count() }} bodega(s)</p>
                    </div>
                </div>
                @if($suc->bodegas->isEmpty())
                <div class="card-body text-center py-4 text-gray-400 text-sm">
                    Sin bodegas.
                </div>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($suc->bodegas as $bod)
                    <div class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-gray-50/60 transition-colors">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-900 text-sm truncate">{{ $bod->nombre }}</p>
                                @if($suc->id_bodega_default === $bod->id)
                                <span class="text-xs bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded flex-shrink-0">Principal</span>
                                @endif
                                @if(!$bod->activo)
                                <span class="text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded flex-shrink-0">Inactiva</span>
                                @endif
                            </div>
                            @if($bod->descripcion)
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $bod->descripcion }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button type="button"
                                @click="bodega = {{ $bod->toJson() }}; modalEditar = true"
                                class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"
                                title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('bodegas.toggle', $bod) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="p-1.5 rounded-lg transition-colors {{ $bod->activo ? 'text-gray-400 hover:text-amber-500 hover:bg-amber-50' : 'text-gray-400 hover:text-emerald-500 hover:bg-emerald-50' }}"
                                    title="{{ $bod->activo ? 'Desactivar' : 'Activar' }}">
                                    @if($bod->activo)
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
            @empty
            <div class="card">
                <div class="card-body text-center py-8 text-gray-400 text-sm">
                    Primero crea una sucursal.
                </div>
            </div>
            @endforelse

            {{-- Bodegas de acopio (sin sucursal) --}}
            @if($bodegasAcopio->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Bodegas de acopio</h3>
                        <p class="text-xs text-gray-400">Sin sucursal asignada · {{ $bodegasAcopio->count() }} bodega(s)</p>
                    </div>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($bodegasAcopio as $bod)
                    <div class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-gray-50/60 transition-colors">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-900 text-sm truncate">{{ $bod->nombre }}</p>
                                <span class="text-xs bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded flex-shrink-0">Acopio</span>
                                @if(!$bod->activo)
                                <span class="text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded flex-shrink-0">Inactiva</span>
                                @endif
                            </div>
                            @if($bod->descripcion)
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $bod->descripcion }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button type="button"
                                @click="bodega = {{ $bod->toJson() }}; modalEditar = true"
                                class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors"
                                title="Editar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('bodegas.toggle', $bod) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="p-1.5 rounded-lg transition-colors {{ $bod->activo ? 'text-gray-400 hover:text-amber-500 hover:bg-amber-50' : 'text-gray-400 hover:text-emerald-500 hover:bg-emerald-50' }}"
                                    title="{{ $bod->activo ? 'Desactivar' : 'Activar' }}">
                                    @if($bod->activo)
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
            <h2 class="text-base font-bold text-gray-900">Editar bodega</h2>
            <button @click="modalEditar = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" :action="`{{ url('bodegas') }}/${bodega.id}`">
            @csrf @method('PUT')
            <div class="px-6 py-5 space-y-3">

                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" required :value="bodega.nombre" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" :value="bodega.descripcion" class="form-input">
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
