<x-app-layout>
    @push('vite-scripts')
        @vite(['resources/js/dashboard-charts.js'])
    @endpush

    {{-- Main Container --}}
    <div class="max-w-7xl mx-auto pt-6 pb-12 px-4 sm:px-6 lg:px-8 space-y-8 antialiased selection:bg-blue-500/30">
        
        @php
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

        {{-- 2. HEADER --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
            <div>
                <h1 class="text-xl font-black text-white tracking-wide">
                    Welcome, <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-300">{{ Auth::user()->name }}</span>
                </h1>
                <p class="text-[10px] text-gray-500 font-mono uppercase tracking-widest mt-1">
                    {{ now('Asia/Dhaka')->format('l, M d, Y') }}
                    <span class="text-gray-700 mx-2">·</span>
                    <span class="{{ $processingCount > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                        {{ $processingCount > 0 ? 'Rendering' : 'System stable' }}
                    </span>
                </p>
            </div>
            <div class="bg-[#0a0a0a] border border-white/10 px-4 py-2 rounded-lg">
                <span id="live-clock" class="text-sm font-mono font-bold text-blue-400">{{ now('Asia/Dhaka')->format('h:i:s A') }}</span>
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

        {{-- 5. TODAY + LIFETIME OVERVIEW --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1 rounded-2xl border border-emerald-500/20 bg-gradient-to-br from-emerald-500/10 to-[#0a0a0a] p-6 flex flex-col justify-center">
                <p class="text-[10px] font-black text-emerald-400 uppercase tracking-[0.2em] mb-2">Made Today</p>
                <p class="text-5xl font-black text-white leading-none">{{ $stats['today']['total'] }}</p>
                <p class="text-[10px] text-gray-500 mt-3 font-mono">
                    @if($stats['today']['total'] === 0)
                        No new assets today
                    @else
                        Img {{ $stats['today']['cgi_images'] }} · Vid {{ $stats['today']['cgi_videos'] }} · Occasion {{ $stats['today']['occasion'] }}
                    @endif
                </p>
            </div>
            <div class="md:col-span-2 rounded-2xl border border-white/5 bg-[#0a0a0a] p-6 grid grid-cols-2 sm:grid-cols-4 gap-4 items-center">
                <div class="text-center">
                    <p class="text-[9px] text-gray-500 uppercase font-black tracking-widest mb-1">Lifetime Total</p>
                    <p class="text-3xl font-black text-white">{{ $stats['lifetime_total'] }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[9px] text-gray-500 uppercase font-black tracking-widest mb-1">CGI Images</p>
                    <p class="text-3xl font-black text-blue-400">{{ array_sum($stats['cgi']['images']) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[9px] text-gray-500 uppercase font-black tracking-widest mb-1">CGI Videos</p>
                    <p class="text-3xl font-black text-purple-400">{{ array_sum($stats['cgi']['videos']) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-[9px] text-gray-500 uppercase font-black tracking-widest mb-1">Occasion</p>
                    <p class="text-3xl font-black text-pink-400">{{ array_sum($stats['occasion']['images']) + $stats['occasion']['posted'] }}</p>
                </div>
            </div>
        </div>

        {{-- 6. NEURAL ANALYTICS CHARTS --}}
        <script type="application/json" id="dashboard-chart-data">@json($stats['charts'])</script>
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 relative rounded-2xl border border-white/5 bg-[#0a0a0a] p-6 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 via-transparent to-purple-500/5 pointer-events-none"></div>
                <div class="relative">
                    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6 pb-4 border-b border-white/5">
                        <div>
                            <h2 class="text-[11px] font-black text-cyan-400 uppercase tracking-[0.2em] flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-cyan-400 shadow-[0_0_10px_#22d3ee]"></span>
                                Neural Output · 14 Days
                            </h2>
                            <p class="text-[10px] text-gray-500 mt-1">Your daily production with eGStudio AI</p>
                        </div>
                        <span class="text-[9px] font-mono text-gray-600 uppercase tracking-widest">Line trend</span>
                    </div>
                    <div class="h-[280px] sm:h-[320px]">
                        <canvas id="neural-line-chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="relative rounded-2xl border border-white/5 bg-[#0a0a0a] p-6 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-tr from-pink-500/5 via-transparent to-blue-500/5 pointer-events-none"></div>
                <div class="relative h-full flex flex-col">
                    <div class="mb-4 pb-4 border-b border-white/5">
                        <h2 class="text-[11px] font-black text-pink-400 uppercase tracking-[0.2em] flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-pink-500 shadow-[0_0_10px_#ec4899]"></span>
                            Asset Mix
                        </h2>
                        <p class="text-[10px] text-gray-500 mt-1">Lifetime breakdown by type</p>
                    </div>
                    <div class="flex-1 min-h-[260px]">
                        <canvas id="neural-pie-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- 7. STUDIO BREAKDOWN --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- CGI Studio --}}
            <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl p-6 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-white/5">
                    <h2 class="text-[11px] font-black text-blue-400 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        CGI Studio
                    </h2>
                </div>

                {{-- Images --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Images</p>
                        <a href="{{ route('cgi.images') }}" class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">Gallery →</a>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach([
                            ['label' => 'Raw', 'total' => $stats['cgi']['images']['raw'], 'today' => $stats['today']['cgi_image_raw'], 'color' => 'text-white', 'border' => 'border-white/5'],
                            ['label' => 'Branded', 'total' => $stats['cgi']['images']['branded'], 'today' => $stats['today']['cgi_image_branded'], 'color' => 'text-emerald-400', 'border' => 'border-emerald-500/10'],
                            ['label' => 'Templated', 'total' => $stats['cgi']['images']['templated'], 'today' => $stats['today']['cgi_image_templated'], 'color' => 'text-orange-400', 'border' => 'border-orange-500/10'],
                        ] as $row)
                        <div class="bg-black/50 border {{ $row['border'] }} rounded-xl p-3 text-center">
                            <p class="text-[8px] font-bold text-gray-500 uppercase tracking-widest mb-1">{{ $row['label'] }}</p>
                            <p class="text-xl font-black {{ $row['color'] }}">{{ $row['total'] }}</p>
                            <p class="text-[8px] text-gray-600 font-mono mt-1">+{{ $row['today'] }} today</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Videos --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Videos</p>
                        <a href="{{ route('cgi.videos') }}" class="text-[8px] font-black text-gray-600 hover:text-white uppercase tracking-widest transition-colors">Gallery →</a>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach([
                            ['label' => 'Raw', 'total' => $stats['cgi']['videos']['raw'], 'today' => $stats['today']['cgi_video_raw'], 'color' => 'text-white', 'border' => 'border-white/5'],
                            ['label' => 'Branded', 'total' => $stats['cgi']['videos']['branded'], 'today' => $stats['today']['cgi_video_branded'], 'color' => 'text-pink-400', 'border' => 'border-pink-500/10'],
                            ['label' => 'Templated', 'total' => $stats['cgi']['videos']['templated'], 'today' => $stats['today']['cgi_video_templated'], 'color' => 'text-purple-400', 'border' => 'border-purple-500/10'],
                        ] as $row)
                        <div class="bg-black/50 border {{ $row['border'] }} rounded-xl p-3 text-center">
                            <p class="text-[8px] font-bold text-gray-500 uppercase tracking-widest mb-1">{{ $row['label'] }}</p>
                            <p class="text-xl font-black {{ $row['color'] }}">{{ $row['total'] }}</p>
                            <p class="text-[8px] text-gray-600 font-mono mt-1">+{{ $row['today'] }} today</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Occasion Studio --}}
            <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl p-6 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-white/5">
                    <h2 class="text-[11px] font-black text-pink-400 uppercase tracking-[0.2em] flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                        Occasion Studio
                    </h2>
                    <a href="{{ route('occasions.gallery') }}" class="text-[9px] font-black text-gray-500 hover:text-white uppercase tracking-widest transition-colors">Gallery →</a>
                </div>

                <div>
                    <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3">Images</p>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach([
                            ['label' => 'Raw', 'total' => $stats['occasion']['images']['raw'], 'today' => $stats['today']['occasion_image_raw'], 'color' => 'text-white', 'border' => 'border-white/5'],
                            ['label' => 'Branded', 'total' => $stats['occasion']['images']['branded'], 'today' => $stats['today']['occasion_image_branded'], 'color' => 'text-blue-400', 'border' => 'border-blue-500/10'],
                            ['label' => 'Merged', 'total' => $stats['occasion']['images']['templated'], 'today' => $stats['today']['occasion_image_templated'], 'color' => 'text-indigo-400', 'border' => 'border-indigo-500/10'],
                        ] as $row)
                        <div class="bg-black/50 border {{ $row['border'] }} rounded-xl p-3 text-center">
                            <p class="text-[8px] font-bold text-gray-500 uppercase tracking-widest mb-1">{{ $row['label'] }}</p>
                            <p class="text-xl font-black {{ $row['color'] }}">{{ $row['total'] }}</p>
                            <p class="text-[8px] text-gray-600 font-mono mt-1">+{{ $row['today'] }} today</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3">Social Posts</p>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="bg-black/50 border border-rose-500/10 rounded-xl p-4 text-center">
                            <p class="text-[8px] font-bold text-gray-500 uppercase tracking-widest mb-1">Posted to Social</p>
                            <p class="text-2xl font-black text-rose-400">{{ $stats['occasion']['posted'] }}</p>
                            <p class="text-[8px] text-gray-600 font-mono mt-1">+{{ $stats['today']['posted'] }} today</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 8. QUICK ACTIONS --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="{{ route('cgi.create') }}" class="group flex items-center gap-3 bg-blue-600/10 hover:bg-blue-600/20 border border-blue-500/20 hover:border-blue-500/40 rounded-xl px-5 py-4 transition-all">
                <div class="w-9 h-9 rounded-lg bg-blue-600/20 flex items-center justify-center text-blue-400 group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-white uppercase tracking-widest">CGI Studio</p>
                    <p class="text-[8px] text-gray-500 uppercase tracking-wider mt-0.5">New generation</p>
                </div>
            </a>
            <a href="{{ route('occasions.create') }}" class="group flex items-center gap-3 bg-pink-600/10 hover:bg-pink-600/20 border border-pink-500/20 hover:border-pink-500/40 rounded-xl px-5 py-4 transition-all">
                <div class="w-9 h-9 rounded-lg bg-pink-600/20 flex items-center justify-center text-pink-400 group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-white uppercase tracking-widest">Occasion</p>
                    <p class="text-[8px] text-gray-500 uppercase tracking-wider mt-0.5">New campaign</p>
                </div>
            </a>
            <a href="{{ route('cgi.images') }}" class="group flex items-center gap-3 bg-[#0a0a0a] hover:bg-white/5 border border-white/5 hover:border-white/15 rounded-xl px-5 py-4 transition-all">
                <div class="w-9 h-9 rounded-lg bg-white/5 flex items-center justify-center text-gray-400 group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-white uppercase tracking-widest">Image Gallery</p>
                    <p class="text-[8px] text-gray-500 uppercase tracking-wider mt-0.5">View assets</p>
                </div>
            </a>
            <a href="{{ route('cgi.videos') }}" class="group flex items-center gap-3 bg-[#0a0a0a] hover:bg-white/5 border border-white/5 hover:border-white/15 rounded-xl px-5 py-4 transition-all">
                <div class="w-9 h-9 rounded-lg bg-white/5 flex items-center justify-center text-gray-400 group-hover:scale-110 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <p class="text-[10px] font-black text-white uppercase tracking-widest">Video Gallery</p>
                    <p class="text-[8px] text-gray-500 uppercase tracking-wider mt-0.5">View renders</p>
                </div>
            </a>
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