@extends('layouts.app')

@section('title', 'Mi Perfil')
@section('page-title', 'Mi Perfil')

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- Datos personales --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Información Personal</h3></div>
        <div class="card-body">
            @if(session('status') === 'profile-updated')
            <div class="alert-success mb-4">Perfil actualizado correctamente.</div>
            @endif

            <form method="POST" action="{{ route('perfil.update') }}" class="space-y-4">
                @csrf @method('PUT')

                <div class="flex items-center gap-4 mb-5">
                    <div class="w-16 h-16 rounded-full bg-blue-500 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->nombre, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-bold text-gray-900">{{ auth()->user()->nombre }}</p>
                        <p class="text-sm text-gray-500">@foreach(auth()->user()->roles as $r) {{ $r->name }} @endforeach</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre Completo *</label>
                    <input type="text" name="nombre" value="{{ old('nombre', auth()->user()->nombre) }}" class="form-input" required>
                    @error('nombre')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Usuario *</label>
                        <input type="text" name="usuario" value="{{ old('usuario', auth()->user()->usuario) }}" class="form-input font-mono" required>
                        @error('usuario')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="form-input">
                    </div>
                </div>

                <button type="submit" class="btn-primary">Guardar Cambios</button>
            </form>
        </div>
    </div>

    {{-- Cambiar contraseña --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Cambiar Contraseña</h3></div>
        <div class="card-body">
            @if(session('status') === 'password-updated')
            <div class="alert-success mb-4">Contraseña actualizada correctamente.</div>
            @endif

            <form method="POST" action="{{ route('perfil.password') }}" class="space-y-4" x-data="{ mostrar: false }">
                @csrf @method('PUT')

                <div class="form-group">
                    <label class="form-label">Contraseña Actual *</label>
                    <input :type="mostrar ? 'text' : 'password'" name="current_password" class="form-input" required>
                    @error('current_password', 'updatePassword')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Nueva Contraseña *</label>
                        <input :type="mostrar ? 'text' : 'password'" name="password" class="form-input" required minlength="8">
                        @error('password', 'updatePassword')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar *</label>
                        <input :type="mostrar ? 'text' : 'password'" name="password_confirmation" class="form-input" required>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" x-model="mostrar" class="rounded border-gray-300"> Mostrar contraseñas
                </label>

                <button type="submit" class="btn-warning">Actualizar Contraseña</button>
            </form>
        </div>
    </div>

</div>
@endsection
