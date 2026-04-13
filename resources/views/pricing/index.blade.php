<x-app-layout>
    <div class="py-16 sm:py-24 px-6 max-w-7xl mx-auto antialiased">
        
        {{-- Display Errors if they try to bypass the lock --}}
        @if($errors->any())
            <div class="mb-10 max-w-3xl mx-auto px-5 py-4 bg-red-500/10 border border-red-500/20 text-red-400 text-[11px] font-black uppercase tracking-widest rounded-xl flex items-center justify-center text-center shadow-[0_0_20px_rgba(239,68,68,0.15)]">
                @foreach ($errors->all() as $error)
                    <span>⚠️ {{ $error }}</span>
                @endforeach
            </div>
        @endif

        {{-- Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h1 class="text-3xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500 tracking-[0.2em] uppercase mb-4">
                Choose Your Arsenal
            </h1>
            <p class="text-sm md:text-base text-gray-400 font-bold uppercase tracking-widest">
                Unlock high-performance neural rendering limits and exclusive features to scale your CGI production.
            </p>
        </div>

        {{-- Packages Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 items-stretch">
            @forelse($packages as $package)
                @php
                    // Determine User's Status Relative to This Package
                    $user = auth()->user();
                    $currentPackage = $user->package;
                    
                    $isDowngrade = $currentPackage && $package->price < $currentPackage->price;
                    $isCurrent = $currentPackage && $currentPackage->id === $package->id;
                    $isUpgrade = $currentPackage && $package->price > $currentPackage->price;
                    
                    $buttonText = 'Get Started';
                    $buttonClass = 'bg-white/5 hover:bg-blue-600 border-white/10 hover:border-blue-500 hover:shadow-[0_0_20px_rgba(37,99,235,0.3)] text-white';

                    if ($currentPackage) {
                        if ($isCurrent) {
                            $buttonText = 'Renew Plan';
                            $buttonClass = 'bg-emerald-600/10 hover:bg-emerald-600 border-emerald-500/30 hover:border-emerald-500 hover:shadow-[0_0_20px_rgba(16,185,129,0.3)] text-emerald-400 hover:text-white';
                        } elseif ($isUpgrade) {
                            $buttonText = 'Upgrade Plan';
                            $buttonClass = 'bg-blue-600 hover:bg-blue-500 border-blue-500 shadow-[0_0_20px_rgba(37,99,235,0.3)] text-white';
                        } elseif ($isDowngrade) {
                            // NOW ALLOWED: Treat as a smaller refill
                            $buttonText = 'Purchase Refill';
                            $buttonClass = 'bg-white/5 hover:bg-orange-600 border-white/10 hover:border-orange-500 hover:shadow-[0_0_20px_rgba(249,115,22,0.3)] text-gray-400 hover:text-white';
                        }
                    }
                @endphp

                <div class="bg-[#0a0a0a] border border-white/5 rounded-3xl p-8 shadow-2xl relative overflow-hidden group transition-all flex flex-col duration-300 hover:border-blue-500/30 hover:-translate-y-2">
                    
                    {{-- Hover Top Glow --}}
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-cyan-400 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                    {{-- Package Name & Price --}}
                    <div class="mb-8">
                        <h3 class="text-white font-black uppercase tracking-widest text-sm mb-3">
                            {{ $package->name }} 
                            @if($isCurrent) <span class="ml-2 text-[8px] bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded border border-emerald-500/30 align-middle">Active</span> @endif
                        </h3>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400 leading-none">${{ number_format($package->price, 2) }}</span>
                            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">/ {{ $package->billing_cycle }}</span>
                        </div>
                    </div>

                    {{-- Features List --}}
                    <div class="space-y-4 mb-10 flex-grow">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-300 font-medium"><strong class="text-white font-mono">{{ $package->directive_allowance }}</strong> Neural Prompts</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-300 font-medium"><strong class="text-white font-mono">{{ $package->image_allowance }}</strong> Image Generations</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-300 font-medium"><strong class="text-white font-mono">{{ $package->video_allowance }}</strong> Video Syntheses</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-300 font-medium"><strong class="text-white font-mono">{{ $package->branding_allowance }}</strong> Branded Overlays</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-300 font-medium"><strong class="text-white font-mono">{{ $package->social_post_allowance }}</strong> Social Broadcasts</span>
                        </div>
                    </div>

                    {{-- Button Form --}}
                    <form action="{{ route('pricing.select', $package->id) }}" method="POST" class="mt-auto">
                        @csrf
                        <button type="submit" 
                                class="w-full py-4 text-[11px] font-black rounded-xl uppercase tracking-widest transition-all border {{ $buttonClass }}">
                            {{ $buttonText }}
                        </button>
                    </form>

                </div>
            @empty
                <div class="col-span-full py-20 text-center bg-[#0a0a0a] border border-white/5 rounded-3xl border-dashed">
                    <svg class="w-12 h-12 text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <p class="text-xs font-black text-gray-500 uppercase tracking-widest">No plans available at the moment.</p>
                </div>
            @endforelse
        </div>

    </div>
</x-app-layout>