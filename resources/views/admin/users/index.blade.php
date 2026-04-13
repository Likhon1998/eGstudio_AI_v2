<x-app-layout>
    {{-- Initialize Alpine.js state for the modal at the top level --}}
    <div x-data="{ showTierModal: false, selectedUserId: null, selectedUserName: '' }" class="max-w-7xl mx-auto pt-6 pb-8 px-6 space-y-6 antialiased">
        
        {{-- Header Section --}}
        <div class="flex items-center justify-between border-b border-white/10 pb-6">
            <div>
                <h1 class="text-base font-bold tracking-tight text-white uppercase">
                    Agent <span class="text-blue-500">Roster</span>
                </h1>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-widest mt-1">
                    System Personnel, Subscription Status & Resource Balances
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-400 border border-white/10 rounded text-[9px] font-bold uppercase tracking-widest transition-all">
                    Back to Console
                </a>
                <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded text-[9px] font-bold uppercase tracking-widest shadow-lg shadow-blue-600/20 transition-all">
                    + Provision New
                </a>
            </div>
        </div>

        {{-- Table Container --}}
        <div class="bg-[#0d0d0d] border border-white/10 rounded-lg overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/[0.02] border-b border-white/10 text-[9px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                        <th class="px-6 py-4">Agent Identity</th>
                        <th class="px-6 py-4">Active Plan</th>
                        <th class="px-6 py-4 text-center">Credit Balances</th>
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

                        {{-- 3. Credits (Now showing all 5 resources) --}}
                        <td class="px-6 py-4">
                            @if($user->role === 'admin')
                                <span class="text-[10px] font-black text-purple-400 uppercase tracking-widest block text-center">Infinite</span>
                            @else
                                <div class="flex items-center justify-center gap-3">
                                    <div class="text-center">
                                        <span class="block text-[10px] font-mono text-white">{{ $activeWallet->directive_credits ?? 0 }}</span>
                                        <span class="text-[7px] text-gray-600 font-black uppercase">PRM</span>
                                    </div>
                                    <div class="text-center">
                                        <span class="block text-[10px] font-mono text-white">{{ $activeWallet->image_credits ?? 0 }}</span>
                                        <span class="text-[7px] text-gray-600 font-black uppercase">IMG</span>
                                    </div>
                                    <div class="text-center">
                                        <span class="block text-[10px] font-mono text-white">{{ $activeWallet->video_credits ?? 0 }}</span>
                                        <span class="text-[7px] text-gray-600 font-black uppercase">VID</span>
                                    </div>
                                    <div class="text-center">
                                        <span class="block text-[10px] font-mono text-white">{{ $activeWallet->branding_credits ?? 0 }}</span>
                                        <span class="text-[7px] text-gray-600 font-black uppercase">BRD</span>
                                    </div>
                                    <div class="text-center">
                                        <span class="block text-[10px] font-mono text-white">{{ $activeWallet->social_post_credits ?? 0 }}</span>
                                        <span class="text-[7px] text-gray-600 font-black uppercase">PST</span>
                                    </div>
                                </div>
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
                                    <span class="text-[8px] text-gray-600 font-bold uppercase">{{ \Carbon\Carbon::parse($activeWallet->expires_at)->diffForHumans() }}</span>
                                </div>
                            @else
                                <span class="text-[10px] font-mono text-gray-700">—</span>
                            @endif
                        </td>

                        {{-- 5. Status Badge --}}
                        <td class="px-6 py-4 text-center">
                            @php
                                $isActive = $activeWallet && $activeWallet->expires_at && \Carbon\Carbon::parse($activeWallet->expires_at)->isFuture();
                            @endphp

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
                                
                                {{-- Activate Tier Button (HIDDEN FOR ADMINS) --}}
                                @if($user->role !== 'admin')
                                    <button type="button" @click="showTierModal = true; selectedUserId = {{ $user->id }}; selectedUserName = '{{ addslashes($user->name) }}'" 
                                            class="px-3 py-1.5 bg-purple-600/20 hover:bg-purple-600/40 text-purple-400 border border-purple-500/50 rounded text-[10px] font-bold uppercase tracking-widest transition-all whitespace-nowrap">
                                        + Activate Tier
                                    </button>
                                @endif

                                {{-- Delete Form --}}
                                @if(auth()->id() !== $user->id)
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Remove this agent permanently? This cannot be undone.');">
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
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>