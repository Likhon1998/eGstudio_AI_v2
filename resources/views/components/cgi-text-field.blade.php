@props([
    'name',
    'model' => 'val',
    'required' => false,
])

@php
    $fieldRef = 'cgiField_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
@endphp

<div
    class="cgi-text-field-group"
    x-effect="$nextTick(() => { {{ $model }}; if ($refs.{{ $fieldRef }}) window.cgiGrowField($refs.{{ $fieldRef }}); })"
>
    <textarea
        name="{{ $name }}"
        x-model="{{ $model }}"
        x-ref="{{ $fieldRef }}"
        rows="1"
        @input="window.cgiGrowField($refs.{{ $fieldRef }})"
        x-init="$nextTick(() => window.cgiGrowField($refs.{{ $fieldRef }}))"
        @focus="window.cgiGrowField($refs.{{ $fieldRef }})"
        {{ $required ? 'required' : '' }}
        {{ $attributes->class([
            'cgi-expand-input w-full bg-black/40 border border-gray-700/80 rounded-xl text-white',
            'focus:ring-1 focus:ring-blue-500/50 focus:border-blue-500 p-2.5 outline-none transition-all',
            'text-sm shadow-inner placeholder-gray-600 resize-none overflow-hidden min-h-[42px] leading-relaxed',
        ]) }}
        {{ $attributes->except(['class']) }}
    ></textarea>

    <div
        x-show="({{ $model }} || '').trim().length"
        x-cloak
        class="mt-1.5 px-2.5 py-2 rounded-lg bg-blue-950/20 border border-blue-500/15"
    >
        <p class="text-[8px] font-black uppercase tracking-widest text-blue-400/70 mb-1">What you entered</p>
        <p class="text-[11px] text-gray-200 leading-relaxed break-words whitespace-pre-wrap" x-text="{{ $model }}"></p>
    </div>

    <div
        x-show="!({{ $model }} || '').trim().length && ($refs.{{ $fieldRef }}?.placeholder || '').length"
        x-cloak
        class="mt-1.5 px-2.5 py-2 rounded-lg bg-gray-900/50 border border-gray-800/80"
    >
        <p class="text-[8px] font-black uppercase tracking-widest text-gray-500 mb-1">Example</p>
        <p
            class="text-[11px] text-gray-500 leading-relaxed break-words whitespace-pre-wrap"
            x-text="$refs.{{ $fieldRef }}?.placeholder || ''"
        ></p>
    </div>
</div>
