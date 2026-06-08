{{-- Studio top tabs. Expects: $accent, optional $approvalHistory, $showPostHistoryTab, $postHistoryStats --}}
@php
    $accent = $accent ?? 'blue';
    $pendingCount = $approvalHistory['stats']['pending'] ?? 0;
    $showPostHistoryTab = $showPostHistoryTab ?? false;
    $showApprovalTab = $showApprovalTab ?? (($approvalHistory['stats']['total'] ?? 0) > 0);
    $postHistoryTotal = $postHistoryStats['total'] ?? 0;
    $tabActive = $accent === 'pink'
        ? 'bg-pink-600/10 text-pink-400 border-pink-500/20'
        : 'bg-blue-600/10 text-blue-400 border-blue-500/20';
    $tabIdle = 'text-gray-500 hover:text-gray-300 border-transparent';
@endphp
<div class="flex items-center gap-1 px-4 sm:px-6 pt-4 border-b border-white/5 bg-[#0a0a0a]">
    <button type="button" @click="studioTab = 'directives'"
            :class="studioTab === 'directives' ? '{{ $tabActive }}' : '{{ $tabIdle }}'"
            class="px-4 py-2.5 rounded-t-lg border border-b-0 text-[10px] font-black uppercase tracking-widest transition-all">
        Directives
    </button>
    @if($showPostHistoryTab)
        <button type="button" @click="studioTab = 'posts'"
                :class="studioTab === 'posts' ? '{{ $tabActive }}' : '{{ $tabIdle }}'"
                class="px-4 py-2.5 rounded-t-lg border border-b-0 text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
            Post History
            @if($postHistoryTotal > 0)
                <span class="px-1.5 py-0.5 rounded-full {{ $accent === 'pink' ? 'bg-pink-500' : 'bg-indigo-500' }} text-black text-[8px] font-black">{{ $postHistoryTotal }}</span>
            @endif
        </button>
    @endif
    @if($showApprovalTab)
        <button type="button" @click="studioTab = 'approval'"
                :class="studioTab === 'approval' ? '{{ $tabActive }}' : '{{ $tabIdle }}'"
                class="px-4 py-2.5 rounded-t-lg border border-b-0 text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
            Approval History
            @if($pendingCount > 0)
                <span class="px-1.5 py-0.5 rounded-full bg-amber-500 text-black text-[8px] font-black">{{ $pendingCount }}</span>
            @endif
        </button>
    @endif
</div>
