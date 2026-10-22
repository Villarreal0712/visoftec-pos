@extends('layouts.app')

@section('title', $usuario->nombre)
@section('page-title', 'Usuario: ' . $usuario->nombre)

@section('content')
<div class="space-y-5 max-w-4xl mx-auto">

    <div class="flex flex-wrap gap-3">
        @can('usuarios.editar')
        <a href="{{ route('usuarios.edit', $usuario) }}" class="btn-secondary">Editar</a>
        @endcan
        <a href="{{ route('usuarios.index') }}" class="btn-secondary">← Volver</a>
        @can('usuarios.eliminar')
        @if($usuario->id !== auth()->id())
        <form id="del-usuario" method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="inline ml-auto">
            @csrf @method('DELETE')
            <button type="button" onclick="confirmarEliminar('del-usuario')" class="btn btn-danger btn-sm">Eliminar</button>
        </form>
        @endif
        @endcan
    </div>

    <div class="grid md:grid-cols-3 gap-5">
        {{-- Datos del usuario --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Información</h3></div>
            <div class="card-body space-y-3 text-sm">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-indigo-500 flex items-center justify-center text-white text-2xl font-bold">
                        {{ strtoupper(substr($usuario->nombre, 0, 1)) }}
                    </div>
                </div>
                <div class="flex justify-between"><span class="text-gray-500">Nombre</span><span class="font-medium">{{ $usuario->nombre }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Usuario</span><span class="font-mono text-blue-700">{{ $usuario->usuario }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Correo</span><span class="text-xs">{{ $usuario->email ?? '—' }}</span></div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Rol</span>
                    <span class="badge badge-info">{{ $usuario->roles->first()?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Estado</span>
                    @if($usuario->activo)
                        <span class="badge badge-success">Activo</span>
                    @else
                        <span class="badge badge-danger">Inactivo</span>
                    @endif
                </div>
                <div class="flex justify-between border-t pt-3">
                    <span class="text-gray-500">Registrado</span>
                    <span>{{ $usuario->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Resumen de actividad --}}
        <div class="card md:col-span-2">
            <div class="card-header"><h3 class="card-title">Últimas Ventas</h3></div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Método</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usuario->ventas as $venta)
                        <tr>
                            <td>
                                <a href="{{ route('ventas.show', $venta) }}" class="text-blue-600 font-medium hover:underline">
                                    #{{ str_pad($venta->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td class="text-sm text-gray-600">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-sm">{{ $venta->cliente?->nombre ?? 'Consumidor final' }}</td>
                            <td><span class="badge badge-info">{{ $venta->metodo_pago }}</span></td>
                            <td class="text-right font-bold text-money">${{ number_format($venta->total, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-6 text-gray-400">Sin ventas registradas</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Cambiar contraseña --}}
    @can('usuarios.editar')
    <div class="card max-w-md" x-data="{ abierto: @js($errors->has('password')) }">
        <div class="card-header cursor-pointer" @click="abierto = !abierto">
            <h3 class="card-title">Cambiar Contraseña</h3>
            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="abierto && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div x-show="abierto" x-cloak class="card-body">
            <form method="POST" action="{{ route('usuarios.password', $usuario) }}" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="password" class="form-input" minlength="8" required placeholder="Mín. 8 caracteres">
                        @error('password')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar</label>
                        <input type="password" name="password_confirmation" class="form-input" required placeholder="Repetir">
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full">Actualizar Contraseña</button>
            </form>
        </div>
    </div>
    @endcan

</div>
@endsection
