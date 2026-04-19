<x-app-layout>
    <div class="max-w-4xl mx-auto py-10 px-6 antialiased">
        
        {{-- Header --}}
        <div class="mb-8 border-b border-white/10 pb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-white uppercase tracking-widest flex items-center gap-3">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit Package: <span class="text-blue-400">{{ $package->name }}</span>
                </h1>
                <p class="text-xs text-gray-500 font-bold uppercase mt-2">Modify pricing and neural limits for this tier</p>
            </div>
            <a href="{{ route('admin.packages.index') }}" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-[10px] font-black uppercase tracking-widest rounded-lg border border-white/10 transition-all">
                Cancel & Return
            </a>
        </div>

        {{-- Form --}}
        <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl shadow-2xl overflow-hidden relative">
            
            {{-- Top decorative line --}}
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-cyan-400"></div>

            <form action="{{ route('admin.packages.update', $package->id) }}" method="POST" class="p-8 space-y-8">
                @csrf
                @method('PUT')

                {{-- Core Details Section --}}
                <div>
                    <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4 border-b border-white/5 pb-2">Core Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        {{-- Package Name --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Package Name</label>
                            <input type="text" name="name" value="{{ old('name', $package->name) }}" required class="w-full bg-black border border-white/10 rounded-lg p-3 text-white text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all placeholder-gray-700 font-bold">
                            @error('name') <span class="text-[9px] text-red-500 font-bold uppercase">{{ $message }}</span> @enderror
                        </div>

                        {{-- Price --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Price (USD)</label>
                            <input type="number" step="0.01" name="price" value="{{ old('price', $package->price) }}" required class="w-full bg-black border border-white/10 rounded-lg p-3 text-white text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all font-mono">
                            @error('price') <span class="text-[9px] text-red-500 font-bold uppercase">{{ $message }}</span> @enderror
                        </div>

                        {{-- Billing Cycle --}}
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Billing Cycle</label>
                            <select name="billing_cycle" required class="w-full bg-black border border-white/10 rounded-lg p-3 text-white text-xs focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all font-bold uppercase tracking-wider appearance-none">
                                <option value="monthly" {{ strtolower($package->billing_cycle) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ strtolower($package->billing_cycle) === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                <option value="lifetime" {{ strtolower($package->billing_cycle) === 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                            </select>
                            @error('billing_cycle') <span class="text-[9px] text-red-500 font-bold uppercase">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Allowances Section --}}
                <div>
                    <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-4 border-b border-white/5 pb-2 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Neural Generation Limits
                    </h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        {{-- Prompts --}}
                        <div class="space-y-1.5 bg-white/5 p-4 rounded-xl border border-white/5 text-center">
                            <label class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Prompts</label>
                            <input type="number" name="directive_allowance" value="{{ old('directive_allowance', $package->directive_allowance) }}" required class="w-full bg-black border border-white/10 rounded text-center p-2 text-white text-sm focus:border-emerald-500 outline-none font-mono">
                        </div>

                        {{-- Images --}}
                        <div class="space-y-1.5 bg-white/5 p-4 rounded-xl border border-white/5 text-center">
                            <label class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Images</label>
                            <input type="number" name="image_allowance" value="{{ old('image_allowance', $package->image_allowance) }}" required class="w-full bg-black border border-white/10 rounded text-center p-2 text-emerald-400 text-sm focus:border-emerald-500 outline-none font-mono">
                        </div>

                        {{-- Videos --}}
                        <div class="space-y-1.5 bg-white/5 p-4 rounded-xl border border-white/5 text-center">
                            <label class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Videos</label>
                            <input type="number" name="video_allowance" value="{{ old('video_allowance', $package->video_allowance) }}" required class="w-full bg-black border border-white/10 rounded text-center p-2 text-pink-400 text-sm focus:border-emerald-500 outline-none font-mono">
                        </div>

                        {{-- Logos --}}
                        <div class="col-span-full space-y-4">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-center border-b border-white/5 pb-2 mb-4">Neural Allowances (Branding)</h4>
                            
                            {{-- Branding Allowance (Total) --}}
                            <div class="bg-black/50 p-3 rounded-lg flex items-center justify-between">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Branding</label>
                                <input type="number" name="branding_allowance" value="{{ old('branding_allowance', $package->branding_allowance) }}" required class="w-20 bg-black border border-white/10 rounded text-center p-2 text-purple-400 text-sm focus:border-emerald-500 outline-none font-mono">
                            </div>

                            {{-- Branding Image Allowance --}}
                            <div class="bg-black/50 p-3 rounded-lg flex items-center justify-between">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Branding: Images</label>
                                <input type="number" name="branding_image_allowance" value="{{ old('branding_image_allowance', $package->branding_image_allowance) }}" required class="w-20 bg-black border border-white/10 rounded text-center p-2 text-blue-400 text-sm focus:border-emerald-500 outline-none font-mono">
                            </div>
                            
                            {{-- Branding Video Allowance --}}
                            <div class="bg-black/50 p-3 rounded-lg flex items-center justify-between">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Branding: Videos</label>
                                <input type="number" name="branding_video_allowance" value="{{ old('branding_video_allowance', $package->branding_video_allowance) }}" required class="w-20 bg-black border border-white/10 rounded text-center p-2 text-pink-400 text-sm focus:border-emerald-500 outline-none font-mono">
                            </div>

                            {{-- Social Post Allowance --}}
                            <div class="bg-black/50 p-3 rounded-lg flex items-center justify-between">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Social Posts</label>
                                <input type="number" name="social_post_allowance" value="{{ old('social_post_allowance', $package->social_post_allowance) }}" required class="w-20 bg-black border border-white/10 rounded text-center p-2 text-blue-400 text-sm focus:border-emerald-500 outline-none font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Button --}}
                <div class="pt-4 flex justify-end">
                    <button type="submit" class="px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-black uppercase tracking-widest rounded-xl shadow-[0_0_20px_rgba(37,99,235,0.3)] transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>