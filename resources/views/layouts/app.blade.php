<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ rtrim(url('/'), '/') }}">
    <title>@yield('title', 'Dashboard') — VISOFTEC POS</title>
    {{-- Oculta los elementos x-cloak (modales) hasta que Alpine cargue; evita que parpadeen --}}
    <style>[x-cloak]{display:none !important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-gray-50" x-data="sidebar()">

{{-- Flash messages para JS --}}
@if(session('success'))
    <div data-flash="success" data-message="{{ session('success') }}" hidden></div>
@endif
@if(session('error'))
    <div data-flash="error" data-message="{{ session('error') }}" hidden></div>
@endif
@if(session('info'))
    <div data-flash="info" data-message="{{ session('info') }}" hidden></div>
@endif
@if(session('warning'))
    <div data-flash="warning" data-message="{{ session('warning') }}" hidden></div>
@endif

<div class="flex h-full">
    {{-- SIDEBAR --}}
    <aside
        class="sidebar"
        :class="abierto
            ? 'w-64 translate-x-0'
            : 'w-16 -translate-x-full lg:translate-x-0 sidebar-collapsed'"
    >
        {{-- Logo --}}
        <div class="flex items-center border-b border-white/10 transition-all duration-300 overflow-hidden"
             :class="abierto ? 'gap-3 px-5 py-4' : 'justify-center px-0 py-4'">
            <img src="{{ asset('images/vigitec-logo.png') }}"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                 class="w-9 h-9 rounded-xl object-contain flex-shrink-0" alt="VISOFTEC">
            <div class="w-9 h-9 rounded-xl items-center justify-center flex-shrink-0 hidden" style="background-color:#0D6EFD">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <div x-show="abierto" x-transition:enter="transition-opacity duration-200 delay-100"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-100" x-transition:leave-end="opacity-0">
                <p class="text-sm font-bold text-white leading-tight tracking-wide whitespace-nowrap">VISOFTEC POS</p>
                <p class="text-xs text-gray-400 whitespace-nowrap">Panel Administrativo</p>
            </div>
        </div>

        {{-- Navegación --}}
        <nav class="flex-1 overflow-y-auto py-4 space-y-0.5 scrollbar-hide">

            <a href="{{ route('dashboard') }}"
               data-tooltip="Dashboard"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span x-show="abierto">Dashboard</span>
            </a>

            @can('ventas.crear')
            <a href="{{ route('ventas.create') }}"
               data-tooltip="Nueva Venta"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('ventas.create') ? 'active' : '' }} mx-2 my-1 text-white" style="background-color:#0D6EFD">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span x-show="abierto" class="font-semibold">Nueva Venta</span>
            </a>
            @endcan

            @can('ventas.movil')
            <a href="{{ route('ventas.movil') }}"
               data-tooltip="POS Móvil"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('ventas.movil') ? 'active' : '' }} mx-2 my-1 text-white" style="background-color:#7c3aed">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span x-show="abierto" class="font-semibold">POS Móvil</span>
            </a>
            @endcan

            <div x-show="abierto" class="sidebar-nav-section">Ventas</div>

            @can('ventas.ver')
            <a href="{{ route('ventas.index') }}"
               data-tooltip="Historial Ventas"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('ventas.index') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span x-show="abierto">Historial Ventas</span>
            </a>
            @endcan

            @can('creditos.ver')
            <a href="{{ route('clientes.index') }}?con_deuda=1"
               data-tooltip="Créditos"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('clientes.*') && request('con_deuda') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span x-show="abierto">Créditos</span>
            </a>
            @endcan

            <div x-show="abierto" class="sidebar-nav-section">Inventario</div>

            @can('inventario.ver')
            <a href="{{ route('inventario.index') }}"
               data-tooltip="Inventario"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('inventario.index') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span x-show="abierto">Inventario</span>
            </a>
            @endcan

            @can('productos.ver')
            <a href="{{ route('productos.index') }}"
               data-tooltip="Productos"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('productos.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                <span x-show="abierto">Productos</span>
            </a>
            @endcan
            @can('productos.editar')
            <a href="{{ route('categorias.index') }}"
               data-tooltip="Categorías"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span x-show="abierto">Categorías</span>
            </a>
            @endcan

            @can('inventario.entradas')
            <a href="{{ route('inventario.entrada') }}"
               data-tooltip="Entrada Mercancía"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('inventario.entrada') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span x-show="abierto">Entrada Mercancía</span>
            </a>
            @endcan

            @can('inventario.entradas')
            <a href="{{ route('traslados.index') }}"
               data-tooltip="Traslados"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('traslados.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span x-show="abierto">Traslados</span>
            </a>
            @endcan

            @can('inventario.ver')
            <a href="{{ route('codigos-barras.generador') }}"
               data-tooltip="Etiquetas / Códigos de Barras"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('codigos-barras.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.24M16.57 9H14m2.86-4.93A9 9 0 105.4 18.6M15 15l3 3"/><rect x="3" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/><rect x="3" y="16" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/><rect x="16" y="3" width="5" height="5" rx="0.5" stroke-width="2" stroke="currentColor" fill="none"/></svg>
                <span x-show="abierto">Etiquetas</span>
            </a>
            @endcan

            <div x-show="abierto" class="sidebar-nav-section">Gestión</div>

            @can('clientes.ver')
            <a href="{{ route('clientes.index') }}"
               data-tooltip="Clientes"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('clientes.*') && !request('con_deuda') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-show="abierto">Clientes</span>
            </a>
            @endcan

            @can('gastos.ver')
            <a href="{{ route('gastos.index') }}"
               data-tooltip="Gastos"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('gastos.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span x-show="abierto">Gastos</span>
            </a>
            @endcan

            @can('prestamos.ver')
            <a href="{{ route('prestamos.index') }}"
               data-tooltip="Cuentas Externos"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('prestamos.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span x-show="abierto">Cuentas Externos</span>
            </a>
            <a href="{{ route('locales-externos.index') }}"
               data-tooltip="Locales Externos"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('locales-externos.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-show="abierto">Locales Externos</span>
            </a>
            @endcan

            @can('cuentas.ver')
            <a href="{{ route('cuentas-bancarias.index') }}"
               data-tooltip="Cuentas Bancarias"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('cuentas-bancarias.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M4 18h16M6 18v-7m4 7v-7m4 7v-7m4 7v-7M4 11l8-6 8 6M4 11h16"/></svg>
                <span x-show="abierto">Cuentas Bancarias</span>
            </a>
            @endcan

            @can('caja.ver')
            <a href="{{ route('cuadre-caja.index') }}"
               data-tooltip="Cuadre de Caja"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('cuadre-caja.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span x-show="abierto">Cuadre de Caja</span>
            </a>
            @endcan

            @can('reportes.ver')
            <a href="{{ route('reportes.index') }}"
               data-tooltip="Reportes"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span x-show="abierto">Reportes</span>
            </a>
            @endcan

            @canany(['usuarios.ver', 'roles.ver'])
            <div x-show="abierto" class="sidebar-nav-section">Administración</div>
            @endcanany

            @can('usuarios.ver')
            <a href="{{ route('usuarios.index') }}"
               data-tooltip="Usuarios"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span x-show="abierto">Usuarios</span>
            </a>
            @endcan

            @can('roles.ver')
            <a href="{{ route('roles.index') }}"
               data-tooltip="Roles y Permisos"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span x-show="abierto">Roles y Permisos</span>
            </a>
            @endcan

            @can('roles.gestionar')
            <a href="{{ route('sucursales.index') }}"
               data-tooltip="Sucursales"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('sucursales.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span x-show="abierto">Sucursales</span>
            </a>
            <a href="{{ route('bodegas.index') }}"
               data-tooltip="Bodegas"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('bodegas.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span x-show="abierto">Bodegas</span>
            </a>
            <a href="{{ route('configuracion.edit') }}"
               data-tooltip="Configuración del Comercio"
               @mouseenter="mostrarTooltip($event)" @mouseleave="ocultarTooltip()"
               class="sidebar-nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                <span x-show="abierto">Configuración</span>
            </a>
            @endcan
        </nav>

        {{-- Footer sidebar: perfil con dropdown --}}
        <div class="border-t border-gray-800 p-3 relative" x-data="{ perfilMenu: false }">

            {{-- Dropdown (aparece ARRIBA del botón) --}}
            <div
                x-show="perfilMenu"
                @click.outside="perfilMenu = false"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute bottom-full left-0 right-0 mb-2 mx-1 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden z-50"
                style="display:none">

                {{-- Cabecera del menú --}}
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ auth()->user()->nombre }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    <span class="inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        {{ auth()->user()->getRoleNames()->first() ?? 'Usuario' }}
                    </span>
                </div>

                {{-- Ver perfil --}}
                <a href="{{ route('perfil') }}"
                   @click="perfilMenu = false"
                   class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Ver perfil</span>
                </a>

                {{-- Divisor --}}
                <div class="border-t border-gray-100 mx-3"></div>

                {{-- Cerrar sesión --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>

            {{-- Botón del perfil --}}
            <button type="button"
                @click="perfilMenu = !perfilMenu"
                class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-gray-800 transition-colors"
                :class="abierto ? '' : 'justify-center'">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-semibold flex-shrink-0 flex-shrink-0" style="background-color:#0D6EFD">
                    {{ strtoupper(substr(auth()->user()->nombre, 0, 1)) }}
                </div>
                <div x-show="abierto" class="min-w-0 flex-1 text-left"
                     x-transition:enter="transition-opacity duration-200 delay-100"
                     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity duration-100" x-transition:leave-end="opacity-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->nombre }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
                <svg x-show="abierto" class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform duration-200"
                     :class="perfilMenu ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>
    </aside>

    {{-- OVERLAY para móvil (solo cuando está abierto en pantallas pequeñas) --}}
    <div
        x-show="abierto"
        @click="toggle()"
        class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- CONTENIDO PRINCIPAL --}}
    <div
        class="flex-1 flex flex-col min-h-screen transition-all duration-300"
        :class="abierto ? 'lg:ml-64' : 'lg:ml-16'"
    >
        {{-- HEADER --}}
        <header class="sticky top-0 z-20 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="toggle()" class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-base font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
            </div>

            <div class="flex items-center gap-2">
                {{-- Fecha y hora en tiempo real --}}
                <span id="reloj-header" class="hidden sm:block text-sm text-gray-500"></span>
                <script>
                (function() {
                    function actualizar() {
                        const ahora = new Date();
                        const fecha = ahora.toLocaleDateString('es-CO', { day:'2-digit', month:'2-digit', year:'numeric' });
                        const hora  = ahora.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit', second:'2-digit', hour12: false });
                        const el = document.getElementById('reloj-header');
                        if (el) el.textContent = fecha + ' ' + hora;
                    }
                    actualizar();
                    setInterval(actualizar, 1000);
                })();
                </script>
            </div>
        </header>

        {{-- BREADCRUMBS --}}
        @hasSection('breadcrumbs')
        <div class="bg-white border-b border-gray-100 px-6 py-2">
            <nav class="flex text-sm" aria-label="Breadcrumb">
                <ol class="flex items-center gap-2 text-gray-500">
                    <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Inicio</a></li>
                    @yield('breadcrumbs')
                </ol>
            </nav>
        </div>
        @endif

        {{-- CONTENIDO --}}
        <main class="flex-1 p-4 lg:p-6">
            @yield('content')
        </main>

        {{-- FOOTER --}}
        <footer class="py-3 px-6 border-t border-gray-100 text-xs text-gray-400 text-center">
            © {{ date('Y') }} VISOFTEC POS &mdash; Todos los derechos reservados
        </footer>
    </div>
</div>

@stack('scripts')

{{-- Tooltip global del sidebar (posición fixed, fuera de todo overflow) --}}
<div
    x-show="tooltip.visible"
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 translate-x-1"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-75"
    x-transition:leave-end="opacity-0"
    :style="`top:${tooltip.top}px; left:${tooltip.left}px`"
    class="pointer-events-none fixed z-[9999] -translate-y-1/2 flex items-center gap-1.5"
    style="display:none">
    {{-- Flecha --}}
    <svg class="w-2 h-3 text-gray-900 flex-shrink-0" viewBox="0 0 8 12" fill="currentColor">
        <path d="M0 6 L8 0 L8 12 Z"/>
    </svg>
    {{-- Etiqueta --}}
    <span x-text="tooltip.text"
          class="px-2.5 py-1.5 bg-gray-900 text-white text-xs font-medium rounded-lg shadow-xl whitespace-nowrap">
    </span>
</div>

</body>
</html>
