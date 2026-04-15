<x-app-layout>
    {{-- Alpine state controls both modals now --}}
    <div class="max-w-6xl mx-auto py-10 px-6" x-data="{ showHistory: false, deleteModal: false, formToSubmit: null }">
        
        {{-- Success Notification --}}
        @if(session('success'))
            <div class="mb-6 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-widest rounded-lg flex items-center gap-2 shadow-[0_0_15px_rgba(16,185,129,0.1)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif
        
        {{-- Error Notification --}}
        @if($errors->any())
            <div class="mb-6 px-4 py-3 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold uppercase tracking-widest rounded-lg flex flex-col gap-1 shadow-[0_0_15px_rgba(239,68,68,0.1)]">
                @foreach ($errors->all() as $error)
                    <span>• {{ $error }}</span>
                @endforeach
            </div>
        @endif

        {{-- COMMAND CENTER HEADER --}}
        <div class="mb-10 border-b border-white/10 pb-6 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div>
                <h1 class="text-3xl font-black text-white uppercase tracking-widest">My Neural Wallets</h1>
                <p class="text-xs text-gray-500 font-bold uppercase mt-2">Manage and switch between your purchased asset packages</p>
            </div>
            
            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                
                {{-- Open History Modal Button --}}
                <button @click="showHistory = true" type="button" class="w-full sm:w-auto px-5 py-3 bg-[#0a0a0a] hover:bg-white/5 border border-white/10 text-gray-400 hover:text-white text-[10px] font-black uppercase tracking-widest rounded-lg transition-all shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>View Billing History</span>
                </button>

                {{-- Purchase Button --}}
                {{-- <a href="{{ route('pricing.index') }}" class="w-full sm:w-auto px-5 py-3 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-lg shadow-blue-600/20 transition-all text-center flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Purchase Package
                </a> --}}
            </div>
        </div>

        {{-- WALLETS GRID --}}
        <div class="space-y-6 mb-12">
            @forelse($wallets as $wallet)
                @php
                    $isExpired = $wallet->expires_at && now()->greaterThan($wallet->expires_at);
                    $isActive = $wallet->is_active_selection;
                @endphp

                <div class="bg-[#0a0a0a] border {{ $isActive ? 'border-emerald-500/50 shadow-[0_0_30px_rgba(16,185,129,0.1)]' : 'border-white/10' }} rounded-xl p-6 relative overflow-hidden transition-all {{ $isExpired ? 'opacity-60 grayscale' : '' }}">
                    
                    {{-- Active Glow Background --}}
                    @if($isActive)
                        <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
                    @endif

                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                        
                        {{-- Left: Wallet Info --}}
                        <div class="flex-shrink-0 lg:w-1/4">
                            <h3 class="text-2xl font-black text-white uppercase tracking-tight">{{ $wallet->package->name ?? 'Custom Plan' }}</h3>
                            
                            @if($isExpired)
                                <p class="text-xs text-red-500 font-bold uppercase tracking-widest mt-2">Expired on {{ \Carbon\Carbon::parse($wallet->expires_at)->format('M d, Y') }}</p>
                            @else
                                <p class="text-[10px] text-gray-500 font-mono mt-2">
                                    Valid Until: <span class="text-orange-400">{{ $wallet->expires_at ? \Carbon\Carbon::parse($wallet->expires_at)->format('M d, Y') : 'Lifetime' }}</span>
                                </p>
                            @endif
                        </div>

                        {{-- Middle: The Credits --}}
                        <div class="flex-grow w-full lg:w-auto">
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                                <div>
                                    <span class="block text-xl font-black text-white">{{ $wallet->directive_credits ?? 0 }}</span>
                                    <span class="text-[8px] text-gray-500 font-bold uppercase tracking-widest">Prompts</span>
                                </div>
                                <div>
                                    <span class="block text-xl font-black text-emerald-400">{{ $wallet->image_credits ?? 0 }}</span>
                                    <span class="text-[8px] text-gray-500 font-bold uppercase tracking-widest">Images</span>
                                </div>
                                <div>
                                    <span class="block text-xl font-black text-pink-400">{{ $wallet->video_credits ?? 0 }}</span>
                                    <span class="text-[8px] text-gray-500 font-bold uppercase tracking-widest">Videos</span>
                                </div>
                                <div>
                                    <span class="block text-xl font-black text-purple-400">{{ $wallet->branding_credits ?? 0 }}</span>
                                    <span class="text-[8px] text-gray-500 font-bold uppercase tracking-widest">Logos</span>
                                </div>
                                <div>
                                    <span class="block text-xl font-black text-blue-400">{{ $wallet->social_post_credits ?? 0 }}</span>
                                    <span class="text-[8px] text-gray-500 font-bold uppercase tracking-widest">Posts</span>
                                </div>
                            </div>
                        </div>

                        {{-- Right: Actions --}}
                        <div class="flex-shrink-0 w-full lg:w-auto flex lg:justify-end">
                            @if($isActive)
                                <div class="px-5 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-2 w-full lg:w-auto justify-center cursor-default">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Currently Active
                                </div>
                            @elseif($isExpired)
                                <div class="px-5 py-3 bg-[#111] border border-white/5 text-gray-600 text-[10px] font-black uppercase tracking-widest rounded-lg w-full lg:w-auto text-center cursor-not-allowed">
                                    Time Expired
                                </div>
                            @else
                                <form action="{{ route('billing.wallet.switch', $wallet->id) }}" method="POST" class="w-full lg:w-auto">
                                    @csrf
                                    <button type="submit" class="w-full lg:w-auto px-5 py-3 bg-white/5 hover:bg-blue-600 border border-white/10 hover:border-blue-500 text-gray-300 hover:text-white text-[10px] font-black uppercase tracking-widest rounded-lg transition-all shadow-lg text-center">
                                        Set As Active
                                    </button>
                                </form>
                            @endif
                        </div>

                    </div>
                </div>
            @empty
                <div class="bg-[#0a0a0a] border border-white/5 rounded-xl p-12 text-center border-dashed">
                    <svg class="w-12 h-12 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    <p class="text-xs font-black text-gray-500 uppercase tracking-widest mb-4">You have no active neural wallets.</p>
                    <a href="{{ route('pricing.index') }}" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-lg inline-block transition-all">
                        View Packages
                    </a>
                </div>
            @endforelse
        </div>

        {{-- ========================================== --}}
        {{-- MODAL 1: BILLING HISTORY & INVOICES        --}}
        {{-- ========================================== --}}
        <div x-show="showHistory" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm px-4">
            
            <div class="bg-[#0a0a0a] border border-white/10 rounded-xl w-full max-w-5xl shadow-2xl relative flex flex-col max-h-[90vh]" 
                 @click.away="showHistory = false" 
                 x-show="showHistory" 
                 x-transition>
                
                {{-- Modal Header --}}
                <div class="px-6 py-5 border-b border-white/5 flex justify-between items-center bg-white/[0.02] rounded-t-xl">
                    <h3 class="text-[11px] font-black text-gray-300 uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Billing History & Invoices
                    </h3>
                    <button type="button" @click="showHistory = false" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                {{-- Modal Body (Scrollable Table) --}}
                <div class="overflow-y-auto p-0 flex-grow">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="bg-black/60 sticky top-0 backdrop-blur-md z-10 border-b border-white/5">
                            <tr class="text-[9px] text-gray-500 uppercase tracking-widest font-bold">
                                <th class="px-6 py-4">Invoice No</th>
                                <th class="px-6 py-4">Package Details</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Issue Date</th>
                                <th class="px-6 py-4">Valid Until</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($billings as $bill)
                            
                            @php
                                $expiryDate = '—';
                                if ($bill->status === 'paid' && $bill->paid_at && $bill->package) {
                                    $paidDate = \Carbon\Carbon::parse($bill->paid_at);
                                    $cycle = strtolower($bill->package->billing_cycle ?? '');
                                    
                                    if (str_contains($cycle, 'month')) {
                                        $expiryDate = $paidDate->copy()->addMonth()->format('M d, Y');
                                    } elseif (str_contains($cycle, 'year')) {
                                        $expiryDate = $paidDate->copy()->addYear()->format('M d, Y');
                                    } else {
                                        $expiryDate = 'Lifetime';
                                    }
                                }
                            @endphp

                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-6 py-4 text-xs font-mono text-white">{{ $bill->invoice_no }}</td>
                                
                                <td class="px-6 py-4">
                                    <span class="block text-xs font-bold text-gray-300 uppercase">{{ $bill->package->name ?? 'Legacy Package' }}</span>
                                    <span class="text-[9px] text-gray-500 uppercase tracking-widest">{{ $bill->package->billing_cycle ?? 'Unknown' }} Cycle</span>
                                </td>
                                
                                <td class="px-6 py-4 text-xs font-black text-emerald-400">${{ number_format($bill->amount, 2) }}</td>
                                <td class="px-6 py-4 text-xs text-gray-400">{{ \Carbon\Carbon::parse($bill->created_at)->format('M d, Y') }}</td>
                                
                                <td class="px-6 py-4 text-xs font-mono {{ $expiryDate === '—' ? 'text-gray-600' : 'text-orange-400' }}">
                                    {{ $expiryDate }}
                                </td>

                                <td class="px-6 py-4">
                                    @if($bill->status === 'paid')
                                        <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded text-[9px] font-black uppercase tracking-widest">Paid</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-orange-500/10 text-orange-500 border border-orange-500/20 rounded text-[9px] font-black uppercase tracking-widest">Due</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right flex justify-end gap-2 items-center">
                                    {{-- <a href="{{ route('billing.invoice', $bill->id) }}" target="_blank" class="text-[10px] font-black text-blue-400 hover:text-blue-300 uppercase tracking-widest border border-blue-500/30 px-3 py-1.5 rounded transition-all whitespace-nowrap">
                                        View Invoice
                                    </a> --}}
                                    
                                    @if($bill->status === 'due')
                                        @if($bill->payment_proof)
                                            <span class="text-[10px] font-black text-orange-400 uppercase tracking-widest border border-orange-500/30 px-3 py-1.5 rounded bg-orange-500/5 flex items-center gap-1 cursor-default whitespace-nowrap">
                                                <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                Verifying
                                            </span>
                                        @else
                                            <button @click="$dispatch('open-proof-modal', { id: '{{ $bill->id }}' }); showHistory = false" class="text-[10px] font-black text-emerald-400 hover:text-emerald-300 uppercase tracking-widest border border-emerald-500/30 px-3 py-1.5 rounded transition-all bg-emerald-500/10 hover:bg-emerald-500/20 flex items-center gap-1 whitespace-nowrap">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                Submit Proof
                                            </button>
                                        @endif
                                    @endif

                                    @can('delete', $bill)
                                    <form action="{{ route('billing.destroy', $bill->id) }}" method="POST" class="inline" @submit.prevent="formToSubmit = $event.target; deleteModal = true;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" @click="formToSubmit = $event.target.closest('form'); deleteModal = true;" class="p-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded border border-red-500/30 transition-colors" title="Delete Record">
                                            <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center text-xs font-bold text-gray-500 uppercase tracking-widest">No billing history found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- MODAL 2: SUBMIT PAYMENT PROOF              --}}
        {{-- ========================================== --}}
        <div x-data="{ openProof: false, billId: null }" 
             @open-proof-modal.window="openProof = true; billId = $event.detail.id"
             x-show="openProof" 
             x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm px-4">
            
            <div class="bg-[#0a0a0a] border border-white/10 rounded-xl p-6 w-full max-w-md shadow-2xl relative" 
                 @click.away="openProof = false" 
                 x-show="openProof" 
                 x-transition>
                
                <div class="flex justify-between items-center mb-6 pb-4 border-b border-white/5">
                    <h3 class="text-emerald-400 font-black uppercase tracking-widest text-xs flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Submit Payment Proof
                    </h3>
                    <button type="button" @click="openProof = false" class="text-gray-500 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <form x-bind:action="'/billing/' + billId + '/proof'" method="POST" enctype="multipart/form-data" class="space-y-5" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                    @csrf
                    
                    <div>
                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Transaction ID / Reference No.</label>
                        <input type="text" name="transaction_id" required placeholder="e.g. TXN-987654321" class="w-full bg-black border border-white/10 rounded-lg p-3 text-white text-xs focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-all placeholder-gray-700 font-mono">
                        <p class="text-[8px] text-gray-500 mt-1 uppercase tracking-widest">Enter the bank reference or transaction ID.</p>
                    </div>
                    
                    <div>
                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Upload Screenshot</label>
                        <div class="relative">
                            <input type="file" name="payment_proof" accept="image/*" required class="w-full bg-black border border-white/10 rounded-lg p-2.5 text-white text-xs text-gray-400 file:mr-4 file:py-1.5 file:px-4 file:rounded file:border-0 file:text-[9px] file:font-black file:uppercase file:tracking-widest file:bg-emerald-500/10 file:text-emerald-400 hover:file:bg-emerald-500/20 file:transition-colors file:cursor-pointer cursor-pointer outline-none focus:border-emerald-500 transition-all">
                        </div>
                        <p class="text-[8px] text-gray-500 mt-1 uppercase tracking-widest">Accepted formats: JPG, PNG. Max size: 5MB.</p>
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit" :disabled="isSubmitting" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-[0_0_20px_rgba(5,150,105,0.2)] transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-text="isSubmitting ? 'UPLOADING PROOF...' : 'Upload & Submit for Verification'"></span>
                            <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            <svg x-show="isSubmitting" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </button>
                    </div>
                </form>
                
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- MODAL 2: DELETE CONFIRMATION             --}}
        {{-- ========================================== --}}
        <div x-show="deleteModal" 
             x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm px-4">
            
            <div class="bg-gradient-to-br from-[#111] to-[#0a0a0a] border border-red-500/20 rounded-xl w-full max-w-md shadow-2xl shadow-red-900/20"
                 @click.away="deleteModal = false"
                 x-show="deleteModal"
                 x-transition>
                
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-red-500/10 border border-red-500/20 rounded-full mx-auto flex items-center justify-center mb-5">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </div>

                    <h3 class="text-xl font-black text-white uppercase tracking-widest mb-2">Are you sure?</h3>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mb-6">This action will permanently delete the billing record. This cannot be undone.</p>
                    
                    <div class="flex justify-center items-center gap-3">
                        <button type="button" @click="deleteModal = false" class="px-6 py-3 bg-[#1f1f1f] hover:bg-white/5 border border-white/10 text-gray-300 hover:text-white text-[10px] font-black uppercase tracking-widest rounded-lg transition-all w-full">
                            Cancel
                        </button>
                        <button type="button" @click="formToSubmit.submit()" class="px-6 py-3 bg-red-600 hover:bg-red-500 border border-red-500/50 text-white text-[10px] font-black uppercase tracking-widest rounded-lg transition-all w-full shadow-lg shadow-red-600/20">
                            Confirm Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>