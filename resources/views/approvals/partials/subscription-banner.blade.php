{{-- Client subscription summary for approvers. Expects: $subscription, $subCls, $expires, $isActive, $daysLeft --}}
<div class="bg-[#0c0c0e] border {{ $subCls['border'] }} rounded-2xl p-5 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="min-w-0">
            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Client Subscription</span>
            <div class="text-xl sm:text-2xl font-black text-white mt-1 tracking-tight">{{ $subscription['package'] ?? 'No Plan' }}</div>
            <p class="text-[12px] text-gray-500 mt-1">Client: <span class="text-gray-300 font-semibold">{{ $maker->name ?? '—' }}</span></p>
        </div>
        <span class="self-start sm:self-center px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $subCls['badge'] }}">{{ $isActive ? 'Active' : 'Expired' }}</span>
    </div>
    <div class="mt-4 pt-4 border-t border-white/[0.06] flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $subCls['badge'] }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </span>
        <div>
            @if($expires)
                <div class="text-[14px] font-bold text-white">{{ $expires->format('M d, Y') }}</div>
                <div class="text-[11px] font-semibold {{ $subCls['accent'] }}">
                    @if(!$isActive) Expired {{ $expires->diffForHumans() }}
                    @elseif($daysLeft !== null) {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }} remaining @endif
                </div>
            @else
                <div class="text-[14px] font-bold text-gray-400">No active subscription</div>
            @endif
        </div>
    </div>
</div>
