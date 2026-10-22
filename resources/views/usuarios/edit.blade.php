@extends('layouts.app')

@section('title', 'Editar Usuario')
@section('page-title', 'Editar Usuario')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Editando: {{ $usuario->nombre }}</h3>
            <a href="{{ route('usuarios.index') }}" class="btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            @if($errors->any())
            <div class="alert-error mb-5">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            </div>
            @endif

            <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="space-y-4"
                  x-data="{
                    sucursalId: '{{ old('id_sucursal', $usuario->id_sucursal ?? 1) }}',
                    bodegaId: '{{ old('id_bodega', $usuario->id_bodega ?? 1) }}',
                    todasBodegas: {{ $bodegas->map(fn($b) => ['id' => $b->id, 'nombre' => $b->nombre, 'id_sucursal' => $b->id_sucursal])->values()->toJson() }},
                    // Bodegas de la sucursal elegida + bodegas de acopio (sin sucursal, compartidas por todas)
                    get bodegas() { return this.todasBodegas.filter(b => b.id_sucursal == this.sucursalId || b.id_sucursal === null); },
                    get bodegaEsAcopio() { return this.todasBodegas.some(b => b.id == this.bodegaId && b.id_sucursal === null); }
                  }">
                @csrf @method('PUT')

                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" class="form-input" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Usuario *</label>
                        <input type="text" name="usuario" value="{{ old('usuario', $usuario->usuario) }}" class="form-input font-mono" required>
                        @error('usuario')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" value="{{ old('email', $usuario->email) }}" class="form-input">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Sucursal</label>
                        <select name="id_sucursal" class="form-input" x-model="sucursalId">
                            @foreach($sucursales as $suc)
                            <option value="{{ $suc->id }}" {{ old('id_sucursal', $usuario->id_sucursal) == $suc->id ? 'selected' : '' }}>
                                {{ $suc->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bodega</label>
                        <select name="id_bodega" class="form-input" x-model="bodegaId">
                            <template x-for="b in bodegas" :key="b.id">
                                <option :value="b.id" x-text="b.nombre + (b.id_sucursal === null ? ' (Acopio)' : '')"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3" x-show="bodegaEsAcopio" x-cloak>
                    <p class="text-sm font-semibold text-amber-800">Bodega de acopio seleccionada</p>
                    <p class="text-xs text-amber-600 mt-0.5">Este usuario no podrá vender ítems propios desde esta bodega (solo ventas de préstamo/Directo).</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Rol *</label>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($roles as $rol)
                        <label class="relative cursor-pointer">
                            <input type="radio" name="rol" value="{{ $rol->name }}" class="sr-only peer"
                                   {{ $usuario->hasRole($rol->name) ? 'checked' : '' }}>
                            <div class="border-2 rounded-xl p-3 text-center transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 border-gray-200 hover:border-gray-300">
                                <p class="text-sm font-medium">{{ $rol->name }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" {{ old('activo', $usuario->activo) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-500">
                        <span class="text-sm text-gray-700">Usuario activo</span>
                    </label>
                </div>

                {{-- Cambiar contraseña (opcional) --}}
                <div class="border-t pt-4" x-data="{ cambiar: @js($errors->has('password')) }">
                    <label class="flex items-center gap-2 cursor-pointer mb-3">
                        <input type="checkbox" x-model="cambiar" class="rounded border-gray-300 text-amber-500">
                        <span class="text-sm font-medium text-gray-700">Cambiar contraseña</span>
                    </label>
                    {{-- :disabled → si se desmarca la casilla, los campos no se envían y la contraseña no cambia --}}
                    <div x-show="cambiar" x-cloak class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="password" class="form-input" minlength="8" placeholder="Mín. 8 caracteres"
                                   :disabled="!cambiar" :required="cambiar">
                            @error('password')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirmar</label>
                            <input type="password" name="password_confirmation" class="form-input" placeholder="Repetir"
                                   :disabled="!cambiar" :required="cambiar">
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1">Guardar Cambios</button>
                    <a href="{{ route('usuarios.index') }}" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
