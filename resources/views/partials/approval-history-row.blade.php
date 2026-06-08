@php
    $statusCls = match ($item->status) {
        'approved' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
        'rejected' => 'bg-red-500/15 text-red-400 border-red-500/30',
        default    => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
    };
@endphp
<div class="flex flex-col sm:flex-row gap-4 p-4 hover:bg-white/[0.02] transition-colors">
    <div class="relative w-full sm:w-28 h-20 rounded-lg overflow-hidden bg-black shrink-0">
        @if($item->media_type === 'video')
            <video src="{{ $item->media_url }}" class="w-full h-full object-cover opacity-80" muted playsinline preload="metadata"></video>
        @else
            <img src="{{ $item->media_url }}" class="w-full h-full object-cover opacity-80" alt="">
        @endif
    </div>
    <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h4 class="text-[12px] font-bold text-white truncate">{{ $item->product_name ?? 'Untitled' }}</h4>
            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider border {{ $statusCls }}">{{ $item->status }}</span>
            <span class="text-[8px] text-gray-500 font-bold uppercase">{{ $item->category_label }} · {{ $item->media_type }}</span>
        </div>
        @if($item->reviewed_at)
            <p class="text-[9px] text-gray-600 mt-1">Reviewed {{ $item->reviewed_at->diffForHumans() }}</p>
        @else
            <p class="text-[9px] text-gray-600 mt-1">Created {{ $item->created_at->diffForHumans() }}</p>
        @endif
        @if(filled($item->comment))
            <p class="mt-2 text-[11px] italic leading-relaxed {{ $item->status === 'rejected' ? 'text-red-300/90' : 'text-emerald-300/90' }}">
                <span class="not-italic font-bold text-[9px] uppercase text-gray-500">Approver note:</span>
                “{{ $item->comment }}”
            </p>
        @elseif(in_array($item->status, ['approved', 'rejected'], true))
            <p class="mt-2 text-[10px] text-gray-600 italic">No note from approver.</p>
        @else
            <p class="mt-2 text-[10px] text-amber-400/80">Waiting for your approver to review this asset.</p>
        @endif
    </div>
</div>
