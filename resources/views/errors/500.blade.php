<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del servidor</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="text-center px-4">
        <p class="text-8xl font-extrabold text-gray-200 mb-4">500</p>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Error del Servidor</h1>
        <p class="text-gray-500 mb-8">Ocurrió un error inesperado. Por favor intenta de nuevo.</p>
        @auth
        <a href="{{ route('dashboard') }}" class="btn-primary">← Ir al Dashboard</a>
        @else
        <a href="{{ route('login') }}" class="btn-primary">← Ir al Login</a>
        @endauth
    </div>
</body>
</html>
