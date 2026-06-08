{{--
    Maker studio — Approval History tab.
    Expects: $approvalHistory, $accent ('blue'|'pink')
--}}
@php
    $accent = $accent ?? 'blue';
    $activeTab = $accent === 'pink'
        ? 'bg-pink-600/15 text-pink-400 border-pink-500/30'
        : 'bg-blue-600/15 text-blue-400 border-blue-500/30';
    $histFilterIdle = 'text-gray-500 hover:text-gray-300 border-transparent';
    $stats = $approvalHistory['stats'] ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
@endphp

<div x-data="{ histFilter: @js(($stats['pending'] ?? 0) > 0 ? 'pending' : (($stats['approved'] ?? 0) > 0 ? 'approved' : 'rejected')) }" class="p-4 sm:p-6 space-y-5">
    @if(!empty($approvalHistory['approver_name']))
        <p class="text-[10px] text-gray-500 font-semibold">
            Reviewed by <span class="text-gray-300 font-bold">{{ $approvalHistory['approver_name'] }}</span>
            — merged pictures & videos
        </p>
    @else
        <p class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider">
            Merged pictures and videos sent to your approver for review
        </p>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <button type="button" @click="histFilter = 'pending'"
                :class="histFilter === 'pending' ? '{{ $activeTab }}' : '{{ $histFilterIdle }}'"
                class="rounded-xl border p-4 text-left transition-all">
            <div class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Awaiting approval</div>
            <div class="text-2xl font-black text-amber-400 mt-1 tabular-nums">{{ $stats['pending'] }}</div>
        </button>
        <button type="button" @click="histFilter = 'approved'"
                :class="histFilter === 'approved' ? '{{ $activeTab }}' : '{{ $histFilterIdle }}'"
                class="rounded-xl border p-4 text-left transition-all">
            <div class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Approved</div>
            <div class="text-2xl font-black text-emerald-400 mt-1 tabular-nums">{{ $stats['approved'] }}</div>
        </button>
        <button type="button" @click="histFilter = 'rejected'"
                :class="histFilter === 'rejected' ? '{{ $activeTab }}' : '{{ $histFilterIdle }}'"
                class="rounded-xl border p-4 text-left transition-all">
            <div class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Rejected</div>
            <div class="text-2xl font-black text-red-400 mt-1 tabular-nums">{{ $stats['rejected'] }}</div>
        </button>
        <div class="rounded-xl border border-white/[0.06] bg-white/[0.02] p-4">
            <div class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Total merged assets</div>
            <div class="text-2xl font-black text-white mt-1 tabular-nums">{{ $stats['total'] }}</div>
        </div>
    </div>

    <div class="bg-[#0a0a0a] border border-white/[0.06] rounded-xl overflow-hidden min-h-[200px]">
        {{-- Pending --}}
        <div x-show="histFilter === 'pending'" x-cloak>
            @if(($approvalHistory['pending'] ?? collect())->isEmpty())
                <div class="py-12 text-center text-[11px] text-gray-500 font-semibold">No merged assets are waiting for approval.</div>
            @else
                <div class="divide-y divide-white/[0.05]">
                    @foreach($approvalHistory['pending'] as $item)
                        @include('partials.approval-history-row', ['item' => $item])
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Approved --}}
        <div x-show="histFilter === 'approved'" x-cloak>
            @if(($approvalHistory['approved'] ?? collect())->isEmpty())
                <div class="py-12 text-center text-[11px] text-gray-500 font-semibold">Nothing has been approved yet.</div>
            @else
                <div class="divide-y divide-white/[0.05]">
                    @foreach($approvalHistory['approved'] as $item)
                        @include('partials.approval-history-row', ['item' => $item])
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Rejected --}}
        <div x-show="histFilter === 'rejected'" x-cloak>
            @if(($approvalHistory['rejected'] ?? collect())->isEmpty())
                <div class="py-12 text-center text-[11px] text-gray-500 font-semibold">Nothing has been rejected.</div>
            @else
                <div class="divide-y divide-white/[0.05]">
                    @foreach($approvalHistory['rejected'] as $item)
                        @include('partials.approval-history-row', ['item' => $item])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if(($stats['total'] ?? 0) === 0)
        <p class="text-center text-[11px] text-gray-500 py-2">
            Complete <strong class="text-gray-400">Merge Template</strong> on a picture or video to send it for approval.
        </p>
    @endif
</div>
