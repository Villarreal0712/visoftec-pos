@extends('layouts.app')

@section('title', 'Registrar Gasto')
@section('page-title', 'Registrar Gasto')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuevo Gasto</h3>
            <a href="{{ route('gastos.index') }}" class="btn-secondary btn-sm">← Volver</a>
        </div>
        <div class="card-body">
            @if($errors->any())
            <div class="alert-error mb-5">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
            </div>
            @endif

            <form method="POST" action="{{ route('gastos.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div class="form-group">
                    <label class="form-label">Descripción *</label>
                    <input type="text" name="descripcion" value="{{ old('descripcion') }}" class="form-input" required placeholder="Ej: Pago de arriendo, compra de materiales...">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Categoría *</label>
                        <select name="categoria" class="form-select" required>
                            @foreach($categorias as $cat)
                            <option value="{{ $cat }}" {{ old('categoria') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-select" required>
                            <option value="Externo" {{ old('tipo', 'Externo') === 'Externo' ? 'selected' : '' }}>Externo (Proveedor/Servicio)</option>
                            <option value="Local" {{ old('tipo') === 'Local' ? 'selected' : '' }}>Local (Caja chica)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Valor *</label>
                        <div class="relative" x-data="moneyInput({{ old('valor', '') }})">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input type="text" inputmode="numeric" x-ref="display" @input="update($event)"
                                class="form-input pl-6" required placeholder="0">
                            <input type="hidden" name="valor" :value="raw">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pagado desde</label>
                        <select name="id_cuenta_bancaria" class="form-select">
                            @foreach($cuentas as $cuenta)
                            <option value="{{ $cuenta->id }}" {{ old('id_cuenta_bancaria') == $cuenta->id ? 'selected' : '' }}>{{ $cuenta->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Comprobante (imagen/PDF)</label>
                    <input type="file" name="comprobante" class="form-input" accept=".jpg,.jpeg,.png,.pdf">
                    <p class="text-xs text-gray-400 mt-1">Máximo 5MB. Formatos: JPG, PNG, PDF</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Observación</label>
                    <textarea name="observacion" rows="3" class="form-textarea" placeholder="Detalles adicionales...">{{ old('observacion') }}</textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Registrar Gasto
                    </button>
                    <a href="{{ route('gastos.index') }}" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
