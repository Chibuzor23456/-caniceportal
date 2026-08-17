@props(['id', 'name', 'required' => false, 'autofocus' => false, 'autocomplete' => null, 'value' => null])
<div x-data="{ visible: false }" {{ $attributes->merge(['class' => 'relative']) }}>
    <input
        id="{{ $id }}"
        :type="visible ? 'text' : 'password'"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($autofocus) autofocus @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($value !== null) value="{{ $value }}" @endif
        class="w-full rounded-xl border border-slate-200 px-3 py-2 pr-10 text-sm focus:border-slate-400 focus:outline-none"
    >
    <button
        type="button"
        @click="visible = !visible"
        tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
    >
        <svg x-show="!visible" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
        </svg>
        <svg x-show="visible" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 8 10 8a17.7 17.7 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
            <path d="M6.6 6.6C3.9 8.36 2 11 2 11s3.5 8 10 8a9.6 9.6 0 0 0 5.4-1.6"/>
            <path d="m2 2 20 20"/>
        </svg>
    </button>
</div>
