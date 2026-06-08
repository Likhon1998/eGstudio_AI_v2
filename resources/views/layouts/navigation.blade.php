<nav x-data="{ open: false }" class="h-16 bg-gray-900/80 backdrop-blur-lg border-b border-gray-800 flex items-center justify-between px-6 sticky top-0 z-50">
    
    {{-- Brand (always visible when sidebar is hidden for approvers) --}}
    @if(auth()->check() && auth()->user()->isApprover())
        <div class="flex items-center gap-3 min-w-0">
            <span class="text-xl font-bold tracking-tighter text-white shrink-0">eGStudio<span class="text-blue-500">AI</span></span>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-[9px] font-black text-emerald-400 uppercase tracking-widest">
                Approval Center
            </span>
        </div>
    @else
        <div class="flex items-center sm:hidden">
            <span class="text-xl font-bold tracking-tighter text-white">eGStudio_<span class="text-blue-500">AI</span></span>
        </div>
        <div class="hidden sm:block"></div>
    @endif

    {{-- Right Side Icons & Profile --}}
    <div class="flex items-center gap-2 sm:gap-4">
        
        {{-- ========================================== --}}
        {{-- ONLY SHOW TO ADMINS                        --}}
        {{-- ========================================== --}}
        @if(auth()->check() && auth()->user()->role === 'admin')
            
            @php
                $pendingApprovals = \Illuminate\Support\Facades\Cache::remember(
                    'admin.billing.pending_notifications',
                    60,
                    fn () => \App\Models\Billing::with('user', 'package')
                        ->where('status', 'due')
                        ->whereNotNull('payment_proof')
                        ->latest()
                        ->limit(10)
                        ->get()
                );

                $approvalCount = \Illuminate\Support\Facades\Cache::remember(
                    'admin.billing.pending_count',
                    60,
                    fn () => \App\Models\Billing::where('status', 'due')
                        ->whereNotNull('payment_proof')
                        ->count()
                );
            @endphp

            <div class="relative" x-data="{ openNotifications: false }" @click.away="openNotifications = false">
                
                {{-- Bell Icon Button --}}
                <button @click="openNotifications = !openNotifications" class="relative p-2 text-gray-400 hover:text-white transition-colors rounded-full hover:bg-gray-800 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    
                    {{-- Pulsing Red Badge --}}
                    @if($approvalCount > 0)
                        <span class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500 border border-gray-900"></span>
                        </span>
                    @endif
                </button>

                {{-- Dropdown Menu --}}
                <div x-show="openNotifications" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                     class="absolute right-0 mt-3 w-80 bg-gray-900 border border-gray-700 rounded-xl shadow-2xl z-50 overflow-hidden"
                     style="display: none;">
                     
                     {{-- Header --}}
                     <div class="px-4 py-3 border-b border-gray-800 bg-gradient-to-r from-orange-500/10 to-transparent flex justify-between items-center">
                         <span class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Action Required</span>
                         @if($approvalCount > 0)
                             <span class="px-2 py-0.5 bg-red-500/20 text-red-400 border border-red-500/30 rounded text-[9px] font-black tracking-widest uppercase">
                                {{ $approvalCount }} New
                             </span>
                         @endif
                     </div>

                     {{-- Notification List --}}
                     <div class="max-h-80 overflow-y-auto divide-y divide-gray-800 custom-scrollbar">
                         @forelse($pendingApprovals as $req)
                             <a href="{{ route('admin.billings.requests') }}" class="block p-4 hover:bg-gray-800 transition-colors group">
                                 <div class="flex items-start gap-3">
                                     <div class="w-8 h-8 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500 flex-shrink-0 group-hover:scale-110 transition-transform">
                                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                     </div>
                                     <div>
                                         <p class="text-[11px] font-bold text-white mb-0.5">Proof Uploaded</p>
                                         <p class="text-[10px] text-gray-400 leading-tight">
                                             <span class="text-blue-400 font-bold">{{ $req->user->name }}</span> submitted payment for <span class="text-gray-200">{{ $req->package->name }}</span>.
                                         </p>
                                         <p class="text-[8px] text-gray-500 font-mono mt-1.5 uppercase tracking-wider">{{ $req->updated_at->diffForHumans() }}</p>
                                     </div>
                                 </div>
                             </a>
                         @empty
                             <div class="p-8 text-center flex flex-col items-center">
                                 <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center mb-3 text-gray-500 border border-gray-700">
                                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"></path></svg>
                                 </div>
                                 <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Inbox Zero</p>
                                 <p class="text-[9px] text-gray-500 font-bold uppercase mt-1">No pending approvals right now.</p>
                             </div>
                         @endforelse
                     </div>
                     
                     {{-- Footer Link --}}
                     <a href="{{ route('admin.billings.requests') }}" class="block w-full px-4 py-2.5 bg-gray-950 hover:bg-gray-800 text-center text-[9px] font-black text-gray-500 hover:text-white uppercase tracking-widest transition-colors border-t border-gray-800">
                         View All Requests
                     </a>
                </div>
            </div>
        @endif
        {{-- END ADMIN ONLY SECTION --}}

        {{-- ========================================== --}}
        {{-- USER PROFILE DROPDOWN                      --}}
        {{-- ========================================== --}}
        <x-dropdown align="right" width="w-52">
            <x-slot name="trigger">
                <button type="button" class="flex items-center gap-2.5 pl-1 pr-2 py-1 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 border border-transparent hover:border-white/10 transition-all focus:outline-none">
                    <div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center border border-white/10 shrink-0">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <span class="hidden sm:block text-[11px] font-bold uppercase tracking-widest text-gray-300 max-w-[140px] truncate">{{ Auth::user()->name }}</span>
                    <svg class="w-3.5 h-3.5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-3 border-b border-white/5">
                    <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Signed in as</p>
                    <p class="text-[11px] font-bold text-white truncate mt-0.5">{{ Auth::user()->name }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();"
                        class="!text-red-400 hover:!bg-red-500/10 hover:!text-red-300 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</nav>

{{-- Styling for the scrollbar and Alpine Cloak --}}
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #4B5563; }
    [x-cloak] { display: none !important; }
</style>