@extends('layouts.app')

@section('title', 'Editar Cliente')
@section('page-title', 'Editar Cliente')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Editando: {{ $cliente->nombre }}</h3>
            <a href="{{ route('clientes.index') }}" class="btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            @if($errors->any())
            <div class="alert-error mb-5">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            </div>
            @endif

            <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="space-y-4">
                @csrf @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Documento *</label>
                        <input type="text" name="documento" value="{{ old('documento', $cliente->documento) }}" class="form-input" required>
                        @error('documento')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo" value="{{ old('correo', $cliente->correo) }}" class="form-input">
                    </div>
                    <div class="form-group col-span-2">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" value="{{ old('direccion', $cliente->direccion) }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Límite de Crédito ($)</label>
                        <div class="relative" x-data="moneyInput({{ old('limite_credito', (int) $cliente->limite_credito) }})">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input type="text" inputmode="numeric" x-ref="display" @input="update($event)"
                                class="form-input pl-6" placeholder="0">
                            <input type="hidden" name="limite_credito" :value="raw">
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1">Guardar Cambios</button>
                    <a href="{{ route('clientes.show', $cliente) }}" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
