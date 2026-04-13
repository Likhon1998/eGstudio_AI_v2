<x-app-layout>
    <div class="p-6 max-w-7xl mx-auto space-y-8 antialiased">
        
        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between border-b border-white/10 pb-6 gap-4">
            <div>
                <h1 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500 tracking-[0.2em] uppercase">
                    Monetization Plans
                </h1>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_#3b82f6]"></span>
                    Configure SaaS Packages & Credit Limits
                </p>
            </div>
            
            {{-- Navigation Button to the New Requests Page --}}
            <a href="{{ route('admin.billings.requests') }}" class="px-5 py-2.5 bg-orange-500/10 border border-orange-500/20 text-orange-500 hover:bg-orange-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2 shadow-[0_0_15px_rgba(249,115,22,0.1)]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                View Activation Requests
            </a>
        </div>

        {{-- SUCCESS NOTIFICATION --}}
        @if(session('success'))
            <div class="px-5 py-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-black uppercase tracking-widest rounded-xl flex items-center gap-3 shadow-[0_0_20px_rgba(16,185,129,0.1)] animate-in fade-in slide-in-from-top-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            {{-- LEFT SIDE: CREATE NEW PACKAGE FORM --}}
            <div class="xl:col-span-1">
                <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-cyan-400"></div>
                    
                    <div class="p-6 sm:p-8">
                        <h2 class="text-xs font-black text-white uppercase tracking-widest mb-6 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Deploy New Package
                        </h2>
                        
                        <form action="{{ route('admin.packages.store') }}" method="POST" class="space-y-5">
                            @csrf
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Plan Name</label>
                                    <input type="text" name="name" required placeholder="e.g. Creator Pro" class="w-full bg-[#111] border border-white/10 rounded-lg p-3 text-white text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all placeholder-gray-600">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Price ($)</label>
                                        <input type="number" step="0.01" name="price" required placeholder="29.99" class="w-full bg-[#111] border border-white/10 rounded-lg p-3 text-white text-xs focus:border-blue-500 outline-none transition-all placeholder-gray-600">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Cycle</label>
                                        <select name="billing_cycle" class="w-full bg-[#111] border border-white/10 rounded-lg p-3 text-white text-xs focus:border-blue-500 outline-none appearance-none transition-all">
                                            <option value="monthly">Monthly</option>
                                            <option value="yearly">Yearly</option>
                                            <option value="lifetime">Lifetime</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-5 pb-1 border-t border-white/5">
                                <span class="text-[9px] font-black text-cyan-500 uppercase tracking-widest">Resource Allocations</span>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Prompt Limits</label>
                                    <input type="number" name="directive_allowance" required value="0" class="w-full bg-[#111] border border-white/10 rounded-lg p-2.5 text-white text-xs focus:border-blue-500 outline-none transition-all text-center font-mono">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Image Limits</label>
                                    <input type="number" name="image_allowance" required value="0" class="w-full bg-[#111] border border-white/10 rounded-lg p-2.5 text-white text-xs focus:border-blue-500 outline-none transition-all text-center font-mono">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Video / Audio</label>
                                    <input type="number" name="video_allowance" required value="0" class="w-full bg-[#111] border border-white/10 rounded-lg p-2.5 text-white text-xs focus:border-blue-500 outline-none transition-all text-center font-mono">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Branding Uses</label>
                                    <input type="number" name="branding_allowance" required value="0" class="w-full bg-[#111] border border-white/10 rounded-lg p-2.5 text-white text-xs focus:border-blue-500 outline-none transition-all text-center font-mono">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Social Posting Limits</label>
                                    <input type="number" name="social_post_allowance" required value="0" class="w-full bg-[#111] border border-white/10 rounded-lg p-2.5 text-white text-xs focus:border-blue-500 outline-none transition-all text-center font-mono">
                                </div>
                            </div>

                            <button type="submit" class="w-full mt-4 py-3.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white text-[10px] font-black rounded-lg uppercase tracking-widest shadow-[0_0_20px_rgba(37,99,235,0.3)] transition-all hover:-translate-y-0.5">
                                Save & Activate Package
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- RIGHT SIDE: EXISTING PACKAGES GRID --}}
            <div class="xl:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($packages as $package)
                        <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl p-6 sm:p-8 shadow-xl relative overflow-hidden group hover:border-white/10 transition-colors flex flex-col">
                            
                            {{-- Action Buttons (Edit & Delete) --}}
                            <div class="absolute top-4 right-4 flex items-center gap-2">
                                
                                {{-- Edit Button --}}
                                <a href="{{ route('admin.packages.edit', $package->id) }}" title="Edit Package" class="p-2 bg-white/5 hover:bg-blue-500/20 text-gray-500 hover:text-blue-400 rounded-lg transition-colors border border-transparent hover:border-blue-500/30">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>

                                {{-- Delete Button --}}
                                <form action="{{ route('admin.packages.destroy', $package->id) }}" method="POST" onsubmit="return confirm('Delete this package? Users currently on it will not be affected.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Delete Package" class="p-2 bg-white/5 hover:bg-red-500/20 text-gray-500 hover:text-red-400 rounded-lg transition-colors border border-transparent hover:border-red-500/30">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>

                            <div class="mb-6 pr-20">
                                <h3 class="text-white font-black uppercase tracking-widest text-sm mb-2">{{ $package->name }}</h3>
                                <div class="flex items-end gap-1.5">
                                    <span class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400 leading-none">${{ $package->price }}</span>
                                    <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider mb-1">/ {{ $package->billing_cycle }}</span>
                                </div>
                            </div>

                            <div class="space-y-3.5 mt-auto bg-[#111] p-5 rounded-xl border border-white/5">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        Neural Prompts
                                    </div>
                                    <span class="text-white font-mono font-bold">{{ $package->directive_allowance }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        Image Generations
                                    </div>
                                    <span class="text-white font-mono font-bold">{{ $package->image_allowance }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        Video Syntheses
                                    </div>
                                    <span class="text-white font-mono font-bold">{{ $package->video_allowance }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        Branded Overlays
                                    </div>
                                    <span class="text-white font-mono font-bold">{{ $package->branding_allowance }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 text-gray-400 font-medium">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        Social Broadcasts
                                    </div>
                                    <span class="text-white font-mono font-bold">{{ $package->social_post_allowance }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full flex flex-col items-center justify-center py-16 px-6 bg-[#0a0a0a] border border-white/5 rounded-2xl border-dashed">
                            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </div>
                            <p class="text-[11px] font-black text-gray-500 uppercase tracking-widest">No Monetization Plans Deployed</p>
                            <p class="text-[9px] text-gray-600 font-bold uppercase mt-2">Use the console on the left to create one.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>