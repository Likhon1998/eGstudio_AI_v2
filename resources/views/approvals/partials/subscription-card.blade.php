{{-- Compact vertical subscription card (left column). --}}
<div class="bg-[#0c0c0e] border {{ $subCls['border'] }} rounded-2xl p-5 h-full flex flex-col">
    <div class="flex items-start justify-between gap-2">
        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Client Subscription</span>
        <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider border shrink-0 {{ $subCls['badge'] }}">{{ $isActive ? 'Active' : 'Expired' }}</span>
    </div>
    <div class="text-lg font-black text-white mt-3 tracking-tight leading-tight">{{ $subscription['package'] ?? 'No Plan' }}</div>
    <p class="text-[11px] text-gray-500 mt-1">Client: <span class="text-gray-300 font-semibold">{{ $maker->name ?? '—' }}</span></p>
    <div class="mt-auto pt-5 border-t border-white/[0.06] flex items-center gap-3">
        <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $subCls['badge'] }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </span>
        <div class="min-w-0">
            @if($expires)
                <div class="text-[13px] font-bold text-white">{{ $expires->format('M d, Y') }}</div>
                <div class="text-[10px] font-semibold {{ $subCls['accent'] }}">
                    @if(!$isActive) Expired {{ $expires->diffForHumans() }}
                    @elseif($daysLeft !== null) {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }} remaining @endif
                </div>
            @else
                <div class="text-[13px] font-bold text-gray-400">No active subscription</div>
            @endif
        </div>
    </div>
</div>
