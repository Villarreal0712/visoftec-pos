<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>
<body class="bg-gray-50 p-4">
    @yield('content')
    @stack('scripts')
</body>
</html>
