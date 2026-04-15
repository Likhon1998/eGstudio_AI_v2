<x-app-layout>
    {{-- Global Notification System --}}
    <div x-data="{ 
            notifications: [], 
            add(message, type = 'info') { 
                const id = Date.now();
                this.notifications.push({ id, message, type });
                setTimeout(() => this.remove(id), 5000);
            },
            remove(id) { this.notifications = this.notifications.filter(n => n.id !== id); } 
        }" 
        @notify.window="add($event.detail.message, $event.detail.type)"
        class="fixed top-6 right-6 z-[1100] flex flex-col gap-3 w-80">
        <template x-for="n in notifications" :key="n.id">
            <div x-transition class="px-5 py-4 rounded-xl shadow-2xl border flex items-center gap-3 backdrop-blur-xl bg-[#0a0a0a]/80"
                 :class="n.type === 'success' ? 'border-emerald-500/20 text-emerald-400' : 'border-blue-500/20 text-blue-400'">
                <div class="flex-1 text-[10px] font-black uppercase tracking-widest leading-none" x-text="n.message"></div>
                <button @click="remove(n.id)" class="text-white/20 hover:text-white">✕</button>
            </div>
        </template>
    </div>

    {{-- Main Workspace --}}
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen flex flex-col" x-data="{ openModal: false, currentImage: '', filter: 'all' }">
        
        {{-- Header & Filter Toolbar --}}
        <div class="flex flex-col md:flex-row items-center justify-between px-8 py-6 border-b border-white/5 bg-[#0a0a0a] gap-4">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-emerald-600 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                    Neural Image Repository
                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">High-Fidelity Synthetic Stills</p>
            </div>

            {{-- Filter Controller --}}
            <div class="flex items-center bg-white/5 p-1 rounded-xl border border-white/5">
                <button @click="filter = 'all'" 
                    :class="filter === 'all' ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-gray-300'"
                    class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all">
                    All Images
                </button>
                <button @click="filter = 'branded'" 
                    :class="filter === 'branded' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/20' : 'text-gray-500 hover:text-gray-300'"
                    class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all">
                    Branded (With Logo)
                </button>
                <button @click="filter = 'raw'" 
                    :class="filter === 'raw' ? 'bg-white text-black' : 'text-gray-500 hover:text-gray-300'"
                    class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all">
                    Raw (No Logo)
                </button>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="px-5 py-2 bg-white/5 text-gray-400 hover:text-white text-[10px] font-black rounded-md transition-all uppercase tracking-widest border border-white/5">
                    Dashboard
                </a>
            </div>
        </div>

        <div class="p-8 flex-1">
            @if($images->isEmpty())
                <div class="col-span-full py-32 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-[2rem] bg-white/[0.01]">
                    <h3 class="text-[11px] font-black text-gray-600 uppercase tracking-[0.3em]">No Static Assets Detected</h3>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    @foreach($images as $image)
                        
                        {{-- Standard Image Card --}}
                        @if($image->image_url)
                        <div x-show="filter === 'all' || filter === 'raw'" 
                             x-transition:enter="transition ease-out duration-300"
                             class="group bg-[#0a0a0a] border border-white/5 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-white/20">
                            <div class="relative aspect-square bg-black cursor-pointer overflow-hidden" 
                                @click="openModal = true; currentImage = '{{ str_starts_with($image->image_url, 'http') ? $image->image_url : asset('storage/' . $image->image_url) }}'; $dispatch('notify', {message: 'Enlarging Raw Still', type: 'info'})">
                                <img src="{{ str_starts_with($image->image_url, 'http') ? $image->image_url : asset('storage/' . $image->image_url) }}" class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-all duration-500">
                                <div class="absolute top-4 left-4">
                                    <span class="px-2 py-1 bg-black/60 backdrop-blur-md border border-white/10 text-[8px] font-black text-gray-400 uppercase tracking-widest rounded-md">RAW_RENDER</span>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-[12px] font-black text-white uppercase tracking-wider truncate">{{ $image->product_name }}</h3>
                                <div class="flex justify-between items-center pt-4 border-t border-white/5 mt-4">
                                    <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Neural Asset</span>
                                    <a href="{{ str_starts_with($image->image_url, 'http') ? $image->image_url : asset('storage/' . $image->image_url) }}" download @click.stop class="text-gray-600 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Branded Image Card --}}
                        @if($image->branded_image_url)
                        @can('view_branded_assets')
                        <div x-show="filter === 'all' || filter === 'branded'" 
                             x-transition:enter="transition ease-out duration-300"
                             class="group bg-[#0a0a0a] border border-emerald-500/10 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-emerald-500/40">
                             <!-- ... (rest of the card content) ... -->
                            <div class="relative aspect-square bg-black cursor-pointer overflow-hidden" 
                                @click="openModal = true; currentImage = '{{ str_starts_with($image->branded_image_url, 'http') ? $image->branded_image_url : asset('storage/' . $image->branded_image_url) }}'; $dispatch('notify', {message: 'Enlarging Branded Still', type: 'info'})">
                                <img src="{{ str_starts_with($image->branded_image_url, 'http') ? $image->branded_image_url : asset('storage/' . $image->branded_image_url) }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-all duration-500">
                                <div class="absolute top-4 left-4">
                                    <span class="px-2 py-1 bg-emerald-600 text-[8px] font-black text-white uppercase tracking-widest rounded-md shadow-lg shadow-emerald-600/20">Identity_Applied</span>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-[12px] font-black text-white uppercase tracking-wider truncate">{{ $image->product_name }}</h3>
                                <div class="flex justify-between items-center pt-4 border-t border-white/5 mt-4">
                                    <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Master Still</span>
                                    <a href="{{ str_starts_with($image->branded_image_url, 'http') ? $image->branded_image_url : asset('storage/' . $image->branded_image_url) }}" download @click.stop class="text-gray-600 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                                </div>
                            </div>
                        </div>
                        @endcan
                        @endif

                    @endforeach
                </div>
            @endif

            {{-- Image Viewer Modal --}}
            <template x-teleport="body">
                <div x-show="openModal" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="fixed inset-0 z-[1000] flex items-center justify-center p-6 bg-black/95 backdrop-blur-2xl" 
                     x-cloak>
                    <div class="relative w-full max-w-5xl" @click.away="openModal = false">
                        <button @click="openModal = false" class="absolute -top-12 right-0 text-white font-black hover:text-emerald-500 transition-colors uppercase text-[10px] tracking-[0.2em]">Close Viewer ✕</button>
                        <div class="bg-black rounded-3xl overflow-hidden border border-white/10 shadow-2xl">
                            <img :src="currentImage" class="w-full max-h-[80vh] object-contain bg-black">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="py-6 px-8 border-t border-white/5 flex justify-center">
            <span class="text-[9px] text-gray-700 font-black uppercase tracking-[0.4em]">Powered by eGeneration</span>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        body { background-color: #050505; }
    </style>
</x-app-layout>