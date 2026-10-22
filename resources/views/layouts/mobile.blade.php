<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ rtrim(url('/'), '/') }}">
    <meta name="theme-color" content="#1e40af">
    <title>@yield('title', 'POS Móvil') — VISOFTEC</title>
    {{-- Oculta los elementos x-cloak (modales) hasta que Alpine cargue; evita que parpadeen --}}
    <style>[x-cloak]{display:none !important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full bg-gray-50">

@if(session('success'))
    <div data-flash="success" data-message="{{ session('success') }}" hidden></div>
@endif
@if(session('error'))
    <div data-flash="error" data-message="{{ session('error') }}" hidden></div>
@endif

@yield('content')

@stack('scripts')
</body>
</html>
