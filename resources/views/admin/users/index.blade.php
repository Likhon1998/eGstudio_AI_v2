<x-app-layout>
    {{-- Initialize Alpine.js state for the modal at the top level --}}
    <div x-data="{ 
            showTierModal: false, selectedUserId: null, selectedUserName: '', 
            deleteModal: false, formToSubmit: null,
            showTopupModal: false, topupUserId: null, topupUserName: '', isSubmittingTopup: false,
            topupCreditType: 'directive_credits'
        }" class="max-w-7xl mx-auto pt-6 pb-8 px-6 space-y-6 antialiased">
        
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="px-4 py-3 bg-red-500/10 border border-red-500/20 text-red-500 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                {{ session('error') }}
            </div>
        @endif

        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between border-b border-white/10 pb-6 gap-4">
            <div>
                <h1 class="text-base font-bold tracking-tight text-white uppercase">
                    Agent <span class="text-blue-500">Roster</span>
                </h1>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-widest mt-1">
                    System Personnel, Subscription Status & Resource Balances
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-400 border border-white/10 rounded text-[9px] font-bold uppercase tracking-widest transition-all">
                    Back to Console
                </a>
                
                {{-- Link to the Audit Ledger --}}
                <a href="{{ route('admin.credit_logs') }}" class="px-4 py-2 bg-emerald-600/10 hover:bg-emerald-600/20 border border-emerald-500/30 text-emerald-500 rounded text-[9px] font-bold uppercase tracking-widest transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Audit Ledger
                </a>

                <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded text-[9px] font-bold uppercase tracking-widest shadow-lg shadow-blue-600/20 transition-all">
                    + Provision New
                </a>
            </div>
        </div>

        {{-- Table Container --}}
        <div class="bg-[#0d0d0d] border border-white/10 rounded-lg overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-white/[0.02] border-b border-white/10 text-[9px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                            <th class="px-6 py-4">Agent Identity</th>
                            <th class="px-6 py-4">Active Plan</th>
                            <th class="px-6 py-4 text-center">Remaining Balances</th>
                            <th class="px-6 py-4">Subscription End</th>
                            <th class="px-6 py-4 text-center">Clearance Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        @foreach($users as $user)
                        
                        {{-- Fetch their active wallet from the user_packages table --}}
                        @php
                            $activeWallet = \App\Models\UserPackage::where('user_id', $user->id)
                                ->where('is_active_selection', 'true')
                                ->first();
                            
                            $isActive = $activeWallet && $activeWallet->expires_at && \Carbon\Carbon::parse($activeWallet->expires_at)->isFuture();
                        @endphp

                        <tr class="hover:bg-white/[0.01] transition-colors group">
                            {{-- 1. Identity --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 font-black text-xs uppercase">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-white uppercase">{{ $user->name }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- 2. Package --}}
                            <td class="px-6 py-4">
                                @if($user->role === 'admin')
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest">Master Node</span>
                                        <span class="text-[8px] text-gray-600 font-bold uppercase">System Admin</span>
                                    </div>
                                @elseif($activeWallet && $activeWallet->package)
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest">{{ $activeWallet->package->name }}</span>
                                        <span class="text-[8px] text-gray-600 font-bold uppercase">Active Tier</span>
                                    </div>
                                @else
                                    <span class="text-[9px] text-gray-600 font-bold uppercase tracking-widest italic">No Active Plan</span>
                                @endif
                            </td>

                            {{-- 3. Credits (NOW AS CLICKABLE PILLS) --}}
                            <td class="px-6 py-4">
                                @if($user->role === 'admin')
                                    <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest block text-center">Infinite</span>
                                @elseif($isActive)
                                    <div class="flex flex-wrap items-center justify-center gap-1.5 max-w-[260px] mx-auto">
                                        
                                        {{-- PRM --}}
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'directive_credits'" 
                                                class="px-2 py-1 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-gray-400 rounded flex items-baseline gap-1.5 transition-all" title="Add Prompts">
                                            <span class="text-[11px] font-mono font-bold text-white">{{ $activeWallet->directive_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">PRM</span>
                                        </button>
                                        
                                        {{-- IMG --}}
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'image_credits'" 
                                                class="px-2 py-1 bg-emerald-500/5 hover:bg-emerald-500/10 border border-emerald-500/10 hover:border-emerald-500/30 rounded flex items-baseline gap-1.5 transition-all" title="Add Image Gens">
                                            <span class="text-[11px] font-mono font-bold text-emerald-400">{{ $activeWallet->image_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">IMG</span>
                                        </button>
                                        
                                        {{-- VID --}}
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'video_credits'" 
                                                class="px-2 py-1 bg-pink-500/5 hover:bg-pink-500/10 border border-pink-500/10 hover:border-pink-500/30 rounded flex items-baseline gap-1.5 transition-all" title="Add Video Synth">
                                            <span class="text-[11px] font-mono font-bold text-pink-400">{{ $activeWallet->video_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">VID</span>
                                        </button>
                                        
                                        {{-- BRD --}}
                                        {{-- <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'branding_credits'" 
                                                class="px-2 py-1 bg-purple-500/5 hover:bg-purple-500/10 border border-purple-500/10 hover:border-purple-500/30 rounded flex items-baseline gap-1.5 transition-all" title="Add Total Brand">
                                            <span class="text-[11px] font-mono font-bold text-purple-400">{{ $activeWallet->branding_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">BRD</span>
                                        </button> --}}
                                        
                                        {{-- B_IM --}}
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'branding_image_credits'" 
                                                class="px-2 py-1 bg-blue-500/5 hover:bg-blue-500/10 border border-blue-500/10 hover:border-blue-500/30 rounded flex items-baseline gap-1.5 transition-all" title="Add B_Images">
                                            <span class="text-[11px] font-mono font-bold text-blue-400">{{ $activeWallet->branding_image_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">B_IM</span>
                                        </button>
                                        
                                        {{-- B_VD --}}
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'branding_video_credits'" 
                                                class="px-2 py-1 bg-orange-500/5 hover:bg-orange-500/10 border border-orange-500/10 hover:border-orange-500/30 rounded flex items-baseline gap-1.5 transition-all" title="Add B_Videos">
                                            <span class="text-[11px] font-mono font-bold text-orange-400">{{ $activeWallet->branding_video_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">B_VD</span>
                                        </button>
                                        
                                        {{-- SOC --}}
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'social_post_credits'" 
                                                class="px-2 py-1 bg-teal-500/5 hover:bg-teal-500/10 border border-teal-500/10 hover:border-teal-500/30 rounded flex items-baseline gap-1.5 transition-all" title="Add Social Pub">
                                            <span class="text-[11px] font-mono font-bold text-teal-400">{{ $activeWallet->social_post_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">SOC</span>
                                        </button>

                                    </div>
                                @else
                                    <span class="text-[9px] text-gray-600 font-bold uppercase tracking-widest block text-center italic">Wallet Inactive</span>
                                @endif
                            </td>

                            {{-- 4. Expiry --}}
                            <td class="px-6 py-4">
                                @if($user->role === 'admin')
                                    <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest block">Lifetime</span>
                                @elseif($activeWallet && $activeWallet->expires_at)
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-mono {{ \Carbon\Carbon::parse($activeWallet->expires_at)->isPast() ? 'text-red-500' : 'text-gray-300' }}">
                                            {{ \Carbon\Carbon::parse($activeWallet->expires_at)->format('Y-m-d') }}
                                        </span>
                                        <span class="text-[8px] text-gray-600 font-bold uppercase">
                                            {{ \Carbon\Carbon::parse($activeWallet->expires_at)->isPast() ? 'Expired' : \Carbon\Carbon::parse($activeWallet->expires_at)->diffInDays(now()) . ' days left' }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-[10px] font-mono text-gray-700">—</span>
                                @endif
                            </td>

                            {{-- 5. Status Badge --}}
                            <td class="px-6 py-4 text-center">
                                @if($user->role === 'admin')
                                    <span class="px-2 py-1 bg-purple-500/10 text-purple-400 border border-purple-500/20 rounded text-[8px] font-black uppercase tracking-widest">
                                        Admin Node
                                    </span>
                                @elseif($isActive)
                                    <span class="px-2 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded text-[8px] font-black uppercase tracking-widest">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-red-500/10 text-red-500 border border-red-500/20 rounded text-[8px] font-black uppercase tracking-widest">
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            {{-- 6. Actions --}}
                            <td class="px-6 py-4">
                                <div class="flex justify-end items-center gap-2">
                                    
                                    @if($user->role !== 'admin')
                                        {{-- Inject Credits Button (Top-Up) --}}
                                        @if($isActive)
                                            <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'directive_credits'" 
                                                    class="p-1.5 bg-emerald-600/10 hover:bg-emerald-600 border border-emerald-500/30 text-emerald-500 hover:text-white rounded transition-colors" title="Inject Credits">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                            </button>
                                        @endif

                                        {{-- Activate Tier Button --}}
                                        <button type="button" @click="showTierModal = true; selectedUserId = {{ $user->id }}; selectedUserName = '{{ addslashes($user->name) }}'" 
                                                class="px-3 py-1.5 bg-purple-600/20 hover:bg-purple-600/40 text-purple-400 border border-purple-500/50 rounded text-[10px] font-bold uppercase tracking-widest transition-all whitespace-nowrap">
                                            + Activate Tier
                                        </button>
                                    @endif

                                    {{-- Delete Form --}}
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline-block" @submit.prevent="formToSubmit = $event.target; deleteModal = true;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-red-500/10 hover:bg-red-500/20 text-red-500 hover:text-red-400 rounded transition-colors border border-transparent hover:border-red-500/30" title="Delete Agent">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[8px] text-gray-600 font-bold uppercase tracking-widest ml-2">Active User</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- NEW: ADMIN TOP-UP MODAL (CREDIT INJECTION) --}}
        <template x-teleport="body">
            <div x-show="showTopupModal" 
                 class="fixed inset-0 z-[99999] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl"
                 x-cloak>
                
                <div class="bg-[#0a0a0a] border border-emerald-500/20 w-full max-w-md rounded-2xl shadow-[0_0_40px_rgba(16,185,129,0.1)] overflow-hidden transform transition-all"
                     @click.away="showTopupModal = false"
                     x-show="showTopupModal"
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-8 scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-8 scale-95">
                    
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                        <div>
                            <h2 class="text-white font-black uppercase tracking-[0.2em] text-sm text-emerald-400">Inject Wallet Credits</h2>
                            <p class="text-gray-400 text-[9px] uppercase font-bold mt-1">Client: <span x-text="topupUserName" class="text-white"></span></p>
                        </div>
                        <button @click="showTopupModal = false" type="button" class="text-gray-500 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700 rounded p-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    {{-- Form Body --}}
                    <form :action="`/admin/users/${topupUserId}/top-up`" method="POST" @submit="isSubmittingTopup = true">
                        @csrf
                        <div class="p-6 space-y-5 bg-black/40">
                            
                            {{-- Credit Type Selector (NOW BINDS TO x-model SO CLICKING A BADGE SELECTS IT) --}}
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Select Asset Pipeline</label>
                                <select name="credit_type" x-model="topupCreditType" required class="w-full bg-[#111] border border-white/10 text-white rounded-lg p-3 text-xs focus:border-emerald-500 outline-none transition-all">
                                    <option value="directive_credits">⚡ Prompts (PRM)</option>
                                    <option value="image_credits">🖼️ Image Generation (IMG)</option>
                                    <option value="video_credits">🎥 Video Generation (VID)</option>
                                    <option value="branding_credits">✨ Total Brand (BRD)</option>
                                    <option value="branding_image_credits">🏷️ Image Branding (B_IM)</option>
                                    <option value="branding_video_credits">🎬 Video Branding (B_VD)</option>
                                    <option value="social_post_credits">📢 Social Broadcasts (SOC)</option>
                                </select>
                            </div>

                            {{-- Amount Input --}}
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Injection Amount</label>
                                <input type="number" name="amount" required min="1" max="1000" placeholder="e.g. 10" 
                                       class="w-full bg-[#111] border border-white/10 text-white rounded-lg p-3 text-sm focus:border-emerald-500 outline-none transition-all">
                            </div>

                            {{-- Billing Note / Audit Trail --}}
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Billing Reference / Note</label>
                                <input type="text" name="billing_note" placeholder="e.g. Billed $15 via manual invoice" 
                                       class="w-full bg-[#111] border border-white/10 text-white rounded-lg p-3 text-xs focus:border-emerald-500 outline-none transition-all">
                                <p class="text-[8px] text-gray-500 mt-1 uppercase tracking-wider">This will be recorded in the audit logs for accounting.</p>
                            </div>

                            <div class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg flex gap-3">
                                <svg class="w-4 h-4 text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-[9px] font-bold text-gray-300 uppercase leading-relaxed tracking-wider">
                                    This will add credits directly to the user's active tier instantly. Expiration dates are not altered.
                                </p>
                            </div>
                        </div>

                        {{-- Footer Controls --}}
                        <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex gap-3">
                            <button @click="showTopupModal = false" type="button" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmittingTopup" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-all shadow-[0_0_15px_rgba(16,185,129,0.3)] disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-text="isSubmittingTopup ? 'Injecting...' : 'Inject Credits'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        {{-- ADMIN TIER ACTIVATION MODAL --}}
        <template x-teleport="body">
            <div x-show="showTierModal" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4">
                
                {{-- Deep Backdrop --}}
                <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showTierModal = false" x-transition.opacity></div>
                
                {{-- Modal Box --}}
                <div class="relative bg-[#0a0a0a] border border-purple-500/30 rounded-xl shadow-[0_0_50px_rgba(168,85,247,0.15)] w-full max-w-sm transform transition-all"
                     x-show="showTierModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-8 scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-8 scale-95">
                    
                    <div class="flex justify-between items-center p-4 border-b border-gray-800/80">
                        <h3 class="text-xs font-black text-white uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Activate Client Tier
                        </h3>
                        <button type="button" @click="showTierModal = false" class="text-gray-500 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700 rounded p-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form :action="'/admin/users/' + selectedUserId + '/activate-tier'" method="POST" class="p-5">
                        @csrf
                        
                        <p class="text-[10px] text-gray-400 mb-4 font-medium">Assigning package to: <strong class="text-purple-400" x-text="selectedUserName"></strong></p>

                        <div class="space-y-4">
                            {{-- Select Tier Only --}}
                            <div>
                                <label class="block text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Select Tier</label>
                                <select name="package_id" required class="w-full bg-[#111] border border-gray-700 rounded-lg text-white text-xs p-2.5 focus:border-purple-500 outline-none transition-all">
                                    <option value="">-- Choose Package --</option>
                                    @foreach(\App\Models\Package::all() as $pack)
                                        <option value="{{ $pack->id }}">{{ $pack->name }} ({{ ucfirst($pack->billing_cycle) }})</option>
                                    @endforeach
                                </select>
                                <p class="text-[8px] text-gray-600 mt-2 uppercase tracking-widest font-bold">Expiration date will be auto-calculated based on package cycle.</p>
                            </div>
                        </div>

                        <div class="mt-6 flex gap-2">
                            <button type="button" @click="showTierModal = false" class="flex-1 py-2.5 bg-transparent border border-gray-700 hover:bg-gray-800 text-gray-300 text-[10px] font-black rounded-lg uppercase tracking-widest transition-all">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 py-2.5 bg-purple-600 hover:bg-purple-500 text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-all shadow-[0_0_15px_rgba(168,85,247,0.3)]">
                                Activate Tier
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        {{-- DELETE CONFIRMATION MODAL --}}
        <template x-teleport="body">
            <div x-show="deleteModal" 
                class="fixed inset-0 z-[2200] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl"
                x-cloak>
                <div class="bg-[#0a0a0a] border border-red-500/20 w-full max-w-md rounded-2xl p-8 shadow-[0_0_40px_rgba(239,68,68,0.1)] transform transition-all"
                    @click.away="deleteModal = false"
                    x-show="deleteModal"
                    x-transition:enter="ease-out duration-300" 
                    x-transition:enter-start="opacity-0 translate-y-8 scale-95" 
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                    x-transition:leave="ease-in duration-200" 
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                    x-transition:leave-end="opacity-0 translate-y-8 scale-95">
                    
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-white font-black uppercase tracking-[0.2em] text-sm text-red-500">Purge Agent</h2>
                            <p class="text-gray-400 text-[9px] uppercase font-bold mt-1">Are you sure you want to remove this user?</p>
                        </div>
                        <button @click="deleteModal = false" type="button"
                            class="text-gray-600 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700 rounded p-1">✕</button>
                    </div>
                    
                    <div class="text-gray-400 text-xs mb-8">
                        This action will permanently delete this agent account and all associated system data. This cannot be undone.
                    </div>

                    <div class="flex gap-3">
                        <button @click="deleteModal = false" type="button" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-colors">
                            Cancel
                        </button>
                        <button @click="formToSubmit.submit()" type="button" class="flex-1 py-3 bg-red-600/20 hover:bg-red-600 border border-red-500/50 text-red-500 hover:text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-all">
                            Yes, Delete
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>