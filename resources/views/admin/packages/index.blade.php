<x-app-layout>
    <div x-data="{ deleteModal: false, formToSubmit: null }" class="p-4 sm:p-6 lg:p-8 max-w-[1600px] mx-auto space-y-6 antialiased selection:bg-blue-500/30">
        
        {{-- =========================================================================
             DYNAMIC PHP CALCULATIONS
             ========================================================================= --}}
        @php
            $user = auth()->user();
            
            // Find the currently active wallet for the Admin
            $activeWallet = \App\Models\UserPackage::with('package')
                ->where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->first();

            $expiryDate = $activeWallet ? $activeWallet->expires_at : null;
        @endphp

        {{-- =========================================================================
             1. PAGE HEADER & NAVIGATION
             ========================================================================= --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between border-b border-white/10 pb-5 gap-4 relative">
            <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-600/20 rounded-full blur-[80px] pointer-events-none"></div>

            <div class="relative">
                <h1 class="text-xl sm:text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500 tracking-[0.2em] uppercase">
                    Monetization Plans
                </h1>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1.5 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_#3b82f6] animate-pulse"></span>
                    Configure SaaS Packages & Asset Limits
                </p>
            </div>
            
            <a href="{{ route('admin.billings.requests') }}" class="px-5 py-2.5 bg-orange-500/10 border border-orange-500/20 hover:border-orange-500/50 text-orange-500 hover:bg-orange-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-[0.15em] transition-all flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(249,115,22,0.15)] group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></div>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                View Activation Requests
            </a>
        </div>

        {{-- =========================================================================
             2. SUCCESS NOTIFICATION TOAST
             ========================================================================= --}}
        @if(session('success'))
            <div class="px-5 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-lg flex items-center gap-3 shadow-[0_0_20px_rgba(16,185,129,0.1)] animate-in fade-in slide-in-from-top-4">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- =========================================================================
             3. NEURAL WALLET USAGE (ADMIN VIEW)
             ========================================================================= --}}
        @if($activeWallet && $activeWallet->package)
            @php
                $allowanceDir = $activeWallet->package->directive_allowance ?? 0;
                $remDir = $activeWallet->directive_credits ?? 0;
                $pctDir = $allowanceDir > 0 ? min(100, (($allowanceDir - $remDir) / $allowanceDir) * 100) : 0;

                $allowanceImg = $activeWallet->package->image_allowance ?? 0;
                $remImg = $activeWallet->image_credits ?? 0;
                $pctImg = $allowanceImg > 0 ? min(100, (($allowanceImg - $remImg) / $allowanceImg) * 100) : 0;

                $allowanceVid = $activeWallet->package->video_allowance ?? 0;
                $remVid = $activeWallet->video_credits ?? 0;
                $pctVid = $allowanceVid > 0 ? min(100, (($allowanceVid - $remVid) / $allowanceVid) * 100) : 0;

                $allowanceBImg = $activeWallet->package->branding_image_allowance ?? 0;
                $remBImg = $activeWallet->branding_image_credits ?? 0;
                $pctBImg = $allowanceBImg > 0 ? min(100, (($allowanceBImg - $remBImg) / $allowanceBImg) * 100) : 0;

                $allowanceBVid = $activeWallet->package->branding_video_allowance ?? 0;
                $remBVid = $activeWallet->branding_video_credits ?? 0;
                $pctBVid = $allowanceBVid > 0 ? min(100, (($allowanceBVid - $remBVid) / $allowanceBVid) * 100) : 0;

                $allowanceSoc = $activeWallet->package->social_post_allowance ?? 0;
                $remSoc = $activeWallet->social_post_credits ?? 0;
                $pctSoc = $allowanceSoc > 0 ? min(100, (($allowanceSoc - $remSoc) / $allowanceSoc) * 100) : 0;
            @endphp

            <div class="bg-gradient-to-r from-blue-900/10 to-[#0a0a0a] border border-blue-500/20 rounded-xl p-5 relative overflow-hidden shadow-[0_0_20px_rgba(37,99,235,0.05)]">
                <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-3">
                    <span class="text-[10px] text-blue-400 font-black uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Admin Node Wallet Status
                    </span>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="text-[8px] text-gray-500 font-mono uppercase tracking-widest bg-black/50 px-2 py-1 rounded border border-white/5">
                            Tier: <span class="text-white font-bold ml-1">{{ $activeWallet->package->name }}</span>
                        </span>
                        @if($expiryDate)
                            <span class="text-[8px] font-mono uppercase tracking-widest px-2 py-1 rounded border {{ now()->greaterThan($expiryDate) ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' }}">
                                Exp: {{ \Carbon\Carbon::parse($expiryDate)->timezone('Asia/Dhaka')->format('M d, Y') }}
                            </span>
                        @endif
                    </div>
                </div>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2 flex flex-col items-center text-center relative overflow-hidden">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1">Prompts</span>
                        <div class="text-sm font-black leading-none {{ $remDir <= 0 ? 'text-red-500' : 'text-white' }}">{{ $remDir }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceDir }}</span></div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full"><div class="h-full bg-white transition-all" style="width: {{ 100 - $pctDir }}%"></div></div>
                    </div>
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2 flex flex-col items-center text-center relative overflow-hidden">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1">Image Gens</span>
                        <div class="text-sm font-black leading-none {{ $remImg <= 0 ? 'text-red-500' : 'text-emerald-400' }}">{{ $remImg }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceImg }}</span></div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full"><div class="h-full bg-emerald-500 transition-all" style="width: {{ 100 - $pctImg }}%"></div></div>
                    </div>
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2 flex flex-col items-center text-center relative overflow-hidden">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1">Video Synth</span>
                        <div class="text-sm font-black leading-none {{ $remVid <= 0 ? 'text-red-500' : 'text-pink-400' }}">{{ $remVid }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceVid }}</span></div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full"><div class="h-full bg-pink-500 transition-all" style="width: {{ 100 - $pctVid }}%"></div></div>
                    </div>
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2 flex flex-col items-center text-center relative overflow-hidden">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1">B_Images</span>
                        <div class="text-sm font-black leading-none {{ $remBImg <= 0 ? 'text-red-500' : 'text-blue-400' }}">{{ $remBImg }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceBImg }}</span></div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full"><div class="h-full bg-blue-500 transition-all" style="width: {{ 100 - $pctBImg }}%"></div></div>
                    </div>
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2 flex flex-col items-center text-center relative overflow-hidden">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1">B_Videos</span>
                        <div class="text-sm font-black leading-none {{ $remBVid <= 0 ? 'text-red-500' : 'text-orange-400' }}">{{ $remBVid }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceBVid }}</span></div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full"><div class="h-full bg-orange-500 transition-all" style="width: {{ 100 - $pctBVid }}%"></div></div>
                    </div>
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2 flex flex-col items-center text-center relative overflow-hidden">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1">Social Pub</span>
                        <div class="text-sm font-black leading-none {{ $remSoc <= 0 ? 'text-red-500' : 'text-teal-400' }}">{{ $remSoc }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceSoc }}</span></div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full"><div class="h-full bg-teal-500 transition-all" style="width: {{ 100 - $pctSoc }}%"></div></div>
                    </div>
                </div>
            </div>
        @endif

        {{-- =========================================================================
             4. MAIN CONTENT GRID
             ========================================================================= --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
            
            {{-- LEFT SIDE: HIGHLY COMPACT CREATE FORM (NO-SCROLL DESIGN) --}}
            <div class="xl:col-span-1 sticky top-6 z-10">
                <div class="bg-[#0a0a0a] border border-white/5 rounded-xl shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-cyan-400"></div>
                    
                    <div class="p-5">
                        <h2 class="text-[10px] font-black text-white uppercase tracking-[0.15em] mb-4 flex items-center gap-2 border-b border-white/5 pb-3">
                            <div class="w-5 h-5 rounded flex items-center justify-center bg-blue-500/10 border border-blue-500/20">
                                <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </div>
                            Deploy New Package
                        </h2>
                        
                        <form action="{{ route('admin.packages.store') }}" method="POST" class="space-y-4">
                            @csrf
                            
                            {{-- Basic Info --}}
                            <div>
                                <div>
                                    <label class="block text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Plan Name / Identity</label>
                                    <input type="text" name="name" required placeholder="e.g. Creator Pro" class="w-full bg-black border border-white/10 rounded-lg p-2.5 text-white text-[10px] focus:border-blue-500 outline-none transition-all placeholder-gray-700 font-bold tracking-wide">
                                </div>

                                <div class="grid grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <label class="block text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Price (USD)</label>
                                        <div class="relative">
                                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500 font-black text-[10px]">$</span>
                                            <input type="number" step="0.01" name="price" required placeholder="29.99" class="w-full bg-black border border-white/10 rounded-lg pl-6 p-2.5 text-white text-[10px] focus:border-blue-500 outline-none transition-all placeholder-gray-700 font-mono">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Billing Cycle</label>
                                        <div class="relative">
                                            <select name="billing_cycle" class="w-full bg-black border border-white/10 rounded-lg p-2.5 pr-6 text-white text-[10px] focus:border-blue-500 outline-none appearance-none transition-all uppercase tracking-wider font-bold">
                                                <option value="monthly">Monthly</option>
                                                <option value="yearly">Yearly</option>
                                                <option value="lifetime">Lifetime</option>
                                            </select>
                                            <svg class="w-3 h-3 text-gray-500 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Compact 3-Column Resource Allocations --}}
                            <div class="pt-4 pb-1 border-t border-white/5 relative">
                                <span class="absolute -top-2 left-3 bg-[#0a0a0a] px-2 text-[8px] font-black text-cyan-500 uppercase tracking-[0.2em]">Allocations</span>
                            </div>

                            <div class="grid grid-cols-3 gap-2.5">
                                <div>
                                    <label class="block text-[7px] font-bold text-gray-400 uppercase tracking-wider mb-1 truncate text-center">Prompts</label>
                                    <input type="number" name="directive_allowance" required value="0" min="0" class="w-full bg-[#111] border border-white/5 rounded-lg p-2 text-white text-[10px] focus:border-blue-500 outline-none transition-all font-mono text-center">
                                </div>
                                <div>
                                    <label class="block text-[7px] font-bold text-gray-400 uppercase tracking-wider mb-1 truncate text-center">Img Gen</label>
                                    <input type="number" name="image_allowance" required value="0" min="0" class="w-full bg-[#111] border border-white/5 rounded-lg p-2 text-white text-[10px] focus:border-blue-500 outline-none transition-all font-mono text-center">
                                </div>
                                <div>
                                    <label class="block text-[7px] font-bold text-gray-400 uppercase tracking-wider mb-1 truncate text-center">Vid Synth</label>
                                    <input type="number" name="video_allowance" required value="0" min="0" class="w-full bg-[#111] border border-white/5 rounded-lg p-2 text-white text-[10px] focus:border-blue-500 outline-none transition-all font-mono text-center">
                                </div>
                                <div>
                                    <label class="block text-[7px] font-bold text-gray-400 uppercase tracking-wider mb-1 truncate text-center">B_Image</label>
                                    <input type="number" name="branding_image_allowance" required value="0" min="0" class="w-full bg-[#111] border border-white/5 rounded-lg p-2 text-white text-[10px] focus:border-blue-500 outline-none transition-all font-mono text-center">
                                </div>
                                <div>
                                    <label class="block text-[7px] font-bold text-gray-400 uppercase tracking-wider mb-1 truncate text-center">B_Video</label>
                                    <input type="number" name="branding_video_allowance" required value="0" min="0" class="w-full bg-[#111] border border-white/5 rounded-lg p-2 text-white text-[10px] focus:border-blue-500 outline-none transition-all font-mono text-center">
                                </div>
                                <div>
                                    <label class="block text-[7px] font-bold text-gray-400 uppercase tracking-wider mb-1 truncate text-center">Social</label>
                                    <input type="number" name="social_post_allowance" required value="0" min="0" class="w-full bg-[#111] border border-white/5 rounded-lg p-2 text-white text-[10px] focus:border-blue-500 outline-none transition-all font-mono text-center">
                                </div>
                            </div>
                            
                            <p class="text-[7px] text-gray-600 uppercase tracking-widest text-center italic font-bold leading-tight">Input 0 to disable a specific asset type.</p>

                            <button type="submit" class="w-full mt-2 py-3 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white text-[10px] font-black rounded-lg uppercase tracking-[0.2em] shadow-[0_0_15px_rgba(37,99,235,0.3)] transition-all">
                                Deploy Package
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- RIGHT SIDE: EXISTING PACKAGES GRID --}}
            <div class="xl:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-start">
                    @forelse($packages as $package)
                        <div class="bg-[#0a0a0a] border border-white/5 rounded-xl p-5 shadow-2xl relative overflow-hidden group hover:border-white/10 transition-colors flex flex-col">
                            
                            {{-- Action Buttons --}}
                            <div class="absolute top-4 right-4 flex items-center gap-1.5 z-10">
                                <a href="{{ route('admin.packages.edit', $package->id) }}" title="Edit Package" class="p-2 bg-white/5 hover:bg-blue-500/20 text-gray-400 hover:text-blue-400 rounded-lg transition-all border border-transparent hover:border-blue-500/30 backdrop-blur-md shadow-lg">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>
                                <form action="{{ route('admin.packages.destroy', $package->id) }}" method="POST" class="inline m-0 p-0">
                                    @csrf @method('DELETE')
                                    <button @click.prevent="formToSubmit = $el.closest('form'); deleteModal = true;" type="button" title="Purge Package" class="p-2 bg-white/5 hover:bg-red-500/20 text-gray-400 hover:text-red-400 rounded-lg transition-all border border-transparent hover:border-red-500/30 backdrop-blur-md shadow-lg">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>

                            {{-- Package Identity --}}
                            <div class="mb-6 pr-20 relative z-0">
                                <h3 class="text-white font-black uppercase tracking-[0.15em] text-xs mb-1.5 group-hover:text-blue-400 transition-colors">{{ $package->name }}</h3>
                                <div class="flex items-end gap-1.5">
                                    <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400 leading-none drop-shadow-md">${{ $package->price }}</span>
                                    <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mb-0.5 bg-white/5 px-1.5 py-0.5 rounded border border-white/5">/ {{ $package->billing_cycle }}</span>
                                </div>
                            </div>

                            {{-- Resource Allocations List --}}
                            <div class="space-y-2 mt-auto bg-black border border-white/5 p-4 rounded-lg shadow-inner relative z-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        <span class="uppercase tracking-wider text-[9px] font-bold">Neural Prompts</span>
                                    </div>
                                    <span class="text-white font-mono font-black text-[11px]">{{ $package->directive_allowance }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span class="uppercase tracking-wider text-[9px] font-bold">Image Gens</span>
                                    </div>
                                    <span class="text-white font-mono font-black text-[11px]">{{ $package->image_allowance }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-3 h-3 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                        <span class="uppercase tracking-wider text-[9px] font-bold">Video Synth</span>
                                    </div>
                                    <span class="text-white font-mono font-black text-[11px]">{{ $package->video_allowance }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                                        <span class="uppercase tracking-wider text-[9px] font-bold">Branding: Images</span>
                                    </div>
                                    <span class="text-white font-mono font-black text-[11px]">{{ $package->branding_image_allowance }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-3 h-3 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                                        <span class="uppercase tracking-wider text-[9px] font-bold">Branding: Videos</span>
                                    </div>
                                    <span class="text-white font-mono font-black text-[11px]">{{ $package->branding_video_allowance }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-3 h-3 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                        <span class="uppercase tracking-wider text-[9px] font-bold">Social Broadcasts</span>
                                    </div>
                                    <span class="text-white font-mono font-black text-[11px]">{{ $package->social_post_allowance }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full flex flex-col items-center justify-center py-16 px-6 bg-[#0a0a0a] border border-white/5 rounded-2xl border-dashed">
                            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-4 shadow-inner">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </div>
                            <h3 class="text-sm font-black text-white uppercase tracking-[0.2em] mb-1">No Monetization Plans Deployed</h3>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest text-center max-w-xs">Use the console on the left to configure and deploy your first SaaS package.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- =========================================================================
             ALPINE.JS DELETE CONFIRMATION MODAL
             ========================================================================= --}}
        <template x-teleport="body">
            <div x-show="deleteModal" 
                 x-cloak
                 class="fixed inset-0 z-[5000] flex items-center justify-center bg-black/90 backdrop-blur-xl px-4">
                
                <div class="bg-gradient-to-t from-[#050505] to-[#0a0a0a] border border-red-500/30 rounded-xl p-6 w-full max-w-sm shadow-[0_0_50px_rgba(239,68,68,0.15)] relative overflow-hidden" 
                     @click.away="deleteModal = false" 
                     x-show="deleteModal" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">
                    
                    <div class="w-12 h-12 bg-red-500/10 border border-red-500/20 text-red-500 rounded-full mx-auto flex items-center justify-center mb-4 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    
                    <div class="text-center mb-6">
                        <h3 class="text-sm font-black text-white uppercase tracking-widest mb-1.5">Purge Monetization Plan?</h3>
                        <p class="text-[10px] text-gray-400 font-bold leading-relaxed px-2">
                            Are you absolutely sure you want to permanently delete this package? <br><br>
                            <span class="text-emerald-400 font-black">Note:</span> Users currently subscribed to this tier will <strong class="text-white">not</strong> be affected until their cycle expires.
                        </p>
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="deleteModal = false" type="button" class="w-1/2 px-4 py-3 bg-white/5 hover:bg-white/10 text-gray-300 text-[9px] font-black uppercase tracking-widest rounded-lg transition-colors border border-white/5 hover:border-white/20">
                            Cancel
                        </button>
                        <button 
                            @click="
                                formToSubmit.querySelector('button[type=submit]').disabled = true;
                                $el.innerHTML = 'PURGING...';
                                $el.classList.add('opacity-50', 'cursor-not-allowed');
                                formToSubmit.submit();
                            " 
                            type="button" 
                            class="w-1/2 px-4 py-3 bg-red-600 hover:bg-red-500 text-white text-[9px] font-black uppercase tracking-widest rounded-lg transition-all shadow-[0_0_20px_rgba(220,38,38,0.3)] hover:-translate-y-0.5"
                        >
                            Confirm Purge
                        </button>
                    </div>
                </div>
            </div>
        </template>

    </div>

    {{-- =========================================================================
         GLOBAL STYLES & ANIMATIONS
         ========================================================================= --}}
    <style>
        [x-cloak] { display: none !important; }

        body {
            background-color: #030303;
            color: #e5e7eb;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background-image: radial-gradient(circle at 50% 0%, #1e3a8a 0%, transparent 20%), 
                              radial-gradient(circle at 100% 100%, #064e3b 0%, transparent 20%);
            background-attachment: fixed;
        }

        /* Webkit Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #222; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #444; }

        /* Chrome/Safari Number Input Arrows removal */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }

        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }
    </style>
</x-app-layout>