@extends('layouts.mobile')
@section('title', 'Historial de Ventas')

@section('content')
<div
    x-data="{
        modalAnular: false,
        venta: null,
        motivo: '',
        abrirAnular(id, numero) {
            this.venta = { id, numero };
            this.motivo = '';
            this.modalAnular = true;
        },
        submitAnular() {
            this.$refs.formAnular.submit();
        }
    }"
    class="min-h-screen bg-gray-50 flex flex-col"
>

    {{-- Header --}}
    <header class="bg-emerald-700 px-4 pt-8 pb-4 flex items-center gap-3 shadow-md">
        <a href="{{ route('ventas.movil') }}"
           class="text-emerald-200 hover:text-white p-2 rounded-xl hover:bg-emerald-600/60 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="flex-1">
            <p class="text-emerald-300 text-xs font-semibold uppercase tracking-widest">POS Móvil</p>
            <h1 class="text-2xl font-extrabold text-white leading-tight">Historial de Ventas</h1>
        </div>
    </header>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('ventas.movil.historial') }}"
          class="bg-white border-b border-gray-200 px-4 py-3 space-y-2">
        <div class="flex gap-2">
            <input type="date" name="fecha" value="{{ $fecha }}"
                   class="flex-1 rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
            <button type="submit"
                    class="bg-emerald-600 text-white rounded-xl px-4 py-2 text-sm font-semibold active:scale-95 transition-transform">
                Filtrar
            </button>
        </div>
        <div class="flex gap-2">
            <input type="text" name="referencia" value="{{ request('referencia') }}"
                   placeholder="Buscar por referencia o producto..."
                   class="flex-1 rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
            @if(request('referencia'))
            <a href="{{ route('ventas.movil.historial', ['fecha' => $fecha]) }}"
               class="text-gray-400 px-3 py-2 rounded-xl border border-gray-300 text-sm">✕</a>
            @endif
        </div>
    </form>

    {{-- Resumen --}}
    <div class="bg-emerald-50 border-b border-emerald-100 px-4 py-2 flex items-center justify-between">
        <p class="text-sm text-emerald-700 font-medium">
            {{ $ventas->total() }} venta(s) encontrada(s)
        </p>
        <p class="text-sm font-bold text-emerald-800">
            $ {{ number_format($totalFiltrado, 0, ',', '.') }}
        </p>
    </div>

    {{-- Mensajes flash --}}
    @if(session('success'))
    <div class="mx-4 mt-3 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 font-medium">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mx-4 mt-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 font-medium">
        {{ session('error') }}
    </div>
    @endif

    {{-- Lista de ventas --}}
    <div class="flex-1 px-4 py-3 space-y-3">
        @forelse($ventas as $v)
        <div class="bg-white rounded-2xl shadow-sm border {{ $v->anulada ? 'border-red-100 opacity-70' : 'border-gray-100' }} overflow-hidden">

            {{-- Cabecera de la tarjeta --}}
            <div class="px-4 pt-4 pb-2 flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-gray-800 text-sm">
                            #{{ str_pad($v->id, 5, '0', STR_PAD_LEFT) }}
                        </span>
                        @if($v->anulada)
                            <span class="text-xs font-bold bg-red-100 text-red-700 px-2 py-0.5 rounded-full">Anulada</span>
                        @elseif($v->es_credito && $v->estado_credito !== 'Pagado')
                            <span class="text-xs font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Crédito</span>
                        @else
                            <span class="text-xs font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Pagada</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $v->created_at->format('d/m/Y H:i') }}</p>
                    <p class="text-sm text-gray-700 font-medium mt-1">
                        {{ $v->cliente?->nombre ?? 'Público general' }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xl font-extrabold {{ $v->anulada ? 'text-gray-400 line-through' : 'text-gray-900' }}">
                        $&nbsp;{{ number_format($v->total, 0, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $v->metodo_pago }}</p>
                </div>
            </div>

            {{-- Productos --}}
            @if($v->historialVentas->isNotEmpty())
            <div class="px-4 pb-3 space-y-0.5 border-t border-gray-50 pt-2">
                @foreach($v->historialVentas as $hv)
                <div class="flex justify-between items-center text-xs text-gray-500">
                    <span class="truncate flex-1">
                        {{ $hv->lote->detalleProducto->producto->nombre }}
                        @if($hv->lote->detalleProducto->talla)
                            &nbsp;T{{ $hv->lote->detalleProducto->talla }}
                        @endif
                    </span>
                    <span class="ml-2 flex-shrink-0 font-medium">× {{ $hv->cantidad }}</span>
                    <span class="ml-3 flex-shrink-0 tabular-nums">$ {{ number_format($hv->cantidad * $hv->precio_venta, 0, ',', '.') }}</span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Acción anular --}}
            @if(!$v->anulada)
            @can('ventas.anular')
            <div class="border-t border-gray-100 px-4 py-2 flex items-center justify-between">
                <a href="{{ route('ventas.ticket', $v) }}"
                   class="text-xs text-blue-600 font-semibold">Ver ticket</a>
                <button
                    @click="abrirAnular({{ $v->id }}, '#{{ str_pad($v->id, 5, '0', STR_PAD_LEFT) }}')"
                    class="text-xs text-red-500 font-semibold py-1 px-3 rounded-lg hover:bg-red-50 active:scale-95 transition-all">
                    Anular
                </button>
            </div>
            @endcan
            @endif

        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-16 text-gray-400">
            <svg class="w-14 h-14 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="font-semibold">Sin ventas</p>
            <p class="text-sm mt-1">No hay ventas para este filtro</p>
        </div>
        @endforelse
    </div>

    {{-- Paginación --}}
    @if($ventas->hasPages())
    <div class="px-4 pb-8 flex items-center justify-between gap-3">
        @if($ventas->onFirstPage())
            <span class="flex-1 text-center py-3 rounded-xl border border-gray-200 text-gray-300 text-sm font-semibold">Anterior</span>
        @else
            <a href="{{ $ventas->previousPageUrl() }}"
               class="flex-1 text-center py-3 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold active:scale-95 transition-transform">
                ← Anterior
            </a>
        @endif

        <span class="text-sm text-gray-500 whitespace-nowrap">
            {{ $ventas->currentPage() }} / {{ $ventas->lastPage() }}
        </span>

        @if($ventas->hasMorePages())
            <a href="{{ $ventas->nextPageUrl() }}"
               class="flex-1 text-center py-3 rounded-xl border border-gray-300 text-gray-700 text-sm font-semibold active:scale-95 transition-transform">
                Siguiente →
            </a>
        @else
            <span class="flex-1 text-center py-3 rounded-xl border border-gray-200 text-gray-300 text-sm font-semibold">Siguiente</span>
        @endif
    </div>
    @endif

    {{-- Formulario oculto para anular (reutilizado por el modal) --}}
    @can('ventas.anular')
    <form x-ref="formAnular"
          :action="'{{ url('ventas') }}/' + venta?.id"
          method="POST" style="display:none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="_from_movil" value="1">
        <input type="hidden" name="motivo_anulacion" :value="motivo">
    </form>
    @endcan

    {{-- Modal de confirmación de anulación --}}
    <div
        x-show="modalAnular"
        x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center"
        style="background: rgba(0,0,0,0.55);"
        @click.self="modalAnular = false"
    >
        <div class="w-full max-w-md bg-white rounded-t-3xl px-5 pt-6 pb-10 shadow-2xl" @click.stop>
            <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-5"></div>

            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-gray-900">Anular venta <span x-text="venta?.numero"></span></p>
                    <p class="text-xs text-gray-500">Esta acción devuelve el stock al inventario</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo (opcional)</label>
                <textarea
                    x-model="motivo"
                    rows="2"
                    placeholder="Ej: cliente cambió de opinión, error en el precio..."
                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"
                ></textarea>
            </div>

            <div class="flex flex-col gap-2">
                <button
                    @click="submitAnular()"
                    class="w-full bg-red-600 hover:bg-red-700 active:scale-95 text-white font-bold rounded-2xl py-4 transition-all">
                    Confirmar anulación
                </button>
                <button
                    @click="modalAnular = false"
                    class="w-full text-gray-400 py-2 text-sm">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
