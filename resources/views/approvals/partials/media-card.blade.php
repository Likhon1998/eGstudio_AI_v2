@php
    $payload = [
        'source'      => $item->source,
        'genId'       => $item->generation_id,
        'url'         => $item->media_url,
        'type'        => $item->media_type,
        'variant'     => $item->variant,
        'branded'     => (bool) $item->is_branded,
        'product'     => $item->product_name ?? 'Untitled Asset',
        'status'      => $item->status,
        'comment'     => $item->comment,
        'category'    => $item->category_label,
        'reviewed_at' => $item->reviewed_at?->toIso8601String(),
    ];
@endphp
<div x-show="cat === 'all' || cat === '{{ $item->category_key }}'"
     class="group bg-[#0a0a0a] border border-white/10 rounded-2xl overflow-hidden shadow-2xl flex flex-col hover:border-white/20 transition-all">

    {{-- Preview — opens full review modal (approve/reject only there) --}}
    <div class="relative aspect-video bg-black cursor-pointer overflow-hidden" @click="openReview(@js($payload))">
        @if($item->media_type === 'video')
            <video src="{{ $item->media_url }}" class="w-full h-full object-cover opacity-60 group-hover:opacity-90 transition-all" muted playsinline preload="metadata"></video>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-12 h-12 bg-white/10 backdrop-blur border border-white/20 text-white rounded-full flex items-center justify-center"><svg class="w-5 h-5 fill-current ml-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
            </div>
        @else
            <img src="{{ $item->media_url }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500">
        @endif

        <span class="absolute top-3 left-3 px-2 py-1 bg-black/70 backdrop-blur border border-white/10 text-[8px] font-black text-emerald-300 uppercase tracking-widest rounded">
            {{ $item->category_label }}
        </span>
        <span class="absolute top-3 right-3 px-2 py-1 text-[8px] font-black uppercase tracking-widest rounded
            @if($item->status === 'approved') bg-emerald-600 text-white
            @elseif($item->status === 'rejected') bg-red-600 text-white
            @else bg-amber-500 text-black @endif">
            {{ $item->status }}
        </span>

        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center">
            <span class="px-4 py-2 bg-white text-black text-[9px] font-black uppercase tracking-widest rounded-lg">View &amp; Review</span>
        </div>
    </div>

    <div class="p-4 sm:p-5 flex flex-col flex-1 gap-3">
        <div>
            <h3 class="text-[12px] font-black text-white uppercase tracking-wider truncate">{{ $item->product_name ?? 'Untitled Asset' }}</h3>
            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-1">Created {{ $item->created_at->diffForHumans() }}</p>
        </div>

        @if($item->comment)
            <p class="text-[9px] italic leading-relaxed
                {{ $item->status === 'rejected' ? 'text-red-300/80' : 'text-emerald-300/80' }}">“{{ \Illuminate\Support\Str::limit($item->comment, 90) }}”</p>
        @endif

        <button type="button" @click="openReview(@js($payload))"
                class="mt-auto w-full py-2.5 bg-emerald-600/15 hover:bg-emerald-600 border border-emerald-500/30 hover:border-emerald-500 text-emerald-400 hover:text-white text-[9px] font-black rounded-lg uppercase tracking-widest transition-all">
            Open full review
        </button>
    </div>
</div>
