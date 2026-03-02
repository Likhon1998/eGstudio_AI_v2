<x-app-layout>
    {{-- Reduced top gap using pt-2 --}}
    <div class="max-w-6xl mx-auto pt-2 pb-8 px-6 space-y-6 antialiased">
        
        {{-- 1. HEADER SECTION --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between border-b border-white/10 pb-6 gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 bg-blue-500 rounded-full shadow-[0_0_8px_#3b82f6] animate-pulse"></div>
                    <h1 class="text-base font-bold tracking-tight text-white uppercase">
                        Core <span class="text-blue-500">Operations</span>
                    </h1>
                </div>
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-widest">
                    ID: 0x{{ substr(md5(Auth::id()), 0, 8) }} // Node_Primary
                </p>
            </div>

            <div class="flex items-center gap-8">
                {{-- Compact Stats --}}
                <div class="flex gap-6">
                    <div class="text-right">
                        <span class="block text-[9px] text-gray-500 uppercase font-bold tracking-tighter">Total Renders</span>
                        <span class="text-sm font-mono text-white">{{ $generations->whereNotNull('image_url')->count() + $generations->whereNotNull('video_url')->count() }}</span>
                    </div>
                    <div class="text-right border-l border-white/10 pl-6">
                        <span class="block text-[9px] text-gray-500 uppercase font-bold tracking-tighter">System Load</span>
                        <span class="text-sm font-mono {{ $generations->where('status', 'processing')->count() > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                            {{ $generations->where('status', 'processing')->count() > 0 ? 'Active' : 'Stable' }}
                        </span>
                    </div>
                </div>

                {{-- Live Clock --}}
                <div class="bg-white/5 px-3 py-1.5 rounded border border-white/10">
                    <span id="live-clock" class="text-xs font-mono font-medium text-blue-400">
                        {{ now()->format('H:i:s') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 2. MAIN DASHBOARD GRID --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            {{-- Operator Console --}}
            <div class="md:col-span-2 bg-[#0d0d0d] border border-white/10 rounded-lg p-5">
                <div class="flex items-center justify-between mb-4 border-b border-white/5 pb-2">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Console_Status</span>
                    <span class="text-[9px] text-blue-500/50 font-mono italic">Ready for directives...</span>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-200 mb-1 tracking-tight">Operator Console Access</h2>
                        <p class="text-xs text-gray-500 leading-relaxed max-w-lg">
                            Neural engine synchronized. Awaiting high-fidelity rendering directives. 
                            All subsystems reporting nominal performance.
                        </p>
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <div class="bg-blue-500/5 border border-blue-500/20 px-3 py-2 rounded flex items-center gap-3">
                            <div class="h-1.5 w-1.5 rounded-full bg-blue-500"></div>
                            <span class="text-[11px] font-mono text-blue-300">{{ Auth::user()->name }}</span>
                            <span class="text-[10px] text-gray-600">/</span>
                            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-tighter">{{ Auth::user()->role ?? 'ROOT' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Asset Distribution --}}
            <div class="bg-[#0d0d0d] border border-white/10 rounded-lg p-5 flex flex-col justify-between">
                <div>
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest block mb-4">Pipeline Assets</span>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-gray-500">Made Images</span>
                            <span class="font-mono text-white">{{ $generations->whereNotNull('image_url')->count() }}</span>
                        </div>
                        <div class="w-full bg-white/5 h-1 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full" style="width: 70%"></div>
                        </div>
                        
                        <div class="flex items-center justify-between text-[11px] pt-2">
                            <span class="text-gray-500">Made Videos</span>
                            <span class="font-mono text-white">{{ $generations->whereNotNull('video_url')->count() }}</span>
                        </div>
                        <div class="w-full bg-white/5 h-1 rounded-full overflow-hidden">
                            <div class="bg-pink-600 h-full" style="width: 30%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-white/5">
                    <a href="{{ route('cgi.create') }}" class="block w-full py-2 bg-white text-black text-[10px] font-bold uppercase tracking-widest rounded hover:bg-gray-200 transition-colors text-center">
                        Launch New Task
                    </a>
                </div>
            </div>
        </div>

        {{-- 3. FOOTER / BRANDING --}}
        <div class="flex items-center justify-between pt-6 border-t border-white/5">
            <div class="flex items-center gap-2 opacity-60 hover:opacity-100 transition-opacity">
                <p class="text-[9px] font-bold uppercase tracking-[0.4em] text-gray-500">
                    Powered by <span class="text-blue-500">egeneration</span>
                </p>
            </div>
            
            <div class="flex gap-4 opacity-30">
                <span class="text-[9px] font-mono text-gray-500 uppercase tracking-tighter">Latency: 24ms</span>
                <span class="text-[9px] font-mono text-gray-500 uppercase tracking-tighter">Encrypted_Session</span>
            </div>
        </div>
    </div>

    {{-- SCRIPTS --}}
    <script>
        function updateClock() {
            const clock = document.getElementById('live-clock');
            if (!clock) return;
            const now = new Date();
            clock.textContent = now.toLocaleTimeString('en-GB', { hour12: false });
        }
        setInterval(updateClock, 1000);
    </script>

    {{-- GLOBAL STYLES --}}
    <style>
        body { 
            background-color: #050505; 
            color: #e5e7eb;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
        }
    </style>
</x-app-layout>