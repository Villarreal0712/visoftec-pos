@extends('layouts.app')

@section('title', 'Roles y Permisos')
@section('page-title', 'Roles y Permisos')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-500">Gestión dinámica de roles y sus permisos</p>
        @can('roles.gestionar')
        <a href="{{ route('roles.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Nuevo Rol
        </a>
        @endcan
    </div>

    {{-- Tarjetas de roles --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($roles as $rol)
        @php
            $pct = $totalPermisos > 0 ? round(($rol->permissions_count / $totalPermisos) * 100) : 0;
            $usuariosCount = $rol->users->count();
            $colores = [
                'Administrador' => ['ring' => 'ring-red-400',    'bg' => 'bg-red-500',    'badge' => 'bg-red-100 text-red-700'],
                'Vendedor'      => ['ring' => 'ring-emerald-400','bg' => 'bg-emerald-500','badge' => 'bg-emerald-100 text-emerald-700'],
                'Almacenista'   => ['ring' => 'ring-blue-400',   'bg' => 'bg-blue-500',   'badge' => 'bg-blue-100 text-blue-700'],
            ];
            $color = $colores[$rol->name] ?? ['ring' => 'ring-purple-400', 'bg' => 'bg-purple-500', 'badge' => 'bg-purple-100 text-purple-700'];
        @endphp
        <div class="card ring-2 {{ $color['ring'] }} ring-offset-2 flex flex-col">
            <div class="card-body flex-1 space-y-4">
                {{-- Cabecera del rol --}}
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl {{ $color['bg'] }} flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                            {{ strtoupper(substr($rol->name, 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $rol->name }}</h3>
                            <p class="text-xs text-gray-400">
                                {{ $usuariosCount }} usuario{{ $usuariosCount !== 1 ? 's' : '' }}
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $color['badge'] }}">
                        {{ $rol->permissions_count }}/{{ $totalPermisos }}
                    </span>
                </div>

                {{-- Barra de progreso de permisos --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                        <span>Permisos asignados</span>
                        <span class="font-semibold">{{ $pct }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all {{ $color['bg'] }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                <a href="{{ route('roles.edit', $rol) }}" class="text-sm text-blue-600 font-medium hover:underline flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    @can('roles.gestionar') Editar permisos @else Ver permisos @endcan
                </a>
                @can('roles.gestionar')
                @if($usuariosCount === 0)
                <form id="del-rol-{{ $rol->id }}" method="POST" action="{{ route('roles.destroy', $rol) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="button" onclick="confirmarEliminar('del-rol-{{ $rol->id }}')"
                        class="text-sm text-red-500 hover:text-red-700 font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Eliminar
                    </button>
                </form>
                @else
                <span class="text-xs text-gray-400">En uso</span>
                @endif
                @endcan
            </div>
        </div>
        @empty
        <div class="col-span-3 text-center py-12 text-gray-400">No hay roles registrados</div>
        @endforelse
    </div>

    {{-- Leyenda de permisos disponibles --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title text-sm">Permisos disponibles en el sistema</h3>
            <span class="text-xs text-gray-400">{{ $totalPermisos }} permisos</span>
        </div>
        <div class="card-body">
            @php
                $grupos = \Spatie\Permission\Models\Permission::orderBy('name')->get()
                    ->groupBy(fn($p) => explode('.', $p->name)[0]);
            @endphp
            <div class="flex flex-wrap gap-2">
                @foreach($grupos as $modulo => $permisos)
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $modulo }}</span>
                    @foreach($permisos as $p)
                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-mono">{{ explode('.', $p->name)[1] }}</span>
                    @endforeach
                    <span class="text-gray-200 text-xs">·</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
@endsection
