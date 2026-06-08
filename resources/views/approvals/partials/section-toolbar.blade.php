{{-- Header + status filter + category filter for a section.
     Expects: $title, $subtitle, $counts, $cats, $sectionId, $totalAll, $filter --}}
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h2 class="text-base sm:text-lg font-black text-white uppercase tracking-wider truncate">{{ $title }}</h2>
            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">{!! $subtitle !!}</p>
        </div>

        {{-- Status filter — horizontal scroll on narrow screens --}}
        <div class="w-full sm:w-auto -mx-1 px-1 overflow-x-auto hide-scrollbar">
            <div class="inline-flex min-w-full sm:min-w-0 items-center bg-white/5 p-1 rounded-xl border border-white/5">
                @php
                    $statusTabs = [
                        'pending'  => ['Awaiting', 'bg-amber-600 text-white shadow-lg',   $counts['pending']],
                        'approved' => ['Approved', 'bg-emerald-600 text-white shadow-lg', $counts['approved']],
                        'rejected' => ['Rejected', 'bg-red-600 text-white shadow-lg',     $counts['rejected']],
                    ];
                @endphp
                @foreach($statusTabs as $key => $meta)
                    <a href="{{ route('approvals.index', ['filter' => $key, 'section' => $sectionId]) }}"
                       class="shrink-0 flex-1 sm:flex-none px-3 sm:px-4 py-2.5 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap
                              {{ $filter === $key ? $meta[1] : 'text-gray-500 hover:text-gray-300' }}">
                        {{ $meta[0] }}
                        <span class="px-1.5 py-0.5 rounded-full text-[8px] {{ $filter === $key ? 'bg-black/20' : 'bg-white/10' }}">{{ $meta[2] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Category filter — scroll row on mobile, wrap on larger screens --}}
    @if($cats->count() > 0)
        <div class="-mx-1 px-1 overflow-x-auto hide-scrollbar sm:overflow-visible">
            <div class="flex flex-nowrap sm:flex-wrap items-center gap-2 min-w-min sm:min-w-0 pb-0.5 sm:pb-0">
                <button type="button" @click="cat = 'all'"
                        :class="cat === 'all' ? 'bg-white text-black border-white' : 'bg-white/5 text-gray-400 hover:text-white border-white/10'"
                        class="shrink-0 px-4 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg border transition-all flex items-center gap-1.5 whitespace-nowrap">
                    All <span class="opacity-60">{{ $totalAll }}</span>
                </button>
                @foreach($cats as $c)
                    <button type="button" @click="cat = '{{ $c['key'] }}'"
                            :class="cat === '{{ $c['key'] }}' ? 'bg-emerald-600 text-white border-emerald-500 shadow-lg' : 'bg-white/5 text-gray-400 hover:text-white border-white/10'"
                            class="shrink-0 px-4 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg border transition-all flex items-center gap-1.5 whitespace-nowrap">
                        {{ $c['label'] }} <span class="opacity-60">{{ $c['count'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
