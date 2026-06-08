{{--
    $imageUrl, $name, $createdAt (Carbon|string)
    $editUrl (nullable), $destroyUrl (required)
    $accent: blue | orange | emerald
    $objectFit: cover | contain (default cover)
    $useCheckerboard: bool (default false)
--}}
@php
    $accent = $accent ?? 'blue';
    $objectFit = $objectFit ?? 'cover';
    $useCheckerboard = $useCheckerboard ?? false;
    $fileMissing = $fileMissing ?? false;
    $accentMap = [
        'blue' => ['border' => 'hover:border-blue-500/30', 'btn' => 'bg-blue-600 hover:bg-blue-500'],
        'orange' => ['border' => 'hover:border-orange-500/30', 'btn' => 'bg-orange-600 hover:bg-orange-500'],
        'emerald' => ['border' => 'hover:border-emerald-500/30', 'btn' => 'bg-emerald-600 hover:bg-emerald-500'],
    ];
    $a = $accentMap[$accent] ?? $accentMap['blue'];
    $checkerboard = "bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMWExYTFhIj48L3JlY3Q+CjxyZWN0IHg9IjQiIHk9IjQiIHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiMxYTFhMWEiPjwvcmVjdD4KPHJlY3QgeD0iNCIgd2lkdGg9IjQiIGhlaWdodD0iNCIgZmlsbD0iIzExMSI+PC9yZWN0Pgo8cmVjdCB5PSI0IiB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMTExIj48L3JlY3Q+Cjwvc3ZnPg==')]";
@endphp

<div class="bg-[#0a0a0a] border border-white/5 rounded-xl overflow-hidden group {{ $a['border'] }} transition-all">
    <div class="aspect-square relative overflow-hidden {{ $useCheckerboard ? $checkerboard . ' p-3' : 'bg-[#111]' }} cursor-zoom-in"
        @if(!$fileMissing) @click="openPreview(@js($imageUrl), @js($name), @js($editUrl ?? null))" @endif
        title="{{ $fileMissing ? 'Image file not found on server — edit and re-upload' : 'Click to preview full size' }}"
        @class(['cursor-zoom-in' => !$fileMissing, 'cursor-default' => $fileMissing])>
        @if($fileMissing)
            <div class="w-full h-full flex flex-col items-center justify-center gap-2 p-4 text-center bg-[#111] border border-dashed border-amber-500/30">
                <svg class="w-8 h-8 text-amber-500/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span class="text-[8px] font-black text-amber-400 uppercase tracking-widest leading-relaxed">File Missing<br>Re-upload Image</span>
            </div>
        @else
            <img src="{{ $imageUrl }}" alt="{{ $name }}"
                class="w-full h-full {{ $objectFit === 'contain' ? 'object-contain' : 'object-cover' }} opacity-90 group-hover:opacity-100 group-hover:scale-[1.02] transition-all duration-500"
                loading="lazy"
                onerror="this.classList.add('hidden'); this.nextElementSibling?.classList.remove('hidden');">
            <div class="hidden w-full h-full flex-col items-center justify-center gap-2 p-4 text-center bg-[#111] border border-dashed border-amber-500/30">
                <svg class="w-8 h-8 text-amber-500/70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span class="text-[8px] font-black text-amber-400 uppercase tracking-widest leading-relaxed">File Missing<br>Re-upload Image</span>
            </div>
        @endif

        @unless($fileMissing)
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none flex items-end justify-center pb-3">
            <span class="text-[9px] font-black text-white uppercase tracking-widest bg-black/60 px-2.5 py-1 rounded-md backdrop-blur-sm">Click to Preview</span>
        </div>
        @endunless

        <div class="absolute top-2 right-2 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity z-10" @click.stop>
            @unless($fileMissing)
            <button type="button"
                @click="openPreview(@js($imageUrl), @js($name), @js($editUrl ?? null))"
                class="p-2 {{ $a['btn'] }} text-white rounded-lg shadow-lg transition-colors"
                title="Preview">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            </button>
            @endunless
            @if(!empty($editUrl))
                <a href="{{ $editUrl }}"
                    class="p-2 {{ $a['btn'] }} text-white rounded-lg shadow-lg transition-colors"
                    title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </a>
            @endif
            <form action="{{ $destroyUrl }}" method="POST" @submit.prevent="formToSubmit = $event.target; deleteModal = true;">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-2 bg-red-600 hover:bg-red-500 text-white rounded-lg shadow-lg transition-colors" title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </form>
        </div>
    </div>
    <div class="p-3 border-t border-white/5">
        <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest truncate">{{ $name }}</p>
        <p class="text-[8px] text-gray-600 font-mono mt-1">{{ $createdAt instanceof \Illuminate\Support\Carbon ? $createdAt->diffForHumans() : $createdAt }}</p>
    </div>
</div>
