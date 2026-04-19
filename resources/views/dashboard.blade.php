<x-app-layout>
    {{-- Main Container --}}
    <div class="max-w-7xl mx-auto pt-6 pb-12 px-4 sm:px-6 lg:px-8 space-y-8 antialiased selection:bg-blue-500/30">
        
        {{-- DYNAMIC CALCULATIONS --}}
        @php
            // Calculate dynamic percentages for the Pipeline Assets
            $totalImages = $generations->whereNotNull('image_url')->count();
            $totalVideos = $generations->whereNotNull('video_url')->count();
            $totalAssets = $totalImages + $totalVideos;
            
            $imagePercentage = $totalAssets > 0 ? round(($totalImages / $totalAssets) * 100) : 0;
            $videoPercentage = $totalAssets > 0 ? round(($totalVideos / $totalAssets) * 100) : 0;

            // Multi-Wallet Active Status Logic
            $user = auth()->user();
            
            // Find the currently active wallet
            $activeWallet = \App\Models\UserPackage::with('package')
                ->where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->first();

            $expiryDate = $activeWallet ? $activeWallet->expires_at : null;
        @endphp

        {{-- 1. ALERTS & NOTIFICATIONS --}}
        @if (session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 p-4 rounded-xl flex items-center gap-3 shadow-[0_0_20px_rgba(16,185,129,0.15)] backdrop-blur-sm">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-xs font-black text-emerald-400 uppercase tracking-widest">{{ session('success') }}</p>
            </div>
        @endif

        {{-- NEW: NEURAL WALLET (CREDITS STATUS) AT THE VERY TOP --}}
        @if($activeWallet && $activeWallet->package)
            @php
                // 1. Directives (Prompts)
                $remDir = $activeWallet->directive_credits ?? 0;
                $allowanceDir = max($activeWallet->package->directive_allowance ?? 0, $remDir);
                $pctDir = $allowanceDir > 0 ? min(100, ($remDir / $allowanceDir) * 100) : 0;

                // 2. Images
                $remImg = $activeWallet->image_credits ?? 0;
                $allowanceImg = max($activeWallet->package->image_allowance ?? 0, $remImg);
                $pctImg = $allowanceImg > 0 ? min(100, ($remImg / $allowanceImg) * 100) : 0;

                // 3. Videos
                $remVid = $activeWallet->video_credits ?? 0;
                $allowanceVid = max($activeWallet->package->video_allowance ?? 0, $remVid);
                $pctVid = $allowanceVid > 0 ? min(100, ($remVid / $allowanceVid) * 100) : 0;

                // 4. Brand Images (B_Images)
                $remBImg = $activeWallet->branding_image_credits ?? 0;
                $allowanceBImg = max($activeWallet->package->branding_image_allowance ?? $activeWallet->package->branding_image ?? 0, $remBImg);
                $pctBImg = $allowanceBImg > 0 ? min(100, ($remBImg / $allowanceBImg) * 100) : 0;

                // 5. Brand Videos (B_Videos)
                $remBVid = $activeWallet->branding_video_credits ?? 0;
                $allowanceBVid = max($activeWallet->package->branding_video_allowance ?? $activeWallet->package->branding_video ?? 0, $remBVid);
                $pctBVid = $allowanceBVid > 0 ? min(100, ($remBVid / $allowanceBVid) * 100) : 0;

                // 6. Social Post
                $remSoc = $activeWallet->social_post_credits ?? 0;
                $allowanceSoc = max($activeWallet->package->social_post_allowance ?? $activeWallet->package->social_allowance ?? $activeWallet->package->social_posts ?? 0, $remSoc);
                $pctSoc = $allowanceSoc > 0 ? min(100, ($remSoc / $allowanceSoc) * 100) : 0;
            @endphp

            <div class="bg-gradient-to-r from-blue-900/10 to-[#0a0a0a] border border-blue-500/20 rounded-2xl p-5 relative overflow-hidden group hover:border-blue-500/40 transition-colors shadow-[0_0_30px_rgba(37,99,235,0.05)]">
                
                {{-- Decorative glowing line --}}
                <div class="absolute top-0 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-3">
                    <span class="text-[10px] text-blue-400 font-black uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Neural Wallet Usage
                    </span>
                    <div class="flex items-center gap-2.5">
                        <span class="text-[8px] text-gray-500 font-mono uppercase tracking-widest bg-black/50 px-2 py-1 rounded border border-white/5">
                            Tier: <span class="text-white font-bold ml-1">{{ $activeWallet->package->name }}</span>
                        </span>
                        @if($expiryDate)
                            <div class="flex items-center gap-2">
                                <span class="text-[8px] font-mono uppercase tracking-widest px-2 py-1 rounded border {{ now()->greaterThan($expiryDate) ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' }}">
                                    Exp: {{ \Carbon\Carbon::parse($expiryDate)->timezone('Asia/Dhaka')->format('M d, Y - h:i A') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- THE 6 COMPACT CREDIT CARDS (REMAINING / TOTAL) --}}
                <div class="grid grid-cols-3 md:grid-cols-6 gap-2">
                    
                    {{-- 1. Prompts --}}
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2.5 flex flex-col items-center justify-center text-center hover:bg-white/5 transition-colors relative group/item">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1 line-clamp-1">Prompts</span>
                        <div class="text-base font-black leading-none {{ $remDir <= 0 ? 'text-red-500' : 'text-white' }}">
                            {{ $remDir }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceDir }}</span>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full rounded-b-lg overflow-hidden">
                            <div class="h-full bg-white transition-all" style="width: {{ $pctDir }}%"></div>
                        </div>
                    </div>

                    {{-- 2. Image Gens --}}
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2.5 flex flex-col items-center justify-center text-center hover:bg-white/5 transition-colors relative group/item">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1 line-clamp-1">Image Gens</span>
                        <div class="text-base font-black leading-none {{ $remImg <= 0 ? 'text-red-500' : 'text-emerald-400' }}">
                            {{ $remImg }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceImg }}</span>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full rounded-b-lg overflow-hidden">
                            <div class="h-full bg-emerald-500 transition-all" style="width: {{ $pctImg }}%"></div>
                        </div>
                    </div>

                    {{-- 3. Video Synth --}}
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2.5 flex flex-col items-center justify-center text-center hover:bg-white/5 transition-colors relative group/item">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1 line-clamp-1">Video Synth</span>
                        <div class="text-base font-black leading-none {{ $remVid <= 0 ? 'text-red-500' : 'text-pink-400' }}">
                            {{ $remVid }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceVid }}</span>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full rounded-b-lg overflow-hidden">
                            <div class="h-full bg-pink-500 transition-all" style="width: {{ $pctVid }}%"></div>
                        </div>
                    </div>

                    {{-- 4. B_Images --}}
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2.5 flex flex-col items-center justify-center text-center hover:bg-white/5 transition-colors relative group/item">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1 line-clamp-1">B_Images</span>
                        <div class="text-base font-black leading-none {{ $remBImg <= 0 ? 'text-red-500' : 'text-blue-400' }}">
                            {{ $remBImg }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceBImg }}</span>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full rounded-b-lg overflow-hidden">
                            <div class="h-full bg-blue-500 transition-all" style="width: {{ $pctBImg }}%"></div>
                        </div>
                    </div>

                    {{-- 5. B_Videos --}}
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2.5 flex flex-col items-center justify-center text-center hover:bg-white/5 transition-colors relative group/item">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1 line-clamp-1">B_Videos</span>
                        <div class="text-base font-black leading-none {{ $remBVid <= 0 ? 'text-red-500' : 'text-orange-400' }}">
                            {{ $remBVid }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceBVid }}</span>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full rounded-b-lg overflow-hidden">
                            <div class="h-full bg-orange-500 transition-all" style="width: {{ $pctBVid }}%"></div>
                        </div>
                    </div>

                    {{-- 6. Social Pub --}}
                    <div class="bg-black/80 border border-white/5 rounded-lg p-2.5 flex flex-col items-center justify-center text-center hover:bg-white/5 transition-colors relative group/item">
                        <span class="text-[7px] font-bold text-gray-500 uppercase tracking-widest mb-1 line-clamp-1">Social Pub</span>
                        <div class="text-base font-black leading-none {{ $remSoc <= 0 ? 'text-red-500' : 'text-teal-400' }}">
                            {{ $remSoc }}<span class="text-[8px] text-gray-600 font-mono ml-0.5">/{{ $allowanceSoc }}</span>
                        </div>
                        <div class="absolute bottom-0 left-0 h-0.5 bg-gray-600/30 w-full rounded-b-lg overflow-hidden">
                            <div class="h-full bg-teal-500 transition-all" style="width: {{ $pctSoc }}%"></div>
                        </div>
                    </div>

                </div>
            </div>
        @endif

        @can('subscribe_to_packages')
            @php
                $pendingBill = \App\Models\Billing::where('user_id', auth()->id())->where('status', 'due')->latest()->first();
            @endphp
            @if($pendingBill)
                <div class="bg-gradient-to-r from-orange-500/10 to-[#0a0a0a] border border-orange-500/30 rounded-2xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 shadow-[0_0_25px_rgba(249,115,22,0.1)] relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-orange-500 animate-pulse"></div>
                    <div class="flex items-center gap-5">
                        <div class="w-12 h-12 bg-orange-500/10 rounded-full flex items-center justify-center text-orange-400 flex-shrink-0 border border-orange-500/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-orange-400 text-xs font-black uppercase tracking-widest mb-1">Payment Action Required</h3>
                            <p class="text-[11px] text-gray-400 font-medium">
                                Invoice <span class="text-white font-mono">#{{ $pendingBill->invoice_no }}</span> for <span class="text-white font-bold">{{ $pendingBill->package->name ?? 'Selected Package' }}</span> is <span class="text-orange-500 font-bold uppercase tracking-wider">Due</span>.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col items-start sm:items-end w-full sm:w-auto">
                        <span class="text-2xl font-black text-white tracking-tight">${{ number_format($pendingBill->amount, 2) }}</span>
                        <span class="text-[9px] text-orange-400/80 font-black uppercase tracking-[0.2em] mt-1">Awaiting Admin Activation</span>
                    </div>
                </div>
            @endif
        @endcan

        {{-- 2. HEADER SECTION --}}
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 pb-6 border-b border-white/5">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <div class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                    </div>
                    <h1 class="text-2xl font-black tracking-widest text-white uppercase">
                        Core <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">Operations</span>
                    </h1>
                </div>
                <p class="text-[11px] text-gray-500 font-mono uppercase tracking-widest">
                    ID: 0x{{ substr(md5(Auth::id()), 0, 8) }} <span class="text-gray-600 px-2">|</span> Node_Primary
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4 lg:gap-8 bg-[#0a0a0a] p-4 rounded-xl border border-white/5">
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <span class="block text-[9px] text-gray-500 uppercase font-black tracking-widest mb-1">Total Assets</span>
                        <span class="text-lg font-mono font-bold text-white">{{ $totalAssets }}</span>
                    </div>
                    <div class="w-px h-8 bg-white/10"></div>
                    <div class="text-left">
                        <span class="block text-[9px] text-gray-500 uppercase font-black tracking-widest mb-1">System Load</span>
                        <span class="text-sm font-black uppercase tracking-wider {{ $generations->where('status', 'processing')->count() > 0 ? 'text-amber-400 animate-pulse' : 'text-emerald-400' }}">
                            {{ $generations->where('status', 'processing')->count() > 0 ? 'Rendering' : 'Stable' }}
                        </span>
                    </div>
                </div>
                <div class="hidden lg:block w-px h-8 bg-white/10"></div>
                <div class="bg-black border border-white/10 px-4 py-2 rounded-lg flex items-center justify-center min-w-[100px]">
                    <span id="live-clock" class="text-sm font-mono font-bold text-blue-400">
                        {{ now('Asia/Dhaka')->format('h:i:s A') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 3. ADMIN ONLY COMMAND PANEL --}}
        @if(auth()->check() && auth()->user()->role === 'admin')
            <div class="bg-gradient-to-r from-purple-900/20 to-[#0a0a0a] border border-purple-500/30 p-5 rounded-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-[0_0_20px_rgba(168,85,247,0.05)]">
                <div>
                    <h2 class="text-[11px] font-black text-purple-400 uppercase tracking-widest flex items-center gap-2 mb-1.5">
                        <span class="w-2 h-2 bg-purple-500 rounded-full shadow-[0_0_8px_#a855f7] animate-pulse"></span>
                        Admin Clearance Active
                    </h2>
                    <p class="text-[10px] text-gray-400 font-medium">System-wide modification and provisioning granted.</p>
                </div>
                <div class="flex gap-3 w-full md:w-auto">
                    <a href="{{ route('admin.users.index') }}" class="flex-1 md:flex-none text-center px-5 py-2.5 bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded-lg text-[10px] font-black uppercase tracking-widest transition-colors">
                        Agent Roster
                    </a>
                    <a href="{{ route('admin.users.create') }}" class="flex-1 md:flex-none text-center px-5 py-2.5 bg-purple-600 hover:bg-purple-500 text-white rounded-lg text-[10px] font-black uppercase tracking-widest shadow-lg shadow-purple-600/20 transition-all flex items-center justify-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Provision Agent
                    </a>
                </div>
            </div>
        @endif

        {{-- 5. MAIN DASHBOARD GRID --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Operator Console (Wider) --}}
            <div class="lg:col-span-2 bg-[#0a0a0a] border border-white/5 rounded-2xl p-6 md:p-8 flex flex-col justify-between relative overflow-hidden group hover:border-blue-500/30 transition-colors">
                
                {{-- Decorative background elements --}}
                <div class="absolute -right-20 -top-20 opacity-10 pointer-events-none">
                    <svg width="200" height="200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/5">
                        <span class="text-[11px] text-gray-400 font-black uppercase tracking-widest flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Console_Status
                        </span>
                        <span class="text-[10px] text-blue-400/70 font-mono italic animate-pulse">Awaiting parameters...</span>
                    </div>
                    
                    <div class="space-y-5">
                        <div>
                            <h2 class="text-lg font-black text-white mb-2 tracking-tight">Operator Interface Ready</h2>
                            <p class="text-sm text-gray-500 leading-relaxed max-w-xl">
                                Neural generation engine is synchronized and online. All rendering subsystems are reporting nominal performance. You are cleared to input high-fidelity directives.
                            </p>
                        </div>

                        <div class="inline-flex items-center gap-3 bg-black border border-white/10 px-4 py-2.5 rounded-lg shadow-inner">
                            <div class="h-2 w-2 rounded-full bg-blue-500 animate-pulse"></div>
                            <span class="text-xs font-mono font-bold text-blue-300">{{ Auth::user()->name }}</span>
                            <span class="text-gray-700">|</span>
                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">{{ Auth::user()->role }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-white/5">
                    <a href="{{ route('cgi.create') }}" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-8 py-3.5 bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-black uppercase tracking-widest rounded-xl shadow-[0_0_20px_rgba(37,99,235,0.3)] transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Launch Generation Task
                    </a>
                </div>
            </div>

            {{-- Asset Distribution (DYNAMIC PROGRESS BARS) --}}
            <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl p-6 md:p-8 flex flex-col justify-between group hover:border-white/20 transition-colors">
                <div>
                    <span class="text-[11px] text-gray-400 font-black uppercase tracking-widest block mb-6 flex items-center gap-2 pb-4 border-b border-white/5">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                        Pipeline Assets
                    </span>
                    
                    <div class="space-y-6">
                        {{-- Images Dynamic Bar --}}
                        <div>
                            <div class="flex items-end justify-between mb-2">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Images</span>
                                <div class="text-right">
                                    <span class="text-lg font-mono font-black text-white leading-none">{{ $totalImages }}</span>
                                    <span class="text-[9px] font-mono text-blue-400 ml-1">{{ $imagePercentage }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-black border border-white/10 h-2 rounded-full overflow-hidden p-[1px]">
                                <div class="bg-gradient-to-r from-blue-600 to-cyan-400 h-full rounded-full transition-all duration-1000 ease-out" style="width: {{ $imagePercentage }}%"></div>
                            </div>
                        </div>
                        
                        {{-- Videos Dynamic Bar --}}
                        <div>
                            <div class="flex items-end justify-between mb-2">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Videos</span>
                                <div class="text-right">
                                    <span class="text-lg font-mono font-black text-white leading-none">{{ $totalVideos }}</span>
                                    <span class="text-[9px] font-mono text-pink-400 ml-1">{{ $videoPercentage }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-black border border-white/10 h-2 rounded-full overflow-hidden p-[1px]">
                                <div class="bg-gradient-to-r from-pink-600 to-purple-500 h-full rounded-full transition-all duration-1000 ease-out" style="width: {{ $videoPercentage }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-5 border-t border-white/5">
                    <div class="flex justify-between items-center text-[10px] text-gray-500 font-mono">
                        <span>Total Output</span>
                        <span class="text-emerald-400">{{ $totalAssets }} Rendered</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 7. FOOTER / BRANDING --}}
        <div class="flex flex-col sm:flex-row items-center justify-between pt-8 pb-4 border-t border-white/5 gap-4">
            <div class="flex items-center gap-2 opacity-60 hover:opacity-100 transition-opacity cursor-default">
                <p class="text-[10px] font-black tracking-[0.3em] text-gray-500 uppercase">
                    Powered by <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">eGeneration</span>
                </p>
            </div>
            
            <div class="flex items-center gap-4 opacity-40">
                <span class="text-[9px] font-mono text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Latency: 24ms
                </span>
                <span class="text-gray-700">|</span>
                <span class="text-[9px] font-mono text-gray-400 uppercase tracking-widest flex items-center gap-1.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Encrypted
                </span>
            </div>
        </div>
    </div>

    {{-- SCRIPTS --}}
    <script>
        function updateClock() {
            const clock = document.getElementById('live-clock');
            if (!clock) return;
            const now = new Date();
            clock.textContent = now.toLocaleTimeString('en-US', { hour12: true });
        }
        setInterval(updateClock, 1000);
    </script>

    {{-- GLOBAL STYLES --}}
    <style>
        body { 
            background-color: #030303; 
            color: #e5e7eb;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background-image: radial-gradient(circle at 50% 0%, #1e3a8a 0%, transparent 20%), 
                              radial-gradient(circle at 100% 100%, #064e3b 0%, transparent 20%);
            background-attachment: fixed;
        }
    </style>
</x-app-layout>