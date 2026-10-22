<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 flex items-center justify-center p-4">

<div class="w-full max-w-sm">
    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="inline-flex w-16 h-16 rounded-2xl bg-blue-500 items-center justify-center mb-4 shadow-lg">
            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">VISOFTEC POS</h1>
        <p class="text-blue-300 text-sm mt-1">Gestión Integral de Comercio</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Iniciar Sesión</h2>

        @if($errors->any())
        <div class="alert-error mb-4">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div>
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div class="form-group">
                <label for="usuario" class="form-label">Usuario</label>
                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    value="{{ old('usuario') }}"
                    class="form-input @error('usuario') border-red-400 @enderror"
                    placeholder="Tu nombre de usuario"
                    required
                    autofocus
                />
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <div class="relative" x-data="{ mostrar: false }">
                    <input
                        :type="mostrar ? 'text' : 'password'"
                        id="password"
                        name="password"
                        class="form-input pr-10 @error('password') border-red-400 @enderror"
                        placeholder="Tu contraseña"
                        required
                    />
                    <button type="button" @click="mostrar = !mostrar" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg x-show="!mostrar" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="mostrar" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="remember" name="remember" class="rounded border-gray-300 text-blue-600">
                <label for="remember" class="text-sm text-gray-600">Recordarme</label>
            </div>

            <button type="submit" class="btn-primary w-full py-2.5 text-base font-semibold mt-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Ingresar al Sistema
            </button>
        </form>

      {{--  <div class="mt-6 pt-5 border-t border-gray-100">
            <p class="text-xs text-center text-gray-400 font-medium mb-2">Credenciales de prueba:</p>
            <div class="grid grid-cols-3 gap-2 text-xs">
                <div class="bg-blue-50 rounded-lg p-2 text-center">
                    <p class="font-bold text-blue-700">Admin</p>
                    <p class="text-gray-500">admin</p>
                    <p class="text-gray-500">admin123</p>
                </div>
                <div class="bg-emerald-50 rounded-lg p-2 text-center">
                    <p class="font-bold text-emerald-700">Vendedor</p>
                    <p class="text-gray-500">vendedor1</p>
                    <p class="text-gray-500">vendedor123</p>
                </div>
                <div class="bg-amber-50 rounded-lg p-2 text-center">
                    <p class="font-bold text-amber-700">Almacén</p>
                    <p class="text-gray-500">almacenista1</p>
                    <p class="text-gray-500">almacen123</p>
                </div>
            </div>
        </div>
    </div> --}}

    <p class="text-center text-blue-400 text-xs mt-6">
        © {{ date('Y') }} VISOFTEC POS. <br> Todos los derechos reservados.
    </p>
</div>

</body>
</html>
