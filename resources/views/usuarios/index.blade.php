@extends('layouts.app')

@section('title', 'Usuarios')
@section('page-title', 'Gestión de Usuarios')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-500">Administración de usuarios y permisos del sistema</p>
        @can('usuarios.crear')
        <a href="{{ route('usuarios.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Nuevo Usuario
        </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body border-b border-gray-100 flex flex-wrap items-center gap-3 justify-between">
            <form method="GET" action="{{ route('usuarios.index') }}" class="flex flex-wrap gap-3 flex-1"
                  x-data="{ buscar: @js((string) request('buscar', '')) }">
                <div class="flex-1 min-w-40 relative">
                    <input type="text" name="buscar" x-model="buscar"
                        @keydown.enter.prevent="$el.form.submit()"
                        @input.debounce.400ms="$el.form.submit()"
                        class="form-input pr-8" placeholder="Buscar usuario..." autocomplete="off">
                    <button x-show="buscar.length > 0" type="button"
                        @click="buscar = ''; $nextTick(() => $el.form.submit())"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-300 hover:text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <select name="rol" class="form-select w-auto" @change="$el.form.submit()">
                    <option value="">Todos los roles</option>
                    @foreach($roles as $rol)
                    <option value="{{ $rol->name }}" {{ request('rol') === $rol->name ? 'selected' : '' }}>{{ $rol->name }}</option>
                    @endforeach
                </select>
            </form>
            <div class="flex items-center gap-3 flex-shrink-0">
                <x-per-page-selector :current="request('per_page', 25)" />
                @can('usuarios.ver')<x-export-btn />@endcan
            </div>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th class="text-center">Estado</th>
                        <th>Registrado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                                    {{ strtoupper(substr($usuario->nombre, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-mono text-sm font-medium text-gray-900">{{ $usuario->usuario }}</p>
                                    @if($usuario->email)<p class="text-xs text-gray-400">{{ $usuario->email }}</p>@endif
                                </div>
                            </div>
                        </td>
                        <td class="text-gray-700">{{ $usuario->nombre }}</td>
                        <td>
                            @foreach($usuario->roles as $rol)
                            <span class="badge {{ $rol->name === 'Administrador' ? 'badge-danger' : ($rol->name === 'Vendedor' ? 'badge-success' : 'badge-info') }}">
                                {{ $rol->name }}
                            </span>
                            @endforeach
                        </td>
                        <td class="text-center">
                            @if($usuario->activo)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-sm text-gray-500">{{ $usuario->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('usuarios.show', $usuario) }}" class="btn btn-secondary btn-sm">Ver</a>
                                @can('usuarios.editar')
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-secondary btn-sm">Editar</a>
                                @endcan
                                @can('usuarios.eliminar')
                                @if($usuario->id !== auth()->id())
                                <form id="del-user-{{ $usuario->id }}" method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="confirmarEliminar('del-user-{{ $usuario->id }}')" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-8 text-gray-400">No hay usuarios registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-100">{{ $usuarios->links() }}</div>
    </div>

</div>
@endsection
