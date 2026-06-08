<x-app-layout>
    {{-- Initialize Alpine.js state for the modal at the top level --}}
    <div x-data="{ 
            showTierModal: false, selectedUserId: null, selectedUserName: '', 
            deleteModal: false, formToSubmit: null,
            showTopupModal: false, topupUserId: null, topupUserName: '', isSubmittingTopup: false,
            topupCreditType: 'directive_credits',
            showApproverModal: false, approverUserId: null, approverUserName: '', isSubmittingApprover: false,
            tab: 'agents'
        }" class="max-w-7xl mx-auto pt-6 pb-12 px-4 sm:px-6 lg:px-8 space-y-6 antialiased w-full overflow-x-hidden">

        @php
            // Split the roster: standard users/admins vs. approver credentials.
            $approverUsers = $users->filter(fn($u) => $u->account_type === 'approver')->values();
            $agentUsers    = $users->filter(fn($u) => $u->account_type !== 'approver')->values();
        @endphp
        
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-2 shadow-lg shadow-emerald-500/5">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="px-4 py-3 bg-red-500/10 border border-red-500/20 text-red-500 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-2 shadow-lg shadow-red-500/5">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Header Section --}}
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between border-b border-white/5 pb-6 gap-4">
            <div class="max-w-xl">
                <h1 class="text-xl font-black tracking-tight text-white uppercase flex items-center gap-3">
                    Agent <span class="text-blue-500">Roster</span>
                </h1>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1.5 leading-relaxed">
                    System Personnel, Subscription Status & Resource Balances
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <a href="{{ route('admin.dashboard') }}" class="flex-1 lg:flex-none text-center px-4 py-2.5 bg-white/[0.03] hover:bg-white/[0.08] text-gray-400 border border-white/10 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-all">
                    Back to Console
                </a>
                
                <a href="{{ route('admin.credit_logs') }}" class="flex-1 lg:flex-none justify-center px-4 py-2.5 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-all flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Audit Ledger
                </a>

                <a href="{{ route('admin.users.create') }}" class="flex-1 lg:flex-none justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-[9px] font-bold uppercase tracking-widest shadow-lg shadow-blue-600/20 transition-all">
                    + Provision New
                </a>
            </div>
        </div>

        {{-- TAB SWITCHER --}}
        <div class="flex items-center bg-white/5 p-1 rounded-xl border border-white/5 w-max">
            <button @click="tab = 'agents'"
                :class="tab === 'agents' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-300'"
                class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all">
                Users &amp; Agents ({{ $agentUsers->count() }})
            </button>
            <button @click="tab = 'approvers'"
                :class="tab === 'approvers' ? 'bg-emerald-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-300'"
                class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all">
                Approvers ({{ $approverUsers->count() }})
            </button>
        </div>

        {{-- ============================ TAB 1: USERS & AGENTS ============================ --}}
        <div x-show="tab === 'agents'">

        {{-- Desktop Column Headers (Hidden on Mobile) --}}
        <div class="hidden lg:grid grid-cols-12 gap-6 px-6 py-3 bg-white/[0.02] border border-white/5 rounded-t-xl text-[9px] uppercase tracking-[0.2em] text-gray-500 font-black">
            <div class="col-span-3">Agent Identity</div>
            <div class="col-span-2">Active Plans</div>
            <div class="col-span-4 text-center">Resource Balances (Click to Top-Up)</div>
            <div class="col-span-2 text-center">Status & Expiry</div>
            <div class="col-span-1 text-right">Actions</div>
        </div>

        {{-- Roster List (Responsive Grid/Cards) --}}
        <div class="flex flex-col gap-4 lg:gap-0 lg:-mt-6 lg:border lg:border-t-0 lg:border-white/5 lg:bg-[#0a0a0a] lg:rounded-b-xl lg:overflow-hidden lg:shadow-2xl">
            @foreach($agentUsers as $user)
            
            @php
                $activeWallet = \App\Models\UserPackage::where('user_id', $user->id)
                    ->where('is_active_selection', 'true')
                    ->first();
                
                $isActive = $activeWallet && $activeWallet->expires_at && \Carbon\Carbon::parse($activeWallet->expires_at)->isFuture();

                // Approval workflow context for this row
                $isApproverAccount = $user->account_type === 'approver';
                $ownClient = $isApproverAccount ? \App\Models\User::find($user->client_id) : null;
                $attachedApprover = (!$isApproverAccount && $user->role !== 'admin')
                    ? \App\Models\User::where('client_id', $user->id)->where('account_type', 'approver')->first()
                    : null;
            @endphp

            {{-- User Card / Row --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6 items-center p-5 lg:px-6 bg-[#0d0d0d] lg:bg-transparent border border-white/10 lg:border-0 lg:border-b lg:border-white/[0.03] rounded-xl lg:rounded-none hover:bg-white/[0.01] transition-all group">
                
                {{-- 1. Identity --}}
                <div class="col-span-1 lg:col-span-3 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500/20 to-purple-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 font-black text-sm uppercase shrink-0 shadow-inner">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-black text-white uppercase tracking-wider truncate">{{ $user->name }}</div>
                        <div class="text-[10px] text-gray-500 font-mono truncate mt-0.5">{{ $user->email }}</div>
                        @if($isApproverAccount)
                            <span class="inline-block mt-1.5 px-2 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded text-[8px] font-black uppercase tracking-widest">
                                Approver{{ $ownClient ? ' · '.$ownClient->name : '' }}
                            </span>
                        @elseif($attachedApprover)
                            <span class="inline-block mt-1.5 px-2 py-0.5 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded text-[8px] font-black uppercase tracking-widest" title="{{ $attachedApprover->email }}">
                                Approval Enabled
                            </span>
                        @endif
                    </div>
                </div>

                {{-- 2. Package --}}
                <div class="col-span-1 lg:col-span-2 flex flex-row lg:flex-col flex-wrap gap-2 lg:gap-1.5 mt-2 lg:mt-0 pt-3 lg:pt-0 border-t border-white/5 lg:border-0">
                    <div class="lg:hidden text-[9px] text-gray-600 font-black uppercase tracking-widest w-full">Plans</div>
                    @if($user->role === 'admin')
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest bg-purple-500/10 border border-purple-500/20 px-2 py-1 rounded w-max">Master Node</span>
                        </div>
                    @elseif($activeWallet && $activeWallet->package)
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest leading-tight">{{ $activeWallet->package->name }}</span>
                            <span class="text-[8px] text-gray-600 font-bold uppercase mt-0.5">Active Tier</span>
                        </div>
                    @else
                        <span class="text-[9px] text-gray-600 font-bold uppercase tracking-widest italic bg-white/5 px-2 py-1 rounded w-max">No Plan</span>
                    @endif
                </div>

                {{-- 3. Credits (Interactive Badges) --}}
                <div class="col-span-1 lg:col-span-4 mt-2 lg:mt-0 pt-3 lg:pt-0 border-t border-white/5 lg:border-0">
                    <div class="lg:hidden text-[9px] text-gray-600 font-black uppercase tracking-widest w-full mb-2">Resource Balances</div>
                    
                    @if($user->role === 'admin')
                        <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest lg:text-center block w-full">Infinite Limits</span>
                    @elseif($isActive)
                        <div class="flex flex-col gap-3 lg:items-center">
                            
                            {{-- Plan Wallet (covers CGI & Occasion Studio) --}}
                            @if($isActive)
                                <div class="w-full">
                                    <div class="flex flex-wrap lg:justify-center gap-1.5">
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'directive_credits'"
                                                class="px-2 py-1 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-blue-400/50 rounded flex items-center gap-1.5 transition-all group/btn" title="Add Prompts">
                                            <span class="text-[11px] font-mono font-bold text-white group-hover/btn:text-blue-400">{{ $activeWallet->directive_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">PRM</span>
                                        </button>
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'image_credits'"
                                                class="px-2 py-1 bg-emerald-500/5 hover:bg-emerald-500/10 border border-emerald-500/10 hover:border-emerald-400/50 rounded flex items-center gap-1.5 transition-all" title="Add Image Gens">
                                            <span class="text-[11px] font-mono font-bold text-emerald-400">{{ $activeWallet->image_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">IMG</span>
                                        </button>
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'video_credits'"
                                                class="px-2 py-1 bg-pink-500/5 hover:bg-pink-500/10 border border-pink-500/10 hover:border-pink-400/50 rounded flex items-center gap-1.5 transition-all" title="Add Video Synth">
                                            <span class="text-[11px] font-mono font-bold text-pink-400">{{ $activeWallet->video_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">VID</span>
                                        </button>
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'branding_image_credits'"
                                                class="px-2 py-1 bg-blue-500/5 hover:bg-blue-500/10 border border-blue-500/10 hover:border-blue-400/50 rounded flex items-center gap-1.5 transition-all" title="Add B_Images">
                                            <span class="text-[11px] font-mono font-bold text-blue-400">{{ $activeWallet->branding_image_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">B_IM</span>
                                        </button>
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'branding_video_credits'"
                                                class="px-2 py-1 bg-orange-500/5 hover:bg-orange-500/10 border border-orange-500/10 hover:border-orange-400/50 rounded flex items-center gap-1.5 transition-all" title="Add B_Videos">
                                            <span class="text-[11px] font-mono font-bold text-orange-400">{{ $activeWallet->branding_video_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">B_VD</span>
                                        </button>
                                        <button type="button" @click="showTopupModal = true; topupUserId = {{ $user->id }}; topupUserName = '{{ addslashes($user->name) }}'; topupCreditType = 'social_post_credits'"
                                                class="px-2 py-1 bg-teal-500/5 hover:bg-teal-500/10 border border-teal-500/10 hover:border-teal-400/50 rounded flex items-center gap-1.5 transition-all" title="Add Social Pub">
                                            <span class="text-[11px] font-mono font-bold text-teal-400">{{ $activeWallet->social_post_credits ?? 0 }}</span>
                                            <span class="text-[7px] text-gray-400 font-black uppercase">SOC</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <span class="text-[9px] text-gray-600 font-bold uppercase tracking-widest lg:text-center block w-full italic bg-white/5 p-2 rounded">Wallet Inactive</span>
                    @endif
                </div>

                {{-- 4. Expiry & Status --}}
                <div class="col-span-1 lg:col-span-2 flex flex-row lg:flex-col flex-wrap lg:items-center lg:justify-center gap-4 lg:gap-2 mt-2 lg:mt-0 pt-3 lg:pt-0 border-t border-white/5 lg:border-0">
                    <div class="lg:hidden text-[9px] text-gray-600 font-black uppercase tracking-widest w-full mb-1">Status & Expiry</div>
                    
                    @if($user->role === 'admin')
                         <span class="px-2 py-1 bg-purple-500/10 text-purple-400 border border-purple-500/20 rounded text-[8px] font-black uppercase tracking-widest w-max">Admin Node</span>
                    @else
                        {{-- Status Badges --}}
                        <div class="flex gap-1.5">
                            @if($isActive)
                                <span class="px-2 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded text-[8px] font-black uppercase tracking-widest w-max">Active</span>
                            @else
                                <span class="px-2 py-1 bg-red-500/10 text-red-500 border border-red-500/20 rounded text-[8px] font-black uppercase tracking-widest w-max">Inactive</span>
                            @endif
                        </div>
                        
                        {{-- Expiry Date --}}
                        <div class="flex flex-col gap-1 w-full lg:items-center">
                            @if($isActive && $activeWallet->expires_at)
                                @php
                                    $cgiExpiresAt = \Carbon\Carbon::parse($activeWallet->expires_at);
                                @endphp
                                <div class="text-[9px] font-mono {{ $cgiExpiresAt->isPast() ? 'text-red-500' : 'text-gray-400' }}">
                                    {{ $cgiExpiresAt->format('M d, Y') }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- 5. Actions --}}
                <div class="col-span-1 lg:col-span-1 flex flex-row lg:flex-col justify-end lg:justify-center items-center gap-2 mt-2 lg:mt-0 pt-4 lg:pt-0 border-t border-white/5 lg:border-0 w-full">
                    
                    @if(!$user->isAdmin())
                        <div class="flex flex-wrap lg:flex-col gap-2 w-full lg:w-auto">
                            {{-- Activate Plan (covers CGI & Occasion Studio) --}}
                            <button type="button" @click="showTierModal = true; selectedUserId = {{ $user->id }}; selectedUserName = '{{ addslashes($user->name) }}'" 
                                    class="flex-1 lg:flex-none justify-center px-3 py-1.5 bg-purple-600/10 hover:bg-purple-600/30 text-purple-400 border border-purple-500/30 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-all whitespace-nowrap">
                                + Plan
                            </button>

                            {{-- Attach Approval Credential (only for standard users without one) --}}
                            @if(!$isApproverAccount && !$attachedApprover)
                                <button type="button" @click="showApproverModal = true; approverUserId = {{ $user->id }}; approverUserName = '{{ addslashes($user->name) }}'"
                                        class="flex-1 lg:flex-none justify-center px-3 py-1.5 bg-emerald-600/10 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-all whitespace-nowrap">
                                    + Approver
                                </button>
                            @elseif($attachedApprover)
                                <span class="flex-1 lg:flex-none justify-center px-3 py-1.5 bg-white/5 text-gray-500 border border-white/10 rounded-lg text-[8px] font-bold uppercase tracking-widest text-center" title="{{ $attachedApprover->email }}">
                                    ✓ Approver Set
                                </span>
                            @endif
                        </div>
                    @endif

                    {{-- Delete Form --}}
                    @if(auth()->id() !== $user->id)
                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="w-full lg:w-auto mt-2 lg:mt-1" @submit.prevent="formToSubmit = $event.target; deleteModal = true;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full lg:w-auto flex justify-center items-center p-2 bg-red-500/5 hover:bg-red-500/20 text-red-500/70 hover:text-red-400 rounded-lg transition-colors border border-transparent hover:border-red-500/30" title="Delete Agent">
                                <span class="lg:hidden text-[9px] font-bold uppercase tracking-widest mr-2">Delete Agent</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    @endif
                </div>

            </div>
            @endforeach
        </div>
        </div>
        {{-- ============================ END TAB 1 ============================ --}}

        {{-- ============================ TAB 2: APPROVERS ============================ --}}
        <div x-show="tab === 'approvers'" x-cloak>
            @if($approverUsers->isEmpty())
                <div class="py-20 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-[2rem] bg-white/[0.01]">
                    <h3 class="text-[11px] font-black text-gray-600 uppercase tracking-[0.3em]">No Approver Credentials</h3>
                    <p class="text-[9px] text-gray-700 font-bold uppercase tracking-widest mt-2">Attach one from a user's <span class="text-emerald-500">+ Approver</span> action.</p>
                </div>
            @else
                {{-- Column Headers --}}
                <div class="hidden lg:grid grid-cols-12 gap-6 px-6 py-3 bg-white/[0.02] border border-white/5 rounded-t-xl text-[9px] uppercase tracking-[0.2em] text-gray-500 font-black">
                    <div class="col-span-4">Approver Identity</div>
                    <div class="col-span-5">Reviews For (Client)</div>
                    <div class="col-span-3 text-right">Actions</div>
                </div>

                <div class="flex flex-col gap-4 lg:gap-0 lg:-mt-6 lg:border lg:border-t-0 lg:border-white/5 lg:bg-[#0a0a0a] lg:rounded-b-xl lg:overflow-hidden lg:shadow-2xl">
                    @foreach($approverUsers as $approver)
                        @php $client = \App\Models\User::find($approver->client_id); @endphp
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6 items-center p-5 lg:px-6 bg-[#0d0d0d] lg:bg-transparent border border-white/10 lg:border-0 lg:border-b lg:border-white/[0.03] rounded-xl lg:rounded-none hover:bg-white/[0.01] transition-all">

                            {{-- Identity --}}
                            <div class="col-span-1 lg:col-span-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500/20 to-blue-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-black text-sm uppercase shrink-0 shadow-inner">
                                    {{ substr($approver->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-black text-white uppercase tracking-wider truncate">{{ $approver->name }}</div>
                                    <div class="text-[10px] text-gray-500 font-mono truncate mt-0.5">{{ $approver->email }}</div>
                                    <span class="inline-block mt-1.5 px-2 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded text-[8px] font-black uppercase tracking-widest">Approver · No Plan</span>
                                </div>
                            </div>

                            {{-- Reviews For --}}
                            <div class="col-span-1 lg:col-span-5 flex flex-col gap-1 mt-2 lg:mt-0 pt-3 lg:pt-0 border-t border-white/5 lg:border-0">
                                <div class="lg:hidden text-[9px] text-gray-600 font-black uppercase tracking-widest">Reviews For</div>
                                @if($client)
                                    <span class="text-[11px] font-black text-white uppercase tracking-wider truncate">{{ $client->name }}</span>
                                    <span class="text-[9px] text-gray-500 font-mono truncate">{{ $client->email }}</span>
                                @else
                                    <span class="text-[9px] text-red-500 font-bold uppercase tracking-widest italic">Client removed</span>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="col-span-1 lg:col-span-3 flex justify-end items-center gap-2 mt-2 lg:mt-0 pt-4 lg:pt-0 border-t border-white/5 lg:border-0 w-full">
                                @if(auth()->id() !== $approver->id)
                                    <form action="{{ route('admin.users.destroy', $approver->id) }}" method="POST" class="w-full lg:w-auto" @submit.prevent="formToSubmit = $event.target; deleteModal = true;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full lg:w-auto flex justify-center items-center p-2 bg-red-500/5 hover:bg-red-500/20 text-red-500/70 hover:text-red-400 rounded-lg transition-colors border border-transparent hover:border-red-500/30" title="Remove Approver">
                                            <span class="lg:hidden text-[9px] font-bold uppercase tracking-widest mr-2">Remove</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        {{-- ============================ END TAB 2 ============================ --}}

        {{-- NEW: ADMIN TOP-UP MODAL (CREDIT INJECTION) --}}
        <template x-teleport="body">
            <div x-show="showTopupModal" 
                 class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 bg-black/90 backdrop-blur-xl"
                 x-cloak>
                
                <div class="bg-[#0a0a0a] border border-emerald-500/20 w-full max-w-md rounded-2xl shadow-[0_0_40px_rgba(16,185,129,0.1)] overflow-hidden transform transition-all flex flex-col max-h-[90vh]"
                     @click.away="showTopupModal = false"
                     x-show="showTopupModal"
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-8 scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-8 scale-95">
                    
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex justify-between items-center shrink-0">
                        <div>
                            <h2 class="text-white font-black uppercase tracking-[0.2em] text-sm text-emerald-400">Inject Wallet Credits</h2>
                            <p class="text-gray-400 text-[9px] uppercase font-bold mt-1">Client: <span x-text="topupUserName" class="text-white"></span></p>
                        </div>
                        <button @click="showTopupModal = false" type="button" class="text-gray-500 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700 rounded p-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    {{-- Form Body --}}
                    <form :action="`/admin/users/${topupUserId}/top-up`" method="POST" @submit="isSubmittingTopup = true" class="flex flex-col overflow-hidden">
                        @csrf
                        <div class="p-6 space-y-5 bg-black/40 overflow-y-auto">
                            
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

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Injection Amount</label>
                                <input type="number" name="amount" required min="1" max="1000" placeholder="e.g. 10" 
                                       class="w-full bg-[#111] border border-white/10 text-white rounded-lg p-3 text-sm focus:border-emerald-500 outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Billing Reference / Note</label>
                                <input type="text" name="billing_note" placeholder="e.g. Billed $15 via manual invoice" 
                                       class="w-full bg-[#111] border border-white/10 text-white rounded-lg p-3 text-xs focus:border-emerald-500 outline-none transition-all">
                                <p class="text-[8px] text-gray-500 mt-1.5 uppercase tracking-wider">This will be recorded in the audit logs for accounting.</p>
                            </div>

                            <div class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg flex gap-3">
                                <svg class="w-4 h-4 text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-[9px] font-bold text-gray-300 uppercase leading-relaxed tracking-wider">
                                    Adds credits directly to the active tier instantly. Expiration dates are not altered.
                                </p>
                            </div>
                        </div>

                        {{-- Footer Controls --}}
                        <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex gap-3 shrink-0">
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

        {{-- ATTACH APPROVAL CREDENTIAL MODAL --}}
        <template x-teleport="body">
            <div x-show="showApproverModal"
                 class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 bg-black/90 backdrop-blur-xl"
                 x-cloak>

                <div class="bg-[#0a0a0a] border border-emerald-500/20 w-full max-w-md rounded-2xl shadow-[0_0_40px_rgba(16,185,129,0.1)] overflow-hidden transform transition-all flex flex-col max-h-[90vh]"
                     @click.away="showApproverModal = false"
                     x-show="showApproverModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-white/5 bg-white/[0.02] flex justify-between items-center shrink-0">
                        <div>
                            <h2 class="text-emerald-400 font-black uppercase tracking-[0.2em] text-sm">Add Approval Credential</h2>
                            <p class="text-gray-400 text-[9px] uppercase font-bold mt-1">For: <span x-text="approverUserName" class="text-white"></span></p>
                        </div>
                        <button @click="showApproverModal = false" type="button" class="text-gray-500 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700 rounded p-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form :action="`/admin/users/${approverUserId}/approver`" method="POST" @submit="isSubmittingApprover = true" class="flex flex-col overflow-hidden">
                        @csrf
                        <div class="p-6 space-y-5 bg-black/40 overflow-y-auto">

                            <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg">
                                <p class="text-[9px] font-bold text-emerald-300 uppercase leading-relaxed tracking-wider">
                                    This creates a second login that reviews & approves every pic/video this user makes. The user can't publish until the approver approves.
                                </p>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Approver Name (optional)</label>
                                <input type="text" name="approver_name" placeholder="Defaults to '[User] Approver'"
                                       class="w-full bg-[#111] border border-white/10 text-white rounded-lg p-3 text-xs focus:border-emerald-500 outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Login Email</label>
                                <input type="email" name="approver_email" required placeholder="approver@client.com"
                                       class="w-full bg-[#111] border border-white/10 text-white rounded-lg p-3 text-xs focus:border-emerald-500 outline-none transition-all">
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Password</label>
                                <input type="text" name="approver_password" required minlength="8" placeholder="Min 8 characters"
                                       class="w-full bg-[#111] border border-white/10 text-white rounded-lg p-3 text-xs focus:border-emerald-500 outline-none transition-all">
                                <p class="text-[8px] text-gray-500 mt-1.5 uppercase tracking-wider">Share these credentials with the client's approver.</p>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex gap-3 shrink-0">
                            <button @click="showApproverModal = false" type="button" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-colors">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmittingApprover" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-all shadow-[0_0_15px_rgba(16,185,129,0.3)] disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-text="isSubmittingApprover ? 'Creating...' : 'Create Credential'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        {{-- ADMIN TIER ACTIVATION MODAL --}}
        <template x-teleport="body">
            <div x-show="showTierModal" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/90 backdrop-blur-xl" @click="showTierModal = false" x-transition.opacity></div>
                
                <div class="relative bg-[#0a0a0a] border border-purple-500/30 rounded-2xl shadow-[0_0_50px_rgba(168,85,247,0.15)] w-full max-w-sm transform transition-all flex flex-col max-h-[90vh]"
                     x-show="showTierModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-8 scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100">
                    
                    <div class="flex justify-between items-center p-5 border-b border-gray-800/80 shrink-0">
                        <h3 class="text-xs font-black text-white uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Activate Client Tier
                        </h3>
                        <button type="button" @click="showTierModal = false" class="text-gray-500 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700 rounded p-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form :action="'/admin/users/' + selectedUserId + '/activate-tier'" method="POST" class="flex flex-col overflow-hidden">
                        @csrf
                        <div class="p-6 overflow-y-auto bg-black/40 space-y-5">
                            <p class="text-[10px] text-gray-400 font-medium">Target Client: <strong class="text-purple-400 uppercase tracking-wider" x-text="selectedUserName"></strong></p>

                            <div>
                                <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-2">Select Primary Tier</label>
                                <select name="package_id" required class="w-full bg-[#111] border border-gray-700 rounded-lg text-white text-xs p-3 focus:border-purple-500 outline-none transition-all">
                                    <option value="">-- Choose Package --</option>
                                    @foreach(\App\Models\Package::all() as $pack)
                                        <option value="{{ $pack->id }}">{{ $pack->name }} ({{ ucfirst($pack->billing_cycle) }})</option>
                                    @endforeach
                                </select>
                                <p class="text-[8px] text-gray-500 mt-2 uppercase tracking-widest font-bold">Expiration date will be auto-calculated.</p>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex gap-3 shrink-0">
                            <button type="button" @click="showTierModal = false" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-all shadow-[0_0_15px_rgba(168,85,247,0.3)]">
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
                class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 bg-black/90 backdrop-blur-xl"
                x-cloak>
                <div class="bg-[#0a0a0a] border border-red-500/20 w-full max-w-sm rounded-2xl p-6 sm:p-8 shadow-[0_0_40px_rgba(239,68,68,0.1)] transform transition-all"
                    @click.away="deleteModal = false"
                    x-show="deleteModal"
                    x-transition:enter="ease-out duration-300" 
                    x-transition:enter-start="opacity-0 translate-y-8 scale-95" 
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100">
                    
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-white font-black uppercase tracking-[0.2em] text-sm text-red-500">Purge Agent</h2>
                            <p class="text-gray-400 text-[9px] uppercase font-bold mt-1.5">Destructive Action</p>
                        </div>
                        <button @click="deleteModal = false" type="button" class="text-gray-600 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700 rounded p-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <div class="text-gray-400 text-[11px] leading-relaxed mb-8 bg-red-500/5 p-4 rounded-lg border border-red-500/10">
                        This action will <strong class="text-white">permanently delete</strong> this agent account and all associated system data, active tiers, and wallets. This cannot be undone.
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
        /* Prevent body scroll when modal is open (Alpine plugin optional, handled via CSS here) */
        body:has(.fixed.inset-0[style*="display: flex"]) { overflow: hidden; }
    </style>
</x-app-layout>