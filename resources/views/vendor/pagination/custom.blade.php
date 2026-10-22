@if ($paginator->hasPages())
<nav class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-4" aria-label="Paginación">

    {{-- Info de registros --}}
    <p class="text-sm text-gray-500 order-2 sm:order-1">
        Mostrando
        <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}</span>
        al
        <span class="font-semibold text-gray-700">{{ $paginator->lastItem() }}</span>
        de
        <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span>
        registros
    </p>

    {{-- Botones de página --}}
    <ul class="flex items-center gap-1 order-1 sm:order-2">

        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <li>
                <span class="px-2.5 py-1.5 text-sm rounded-lg text-gray-300 cursor-not-allowed select-none">‹</span>
            </li>
        @else
            <li>
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="px-2.5 py-1.5 text-sm rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">‹</a>
            </li>
        @endif

        {{-- Páginas --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li>
                    <span class="px-2.5 py-1.5 text-sm text-gray-400 select-none">…</span>
                </li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li>
                            <span class="px-3 py-1.5 text-sm font-semibold bg-blue-600 text-white rounded-lg select-none">{{ $page }}</span>
                        </li>
                    @else
                        <li>
                            <a href="{{ $url }}"
                               class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Siguiente --}}
        @if ($paginator->hasMorePages())
            <li>
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="px-2.5 py-1.5 text-sm rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">›</a>
            </li>
        @else
            <li>
                <span class="px-2.5 py-1.5 text-sm rounded-lg text-gray-300 cursor-not-allowed select-none">›</span>
            </li>
        @endif

    </ul>
</nav>
@endif
