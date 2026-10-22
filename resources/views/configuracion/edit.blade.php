@extends('layouts.app')

@section('title', 'Configuración del Comercio')
@section('page-title', 'Configuración del Comercio')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <p class="text-sm text-gray-500">
        Esta información aparecerá en los tickets de venta y comprobantes de abono.
    </p>

    <form method="POST" action="{{ route('configuracion.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Datos del comercio --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Datos del Comercio</h2>
            </div>
            <div class="card-body space-y-4">

                <div class="form-group">
                    <label class="form-label">Nombre del comercio <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre_comercio"
                        value="{{ old('nombre_comercio', $config->nombre_comercio) }}"
                        class="form-input @error('nombre_comercio') border-red-400 @enderror"
                        placeholder="Ej: Tienda Siscom">
                    @error('nombre_comercio')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">NIT / RUT</label>
                        <input type="text" name="nit"
                            value="{{ old('nit', $config->nit) }}"
                            class="form-input @error('nit') border-red-400 @enderror"
                            placeholder="Ej: 900.123.456-7">
                        @error('nit')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono"
                            value="{{ old('telefono', $config->telefono) }}"
                            class="form-input @error('telefono') border-red-400 @enderror"
                            placeholder="Ej: 300 123 4567">
                        @error('telefono')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="ciudad"
                            value="{{ old('ciudad', $config->ciudad) }}"
                            class="form-input @error('ciudad') border-red-400 @enderror"
                            placeholder="Ej: Medellín">
                        @error('ciudad')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email"
                            value="{{ old('email', $config->email) }}"
                            class="form-input @error('email') border-red-400 @enderror"
                            placeholder="Ej: tienda@ejemplo.com">
                        @error('email')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion"
                        value="{{ old('direccion', $config->direccion) }}"
                        class="form-input @error('direccion') border-red-400 @enderror"
                        placeholder="Ej: Calle 10 # 5-23, Local 4">
                    @error('direccion')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Mensaje en el ticket</label>
                    <textarea name="mensaje_ticket" rows="2"
                        class="form-input @error('mensaje_ticket') border-red-400 @enderror"
                        placeholder="Ej: ¡Gracias por su compra! No se aceptan devoluciones sin factura.">{{ old('mensaje_ticket', $config->mensaje_ticket) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Aparece al pie del ticket de venta y del comprobante de abono.</p>
                    @error('mensaje_ticket')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Ancho de la impresora térmica</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ancho_ticket" value="58mm"
                                {{ old('ancho_ticket', $config->ancho_ticket ?? '80mm') === '58mm' ? 'checked' : '' }}
                                class="accent-blue-600">
                            <span class="text-sm font-medium">58 mm</span>
                            <span class="text-xs text-gray-400">(papel angosto)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="ancho_ticket" value="80mm"
                                {{ old('ancho_ticket', $config->ancho_ticket ?? '80mm') === '80mm' ? 'checked' : '' }}
                                class="accent-blue-600">
                            <span class="text-sm font-medium">80 mm</span>
                            <span class="text-xs text-gray-400">(papel estándar)</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">El ticket se ajusta automáticamente al ancho seleccionado al imprimir.</p>
                    @error('ancho_ticket')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- Vista previa del encabezado del ticket --}}
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Vista previa del encabezado</h2>
                <span class="text-xs text-gray-400">Así se verá en los tickets</span>
            </div>
            <div class="card-body flex justify-center">
                <div class="font-mono text-sm text-center border-b-2 border-dashed border-gray-300 pb-4 w-64">
                    <p class="text-base font-bold" id="prev-nombre">{{ $config->nombre_comercio ?: 'Mi Tienda' }}</p>
                    <p class="text-xs text-gray-500" id="prev-nit">
                        @if($config->nit) NIT: {{ $config->nit }} @endif
                    </p>
                    <p class="text-xs text-gray-500" id="prev-direccion">{{ $config->direccion }}</p>
                    <p class="text-xs text-gray-500" id="prev-ciudad-tel">
                        {{ collect([$config->ciudad, $config->telefono])->filter()->implode(' · ') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1" id="prev-email">{{ $config->email }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Guardar Configuración
            </button>
            <a href="{{ route('dashboard') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Actualizar vista previa en tiempo real
const campos = {
    nombre_comercio: 'prev-nombre',
    nit:             'prev-nit',
    direccion:       'prev-direccion',
    ciudad:          'prev-ciudad-tel',
    telefono:        'prev-ciudad-tel',
    email:           'prev-email',
};

function actualizarPreview() {
    const nombre   = document.querySelector('[name=nombre_comercio]').value || 'Mi Tienda';
    const nit      = document.querySelector('[name=nit]').value;
    const dir      = document.querySelector('[name=direccion]').value;
    const ciudad   = document.querySelector('[name=ciudad]').value;
    const tel      = document.querySelector('[name=telefono]').value;
    const email    = document.querySelector('[name=email]').value;

    document.getElementById('prev-nombre').textContent    = nombre;
    document.getElementById('prev-nit').textContent       = nit ? 'NIT: ' + nit : '';
    document.getElementById('prev-direccion').textContent = dir;
    document.getElementById('prev-ciudad-tel').textContent = [ciudad, tel].filter(Boolean).join(' · ');
    document.getElementById('prev-email').textContent     = email;
}

document.querySelectorAll('[name=nombre_comercio],[name=nit],[name=direccion],[name=ciudad],[name=telefono],[name=email]')
    .forEach(el => el.addEventListener('input', actualizarPreview));
</script>
@endpush
@endsection
