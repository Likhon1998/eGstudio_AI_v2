<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto space-y-8 antialiased">
        
        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between border-b border-white/10 pb-6 gap-4">
            <div>
                <h1 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-red-500 tracking-[0.2em] uppercase">
                    Activation Requests
                </h1>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500 shadow-[0_0_8px_#f97316] animate-pulse"></span>
                    Manage Pending User Subscriptions & Payments
                </p>
            </div>
            
            <a href="{{ route('admin.packages.index') }}" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">
                Back to Packages
            </a>
        </div>

        {{-- SUCCESS NOTIFICATION --}}
        @if(session('success'))
            <div class="px-5 py-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-black uppercase tracking-widest rounded-xl flex items-center gap-3 shadow-[0_0_20px_rgba(16,185,129,0.1)]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- REQUESTS PANEL --}}
        <div class="bg-[#0a0a0a] border border-orange-500/20 rounded-2xl shadow-[0_0_30px_rgba(249,115,22,0.05)] overflow-hidden relative">
            
            <div class="px-6 sm:px-8 py-5 border-b border-white/5 bg-gradient-to-r from-orange-500/10 to-transparent flex items-center justify-between">
                <h2 class="text-xs font-black text-orange-500 uppercase tracking-widest flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-orange-500"></span>
                    </span>
                    Pending Clearances
                </h2>
            </div>
            
            {{-- Keeping the inline logic exactly as you requested to avoid breaking anything --}}
            @php 
                $pendingRequests = \App\Models\Billing::where('status', 'due')->with('user', 'package')->latest()->get(); 
            @endphp
            
            <div class="p-6 sm:p-8">
                <div class="space-y-4">
                    @forelse($pendingRequests as $req)
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between p-5 bg-[#111] border border-white/5 rounded-xl hover:border-orange-500/30 transition-all gap-6">
                            
                            {{-- User & Invoice Details --}}
                            <div class="w-full lg:w-1/3">
                                <p class="text-white text-sm font-bold flex items-center gap-3 mb-1.5">
                                    {{ $req->user->name }}
                                    <span class="text-[9px] text-gray-400 font-mono bg-white/5 border border-white/10 px-2 py-0.5 rounded">{{ $req->user->email }}</span>
                                </p>
                                <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <span>Inv: <span class="text-white font-mono">#{{ $req->invoice_no }}</span></span>
                                    <span class="text-gray-700">•</span>
                                    <span>Plan: <span class="text-blue-400">{{ $req->package->name }}</span></span>
                                    <span class="text-gray-700">•</span>
                                    <span>Amount: <span class="text-emerald-400">${{ $req->amount }}</span></span>
                                </p>
                            </div>

                            {{-- PAYMENT PROOF DISPLAY --}}
                            <div class="w-full lg:w-1/3 flex lg:justify-center">
                                @if($req->payment_proof)
                                    <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-lg px-4 py-3 w-full sm:w-auto flex flex-col justify-center shadow-inner">
                                        <p class="text-[9px] text-emerald-400 font-black uppercase tracking-widest mb-1.5 flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            User Submitted Proof
                                        </p>
                                        <p class="text-[9px] text-gray-400 font-mono mb-2 uppercase">Txn: <span class="text-gray-300">{{ $req->transaction_id }}</span></p>
                                        <a href="{{ Storage::url($req->payment_proof) }}" target="_blank" class="text-[9px] font-black text-white hover:text-emerald-300 transition-colors flex items-center justify-center gap-1.5 bg-emerald-500/20 hover:bg-emerald-500/40 px-3 py-1.5 rounded-md border border-emerald-500/30">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            View Screenshot
                                        </a>
                                    </div>
                                @else
                                    <div class="flex items-center gap-3 bg-orange-500/5 border border-orange-500/10 px-4 py-3 rounded-lg w-full sm:w-auto">
                                        <svg class="w-5 h-5 text-orange-500/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <div>
                                            <p class="text-[9px] text-orange-500 font-black uppercase tracking-widest">Awaiting Payment</p>
                                            <p class="text-[8px] text-gray-500 font-bold uppercase mt-0.5">User has not uploaded proof</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Admin Action Form --}}
                            <div class="w-full lg:w-auto flex lg:justify-end">
                                <form action="{{ route('admin.billings.approve', $req->id) }}" method="POST" class="w-full sm:w-auto" onsubmit="return confirm('Confirm payment received? This will instantly deploy credits to the user.');">
                                    @csrf
                                    <button type="submit" class="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-[0_0_15px_rgba(16,185,129,0.3)] transition-all hover:-translate-y-0.5 flex justify-center items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        Mark Paid & Activate
                                    </button>
                                </form>
                            </div>

                        </div>
                    @empty
                        <div class="py-12 text-center border border-dashed border-white/5 rounded-xl bg-black/50">
                            <div class="w-12 h-12 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest">All caught up</p>
                            <p class="text-[9px] text-gray-600 font-bold uppercase mt-1">No pending activation requests at this time.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>