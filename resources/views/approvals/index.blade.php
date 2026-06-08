<x-app-layout>
    @php
        // ---- Subscription banner colour logic ----
        $expires   = $subscription['expires_at'] ?? null;
        $isActive  = $subscription['active'] ?? false;
        $daysLeft  = $expires ? now()->startOfDay()->diffInDays($expires->copy()->startOfDay(), false) : null;
        $subColor  = !$isActive ? 'red' : (($daysLeft !== null && $daysLeft <= 7) ? 'amber' : 'emerald');
        $subCls = match ($subColor) {
            'red'   => ['border' => 'border-red-500/30',     'badge' => 'bg-red-500/15 text-red-400 border-red-500/30',         'accent' => 'text-red-400'],
            'amber' => ['border' => 'border-amber-500/30',   'badge' => 'bg-amber-500/15 text-amber-400 border-amber-500/30',   'accent' => 'text-amber-400'],
            default => ['border' => 'border-emerald-500/30', 'badge' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30', 'accent' => 'text-emerald-400'],
        };

        // ---- Split categories + items by section source ----
        $promoCats = collect($categories)->reject(fn ($c) => str_starts_with($c['key'], 'occ'))->values();
        $occCats   = collect($categories)->filter(fn ($c) => str_starts_with($c['key'], 'occ'))->values();
        $promoItems = $items->where('source', 'cgi')->values();
        $occItems   = $items->where('source', 'occasion')->values();

        // ---- Sidebar nav definition ----
        $nav = [
            ['key' => 'dashboard',    'label' => 'Dashboard',       'badge' => null,                  'd' => 'M4 5a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h6a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM14 13a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z'],
            ['key' => 'promotional',  'label' => 'Promotional Content', 'badge' => $promoCounts['pending'], 'd' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['key' => 'occasional',   'label' => 'Occasional Content',  'badge' => $occCounts['pending'],   'd' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ];
    @endphp

    <div x-data="{
            section: @js($section),
            cat: 'all',
            modalOpen: false,
            current: {},
            undoTick: Date.now(),
            undoTimer: null,
            go(s) {
                this.section = s;
                this.cat = 'all';
                const url = new URL(window.location.href);
                url.searchParams.set('section', s);
                if (s === 'dashboard') {
                    url.searchParams.delete('filter');
                }
                window.history.replaceState({}, '', url);
            },
            openReview(data) {
                this.current = data;
                this.modalOpen = true;
                this.startUndoTimer();
            },
            closeModal() {
                this.modalOpen = false;
                this.stopUndoTimer();
                document.querySelectorAll('video').forEach(v => v.pause());
            },
            startUndoTimer() {
                this.stopUndoTimer();
                this.undoTick = Date.now();
                this.undoTimer = setInterval(() => { this.undoTick = Date.now(); }, 1000);
            },
            stopUndoTimer() {
                if (this.undoTimer) clearInterval(this.undoTimer);
                this.undoTimer = null;
            },
            isPendingReview() {
                return !this.current.status || this.current.status === 'pending';
            },
            canUndoDecision() {
                if (!this.current.reviewed_at) return false;
                if (this.current.status !== 'approved' && this.current.status !== 'rejected') return false;
                const reviewed = new Date(this.current.reviewed_at).getTime();
                return (this.undoTick - reviewed) < 60000;
            },
            undoSecondsLeft() {
                if (!this.current.reviewed_at) return 0;
                const reviewed = new Date(this.current.reviewed_at).getTime();
                return Math.max(0, Math.ceil((60000 - (this.undoTick - reviewed)) / 1000));
            }
         }"
         class="max-w-screen-2xl mx-auto pt-5 pb-16 px-3 sm:px-6 lg:px-8 antialiased w-full">

        {{-- HEADER: client identity + section tabs --}}
        <header class="mb-6 space-y-5">
            <div class="flex items-center justify-between gap-3 sm:gap-4">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center text-white text-sm sm:text-base font-black shadow-lg shrink-0">
                        {{ strtoupper(substr($maker->name ?? 'C', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-[8px] font-black text-emerald-500 uppercase tracking-[0.25em]">Approval Center</div>
                        <h2 class="text-sm sm:text-lg font-black text-white tracking-tight truncate leading-tight">{{ $maker->name ?? 'Client' }}</h2>
                    </div>
                </div>
                <div class="flex items-center gap-2 px-2.5 sm:px-3 py-1.5 sm:py-2 bg-[#0c0c0e] border border-white/[0.06] rounded-xl shrink-0 max-w-[42%] sm:max-w-none">
                    <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-white/[0.06] flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-[7px] font-black text-gray-600 uppercase tracking-[0.2em] hidden sm:block">Signed in</div>
                        <div class="text-[10px] sm:text-[11px] font-bold text-gray-200 truncate max-w-[72px] min-[380px]:max-w-[88px] sm:max-w-[140px]">{{ auth()->user()->name }}</div>
                    </div>
                </div>
            </div>

            {{-- Section tabs --}}
            <nav class="flex items-center gap-1 p-1 bg-[#0c0c0e] border border-white/[0.06] rounded-2xl overflow-x-auto hide-scrollbar">
                @foreach($nav as $n)
                    <button type="button" @click="go('{{ $n['key'] }}')"
                            :class="section === '{{ $n['key'] }}' ? 'bg-white/[0.07] text-white shadow-sm' : 'text-gray-500 hover:text-gray-200'"
                            class="shrink-0 flex items-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl text-[11px] font-bold tracking-wide transition-all whitespace-nowrap">
                        <svg class="w-[18px] h-[18px] shrink-0" :class="section === '{{ $n['key'] }}' ? 'text-emerald-400' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $n['d'] }}"></path></svg>
                        <span>{{ $n['label'] }}</span>
                        @if($n['badge'])
                            <span class="px-1.5 py-0.5 rounded-full text-[9px] font-black bg-amber-500 text-black">{{ $n['badge'] }}</span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </header>

        <div class="space-y-6">

                {{-- Flash --}}
                @if(session('success'))
                    <div class="px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="px-4 py-3 bg-red-500/10 border border-red-500/20 text-red-500 text-[10px] font-black uppercase tracking-widest rounded-lg">{{ session('error') }}</div>
                @endif

                {{-- ===================================================== --}}
                {{-- SECTION: DASHBOARD (only this block when tab active)   --}}
                {{-- ===================================================== --}}
                <template x-if="section === 'dashboard'">
                <div class="space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white leading-none">Overview</h1>
                        @if($stats['pending'] > 0)
                            <button type="button" @click="go('{{ $promoCounts['pending'] >= $occCounts['pending'] ? 'promotional' : 'occasional' }}')"
                                    class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold rounded-xl transition-all">
                                Review {{ $stats['pending'] }} pending →
                            </button>
                        @endif
                    </div>

                    {{-- Subscription (left) + stats 2x2 (right) --}}
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-stretch">
                        <div class="md:col-span-4 lg:col-span-3">
                            @include('approvals.partials.subscription-card')
                        </div>
                        <div class="md:col-span-8 lg:col-span-9">
                            @include('approvals.partials.stat-cards', ['stats' => $stats])
                        </div>
                    </div>

                    {{-- Needs your attention --}}
                    <div class="bg-[#0c0c0e] border border-white/[0.06] rounded-2xl flex flex-col">
                            <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.06]">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 {{ $pendingPreview->count() ? 'animate-pulse' : '' }}"></span>
                                    <h3 class="text-[13px] font-bold text-white tracking-tight">Needs your attention</h3>
                                </div>
                                <span class="text-[11px] font-semibold text-gray-500 tabular-nums">{{ $stats['pending'] }} pending</span>
                            </div>

                            @if($pendingPreview->isEmpty())
                                <div class="flex-1 flex flex-col items-center justify-center text-center py-14 px-6">
                                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-4">
                                        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <p class="text-[14px] font-bold text-white">You're all caught up</p>
                                    <p class="text-[12px] text-gray-500 mt-1">No assets are waiting for your review right now.</p>
                                </div>
                            @else
                                <div class="divide-y divide-white/[0.05]">
                                    @foreach($pendingPreview as $p)
                                        @php
                                            $pPayload = [
                                                'source' => $p->source, 'genId' => $p->generation_id, 'url' => $p->media_url,
                                                'type' => $p->media_type, 'variant' => $p->variant, 'branded' => (bool) $p->is_branded,
                                                'product' => $p->product_name ?? 'Untitled Asset', 'status' => $p->status,
                                                'comment' => $p->comment, 'category' => $p->category_label,
                                                'reviewed_at' => $p->reviewed_at?->toIso8601String(),
                                            ];
                                        @endphp
                                        <button type="button" @click="openReview(@js($pPayload))"
                                                class="group w-full flex items-center gap-3 sm:gap-4 px-3 sm:px-5 py-3.5 hover:bg-white/[0.03] transition-colors text-left">
                                            <div class="relative w-14 h-10 sm:w-16 sm:h-12 rounded-lg overflow-hidden bg-black shrink-0">
                                                @if($p->media_type === 'video')
                                                    <video src="{{ $p->media_url }}" class="w-full h-full object-cover opacity-80" muted playsinline preload="metadata"></video>
                                                    <div class="absolute inset-0 flex items-center justify-center bg-black/30 pointer-events-none">
                                                        <svg class="w-4 h-4 text-white fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                @else
                                                    <img src="{{ $p->media_url }}" onerror="this.style.opacity=0" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[13px] font-semibold text-white truncate">{{ $p->product_name ?? 'Untitled Asset' }}</p>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-[10px] font-medium text-emerald-400/90">{{ $p->category_label }}</span>
                                                    <span class="w-1 h-1 rounded-full bg-gray-700"></span>
                                                    <span class="text-[10px] text-gray-500">{{ $p->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                            <span class="shrink-0 px-2.5 sm:px-3 py-1.5 bg-white/[0.05] group-hover:bg-emerald-600 group-hover:text-white border border-white/10 group-hover:border-emerald-500 text-gray-300 text-[9px] sm:text-[10px] font-bold rounded-lg uppercase tracking-wider transition-all">Review</span>
                                        </button>
                                    @endforeach
                                </div>
                                @if($stats['pending'] > $pendingPreview->count())
                                    <div class="px-5 py-3 border-t border-white/[0.06]">
                                        <button type="button" @click="go('{{ $promoCounts['pending'] >= $occCounts['pending'] ? 'promotional' : 'occasional' }}')"
                                                class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 transition-colors">
                                            View all {{ $stats['pending'] }} pending →
                                        </button>
                                    </div>
                                @endif
                            @endif
                    </div>
                </div>
                </template>

                {{-- ===================================================== --}}
                {{-- SECTION: PROMOTIONAL CONTENT (CGI only)               --}}
                {{-- ===================================================== --}}
                <template x-if="section === 'promotional'">
                <div class="space-y-5">
                    @include('approvals.partials.section-toolbar', [
                        'title'     => 'Promotional Content',
                        'subtitle'  => 'CGI Studio merged pictures & merged videos',
                        'counts'    => $promoCounts,
                        'cats'      => $promoCats,
                        'sectionId' => 'promotional',
                        'totalAll'  => $promoItems->count(),
                    ])

                    @if($promoItems->isEmpty())
                        @include('approvals.partials.empty', ['filter' => $filter])
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 sm:gap-6">
                            @foreach($promoItems as $item)
                                @include('approvals.partials.media-card', ['item' => $item])
                            @endforeach
                        </div>
                    @endif
                </div>
                </template>

                {{-- ===================================================== --}}
                {{-- SECTION: OCCASIONAL CONTENT (Occasion only)           --}}
                {{-- ===================================================== --}}
                <template x-if="section === 'occasional'">
                <div class="space-y-5">
                    @include('approvals.partials.section-toolbar', [
                        'title'     => 'Occasional Content',
                        'subtitle'  => 'Occasion Studio campaigns',
                        'counts'    => $occCounts,
                        'cats'      => $occCats,
                        'sectionId' => 'occasional',
                        'totalAll'  => $occItems->count(),
                    ])

                    @if($occItems->isEmpty())
                        @include('approvals.partials.empty', ['filter' => $filter])
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 sm:gap-6">
                            @foreach($occItems as $item)
                                @include('approvals.partials.media-card', ['item' => $item])
                            @endforeach
                        </div>
                    @endif
                </div>
                </template>

        </div>

        {{-- ============ VIEW & REVIEW MODAL ============ --}}
        <template x-teleport="body">
            <div x-show="modalOpen" x-cloak
                 class="fixed inset-0 z-[1000] flex items-center justify-center p-3 sm:p-6 bg-black/95 backdrop-blur-xl"
                 x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <div class="relative w-full max-w-6xl bg-[#0a0a0a] border border-white/10 rounded-3xl overflow-y-auto lg:overflow-hidden shadow-2xl flex flex-col lg:flex-row max-h-[94vh]"
                     @click.away="closeModal()">
                    <div class="shrink-0 lg:w-[58%] bg-black flex items-center justify-center min-h-[50vh] max-h-[55vh] lg:min-h-0 lg:max-h-[94vh] p-2">
                        <template x-if="current.type === 'video'">
                            <video :src="current.url" class="w-full max-h-[55vh] lg:max-h-[94vh] object-contain bg-black" controls playsinline></video>
                        </template>
                        <template x-if="current.type !== 'video'">
                            <img :src="current.url" class="w-full max-h-[55vh] lg:max-h-[94vh] object-contain bg-black" alt="Review asset">
                        </template>
                    </div>
                    <div class="lg:w-2/5 flex flex-col min-h-0">
                        <div class="shrink-0 px-5 sm:px-6 py-4 sm:py-5 border-b border-white/5 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="text-[8px] font-black text-emerald-500 uppercase tracking-[0.25em]" x-text="current.category || 'Review Asset'"></div>
                                <h3 class="text-base font-black text-white uppercase tracking-wider truncate mt-1" x-text="current.product"></h3>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="px-2 py-0.5 bg-white/5 border border-white/10 text-gray-300 text-[8px] font-black uppercase tracking-widest rounded" x-text="current.type"></span>
                                    <span class="px-2 py-0.5 bg-white/5 border border-white/10 text-gray-300 text-[8px] font-black uppercase tracking-widest rounded" x-text="current.variant"></span>
                                    <span class="px-2 py-0.5 text-[8px] font-black uppercase tracking-widest rounded"
                                          :class="current.status === 'approved' ? 'bg-emerald-600 text-white' : (current.status === 'rejected' ? 'bg-red-600 text-white' : 'bg-amber-500 text-black')"
                                          x-text="current.status"></span>
                                </div>
                            </div>
                            <button @click="closeModal()" class="text-gray-500 hover:text-white bg-white/5 hover:bg-white/10 rounded-lg p-2 transition-colors shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('approvals.review') }}" class="flex flex-col lg:flex-1 lg:min-h-0 lg:overflow-hidden">
                            @csrf
                            <input type="hidden" name="source" :value="current.source">
                            <input type="hidden" name="generation_id" :value="current.genId">
                            <input type="hidden" name="media_url" :value="current.url">
                            <input type="hidden" name="media_type" :value="current.type">
                            <input type="hidden" name="variant" :value="current.variant">
                            <input type="hidden" name="is_branded" :value="current.branded ? 1 : 0">
                            <div class="p-5 sm:p-6 space-y-3 lg:overflow-y-auto lg:flex-1">
                                <p class="text-[10px] text-gray-500 leading-relaxed">Review the full asset above before deciding. A note to the client is optional.</p>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest">Note to the user <span class="text-gray-600 font-bold normal-case">(optional)</span></label>
                                    <textarea name="comment" rows="4" x-model="current.comment" placeholder="e.g. Looks great — approved. / Please adjust the headline."
                                              :readonly="!isPendingReview()"
                                              class="mt-2 w-full bg-black border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:border-emerald-500 outline-none transition-all resize-none disabled:opacity-60"></textarea>
                                </div>
                                <p class="text-[8px] text-gray-600">The client will see this note when you approve or reject.</p>
                            </div>
                            <div class="shrink-0 px-5 sm:px-6 py-4 sm:py-5 border-t border-white/5 space-y-3">
                                {{-- Pending: approve or reject (mutually exclusive path) --}}
                                <div x-show="isPendingReview()" class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                                    <button type="submit" name="decision" value="approved"
                                            class="flex-1 py-3.5 sm:py-3 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black rounded-xl uppercase tracking-widest transition-all shadow-[0_0_18px_rgba(16,185,129,0.3)]">Approve</button>
                                    <button type="submit" name="decision" value="rejected"
                                            class="flex-1 py-3.5 sm:py-3 bg-red-600/15 hover:bg-red-600 border border-red-500/40 text-red-400 hover:text-white text-[10px] font-black rounded-xl uppercase tracking-widest transition-all">Reject</button>
                                </div>

                                {{-- Finalized: undo within 1 minute only --}}
                                <template x-if="!isPendingReview() && canUndoDecision()">
                                    <div class="space-y-2">
                                        <p class="text-[9px] text-amber-400/90 text-center font-semibold">
                                            You can undo this <span class="font-black" x-text="current.status"></span> decision for
                                            <span class="font-black tabular-nums" x-text="undoSecondsLeft()"></span>s
                                        </p>
                                        <button type="submit" name="decision" value="undo"
                                                class="w-full py-3 bg-amber-500/15 hover:bg-amber-500 border border-amber-500/40 text-amber-300 hover:text-black text-[10px] font-black rounded-xl uppercase tracking-widest transition-all">
                                            Undo decision
                                        </button>
                                    </div>
                                </template>

                                <template x-if="!isPendingReview() && !canUndoDecision()">
                                    <p class="text-center text-[10px] font-bold uppercase tracking-widest py-2"
                                       :class="current.status === 'approved' ? 'text-emerald-400' : 'text-red-400'">
                                        Decision is final — cannot switch to <span x-text="current.status === 'approved' ? 'reject' : 'approve'"></span>
                                    </p>
                                </template>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        [x-cloak]{display:none!important;}
        .hide-scrollbar::-webkit-scrollbar{display:none;}
        .hide-scrollbar{-ms-overflow-style:none;scrollbar-width:none;}
    </style>
</x-app-layout>
