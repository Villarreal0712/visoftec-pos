@props(['current' => 25])

<div class="flex items-center gap-2">
    <label class="text-sm text-gray-500 whitespace-nowrap">Mostrar</label>
    <select
        onchange="(function(v){ var u = new URL(window.location); u.searchParams.set('per_page', v); u.searchParams.delete('page'); window.location = u.toString(); })(this.value)"
        class="form-input py-1.5 pl-2.5 pr-7 text-sm w-auto">
        @foreach ([10, 25, 50, 100] as $n)
            <option value="{{ $n }}" {{ (int)$current === $n ? 'selected' : '' }}>{{ $n }}</option>
        @endforeach
    </select>
    <span class="text-sm text-gray-500 whitespace-nowrap">por página</span>
</div>
