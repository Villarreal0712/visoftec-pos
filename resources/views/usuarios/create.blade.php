@extends('layouts.app')

@section('title', 'Nuevo Usuario')
@section('page-title', 'Crear Usuario')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuevo Usuario del Sistema</h3>
            <a href="{{ route('usuarios.index') }}" class="btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            @if($errors->any())
            <div class="alert-error mb-5">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            </div>
            @endif

            <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-4"
                  x-data="{
                    sucursalId: '{{ old('id_sucursal', 1) }}',
                    bodegaId: '{{ old('id_bodega', 1) }}',
                    todasBodegas: {{ $bodegas->map(fn($b) => ['id' => $b->id, 'nombre' => $b->nombre, 'id_sucursal' => $b->id_sucursal])->values()->toJson() }},
                    // Bodegas de la sucursal elegida + bodegas de acopio (sin sucursal, compartidas por todas)
                    get bodegas() { return this.todasBodegas.filter(b => b.id_sucursal == this.sucursalId || b.id_sucursal === null); },
                    get bodegaEsAcopio() { return this.todasBodegas.some(b => b.id == this.bodegaId && b.id_sucursal === null); }
                  }">
                @csrf

                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" class="form-input" required placeholder="María García">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Nombre de Usuario *</label>
                        <input type="text" name="usuario" value="{{ old('usuario') }}" class="form-input font-mono" required placeholder="maria.garcia">
                        @error('usuario')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="maria@tienda.com">
                    </div>
                    <div class="form-group" x-data="{ mostrar: false }">
                        <label class="form-label">Contraseña *</label>
                        <div class="relative">
                            <input :type="mostrar ? 'text' : 'password'" name="password" class="form-input pr-10" required minlength="8" placeholder="Mín. 8 caracteres">
                            <button type="button" @click="mostrar = !mostrar" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg x-show="!mostrar" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar Contraseña *</label>
                        <input type="password" name="password_confirmation" class="form-input" required placeholder="Repetir contraseña">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Sucursal</label>
                        <select name="id_sucursal" class="form-input" x-model="sucursalId">
                            @foreach($sucursales as $suc)
                            <option value="{{ $suc->id }}">{{ $suc->nombre }}</option>
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
                            <input type="radio" name="rol" value="{{ $rol->name }}" class="sr-only peer" {{ old('rol') === $rol->name ? 'checked' : '' }} required>
                            <div class="border-2 rounded-xl p-3 text-center transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 border-gray-200 hover:border-gray-300">
                                <p class="text-sm font-medium text-gray-900">{{ $rol->name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    @if($rol->name === 'Administrador') Acceso total
                                    @elseif($rol->name === 'Vendedor') Ventas y clientes
                                    @else Inventario
                                    @endif
                                </p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Crear Usuario
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
