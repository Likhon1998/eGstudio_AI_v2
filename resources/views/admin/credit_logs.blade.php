<x-app-layout>
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 antialiased">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-10">
            <div class="relative">
                <div class="absolute -inset-4 bg-purple-500/10 blur-2xl rounded-full z-0"></div>
                <div class="relative z-10">
                    <h1 class="text-3xl sm:text-4xl font-black text-white uppercase tracking-tight">Manual Top-Up Ledger</h1>
                    <p class="text-sm text-gray-400 font-medium tracking-wide mt-2">Audit trail of all manual credit injections</p>
                </div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="relative group px-6 py-2.5 bg-[#111] hover:bg-white text-gray-300 hover:text-black text-xs font-black uppercase tracking-widest rounded-xl transition-all duration-300 border border-white/10 hover:border-white shadow-xl">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Users
                </span>
            </a>
        </div>

        <div class="bg-[#0a0a0a] border border-white/10 rounded-3xl overflow-hidden shadow-[0_0_40px_rgba(0,0,0,0.5)] relative">
            
            @php
                $groupedLogs = $logs->groupBy('user_id');
            @endphp

            <div class="hidden lg:grid grid-cols-12 gap-6 px-8 py-5 bg-white/[0.02] border-b border-white/10 shadow-sm">
                <div class="col-span-5 text-[10px] uppercase tracking-widest text-gray-500 font-bold">Client Identity</div>
                <div class="col-span-3 text-[10px] uppercase tracking-widest text-gray-500 font-bold text-center">Volume</div>
                <div class="col-span-3 text-[10px] uppercase tracking-widest text-gray-500 font-bold text-right">Total Injection</div>
                <div class="col-span-1 text-[10px] uppercase tracking-widest text-gray-500 font-bold text-right">Action</div>
            </div>

            <div class="divide-y divide-white/[0.05]">
                @forelse($groupedLogs as $userId => $userLogs)
                    @php
                        $user = $userLogs->first()->user;
                        $totalAmount = $userLogs->sum('amount');
                        $transactionCount = $userLogs->count();
                    @endphp

                    <div class="group cursor-pointer hover:bg-white/[0.02] transition-all duration-300" onclick="toggleSubBranch('user-branch-{{ $userId }}', 'icon-{{ $userId }}')">
                        <div class="flex flex-col lg:grid lg:grid-cols-12 gap-4 lg:gap-6 px-6 lg:px-8 py-6 items-center relative">
                            
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-purple-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                            <div class="col-span-5 flex items-center w-full lg:w-auto gap-4">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-900/50 to-blue-900/50 border border-white/10 flex items-center justify-center shrink-0">
                                    <span class="text-sm font-black text-white">{{ substr($user->name ?? 'U', 0, 1) }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-white uppercase tracking-wider">{{ $user->name ?? 'Unknown Client' }}</span>
                                    <span class="text-xs text-gray-500 font-mono mt-0.5">{{ $user->email ?? 'No Email' }}</span>
                                </div>
                            </div>

                            <div class="col-span-3 flex w-full lg:justify-center mt-3 lg:mt-0">
                                <span class="lg:hidden text-[10px] text-gray-600 font-bold uppercase tracking-widest mr-auto self-center">Volume</span>
                                <span class="px-3 py-1.5 bg-[#111] border border-white/10 text-gray-300 text-[10px] font-black uppercase tracking-widest rounded-full shadow-inner flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                                    {{ $transactionCount }} Record{{ $transactionCount > 1 ? 's' : '' }}
                                </span>
                            </div>

                            <div class="col-span-3 flex w-full lg:justify-end mt-3 lg:mt-0 items-center">
                                <span class="lg:hidden text-[10px] text-gray-600 font-bold uppercase tracking-widest mr-auto self-center">Total Injected</span>
                                <span class="text-xl font-black text-emerald-400 drop-shadow-[0_0_10px_rgba(52,211,153,0.2)]">
                                    +{{ number_format($totalAmount) }}
                                </span>
                            </div>

                            <div class="col-span-1 flex w-full justify-end mt-3 lg:mt-0">
                                <div class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center border border-white/5 group-hover:bg-white/10 group-hover:border-white/20 transition-all">
                                    <svg id="icon-{{ $userId }}" class="w-4 h-4 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="user-branch-{{ $userId }}" class="hidden bg-[#050505] shadow-inner overflow-hidden">
                        <div class="pl-6 lg:pl-16 pr-6 lg:pr-8 py-4">
                            <div class="border-l border-white/10 ml-4 pl-4 lg:pl-8 py-2 space-y-4">
                                
                                @foreach($userLogs as $log)
                                    <div class="flex flex-col lg:flex-row justify-between lg:items-center p-4 bg-white/[0.02] border border-white/5 rounded-xl hover:bg-white/[0.04] transition-colors relative">
                                        
                                        <div class="absolute -left-[1.35rem] lg:-left-[2.35rem] top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-purple-500 border-2 border-[#050505]"></div>

                                        <div class="flex flex-col lg:flex-row gap-4 lg:gap-12 items-start lg:items-center">
                                            <div>
                                                <span class="block text-[11px] text-gray-300 font-mono">{{ $log->created_at->format('M d, Y') }}</span>
                                                <span class="block text-[9px] text-gray-500 mt-1 uppercase tracking-widest">{{ $log->created_at->format('h:i A') }}</span>
                                            </div>

                                            <div class="flex items-center gap-4 flex-wrap">
                                                @php
                                                    $walletType = $log->wallet_type ?? 'cgi';
                                                @endphp
                                                <span class="px-2 py-0.5 {{ $walletType === 'occasion' ? 'bg-pink-500/10 border-pink-500/20 text-pink-400' : 'bg-blue-500/10 border-blue-500/20 text-blue-400' }} border text-[8px] font-black uppercase tracking-widest rounded">
                                                    {{ $walletType === 'occasion' ? 'Occasion' : 'CGI' }}
                                                </span>
                                                <span class="px-2.5 py-1 bg-purple-500/10 border border-purple-500/20 text-purple-400 text-[9px] font-black uppercase tracking-widest rounded-md">
                                                    {{ str_replace('_', ' ', $log->credit_type) }}
                                                </span>
                                                <span class="text-sm font-black text-emerald-400">+{{ $log->amount }}</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-col lg:flex-row gap-4 lg:gap-8 items-start lg:items-center mt-4 lg:mt-0">
                                            <div class="max-w-[200px]">
                                                <p class="text-[10px] text-gray-400 font-medium leading-relaxed truncate" title="{{ $log->billing_note }}">
                                                    "{{ $log->billing_note ?: 'No specific reason provided' }}"
                                                </p>
                                            </div>

                                            <div class="flex items-center gap-2 px-3 py-1.5 bg-black/50 rounded-lg border border-white/5">
                                                <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ $log->admin->name ?? 'System' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-32 flex flex-col items-center justify-center text-center px-4">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 bg-purple-500/20 blur-xl rounded-full"></div>
                            <div class="w-16 h-16 rounded-2xl bg-[#111] border border-white/10 flex items-center justify-center relative z-10 shadow-2xl">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-black text-white uppercase tracking-widest mb-2">No Ledger Records</h3>
                        <p class="text-xs text-gray-500 font-medium max-w-sm">There are no manual credit injections recorded in the database yet. Transactions will appear here once authorized.</p>
                    </div>
                @endforelse
            </div>
            
        </div>
    </div>

    <script>
        function toggleSubBranch(branchId, iconId) {
            const branch = document.getElementById(branchId);
            const icon = document.getElementById(iconId);
            
            if (branch.classList.contains('hidden')) {
                branch.classList.remove('hidden');
                // Adds a tiny fade-in effect to the dropdown
                branch.style.opacity = '0';
                setTimeout(() => branch.style.opacity = '1', 10);
                icon.style.transform = 'rotate(180deg)';
            } else {
                branch.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
    </script>
</x-app-layout>