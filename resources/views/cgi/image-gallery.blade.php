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
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen flex flex-col" x-data="{ openModal: false, currentImage: '' }">
        
        {{-- Slim Flush Header Toolbar --}}
        <div class="flex items-center justify-between px-8 py-4 border-b border-white/5 bg-[#0a0a0a]">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-emerald-600 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                    Neural Image Repository
                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">High-Fidelity Synthetic Stills</p>
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
                    <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-[11px] font-black text-gray-600 uppercase tracking-[0.3em]">No Static Assets Detected</h3>
                    <p class="text-[9px] text-gray-800 mt-2 uppercase font-bold">The image pipeline is currently empty.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    @foreach($images as $image)
                        <div class="group bg-[#0a0a0a] border border-white/5 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-emerald-500/30">
                            
                            {{-- Image Container --}}
                            <div class="relative aspect-square bg-black cursor-pointer overflow-hidden" 
                                 @click="openModal = true; currentImage = '{{ $image->image_url }}'; $dispatch('notify', {message: 'Enlarging Neural Still', type: 'info'})">
                                <img src="{{ $image->image_url }}" class="w-full h-full object-cover opacity-70 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500" alt="Still">
                                
                                {{-- Zoom Overlay --}}
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-black/20">
                                    <div class="w-10 h-10 bg-emerald-600 text-white rounded-full flex items-center justify-center shadow-[0_0_20px_rgba(16,185,129,0.4)]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                    </div>
                                </div>

                                {{-- Meta Tag --}}
                                <div class="absolute top-4 left-4">
                                    <span class="px-2 py-1 bg-black/60 backdrop-blur-md border border-white/10 text-[8px] font-black text-white uppercase tracking-widest rounded-md">
                                        RAW_GEN
                                    </span>
                                </div>
                            </div>

                            {{-- Details --}}
                            <div class="p-6">
                                <div class="mb-1">
                                    <h3 class="text-[12px] font-black text-white uppercase tracking-wider truncate">{{ $image->product_name }}</h3>
                                </div>
                                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-tighter mb-4">{{ $image->visual_prop }}</p>
                                
                                <div class="flex justify-between items-center pt-4 border-t border-white/5">
                                    <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest px-2 py-1 bg-emerald-500/5 border border-emerald-500/10 rounded">
                                        Captured
                                    </span>
                                    <a href="{{ $image->image_url }}" download @click="$dispatch('notify', {message: 'Downloading High-Res Still...', type: 'success'})" class="text-gray-600 hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Neural Image Viewer Modal --}}
            <template x-teleport="body">
                <div x-show="openModal" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="fixed inset-0 z-[1000] flex items-center justify-center p-6 bg-black/95 backdrop-blur-2xl" 
                     x-cloak>
                    <div class="relative w-full max-w-5xl" @click.away="openModal = false">
                        <button @click="openModal = false" class="absolute -top-12 right-0 text-white font-black hover:text-emerald-500 transition-colors uppercase text-[10px] tracking-[0.2em]">Close Viewer ✕</button>
                        <div class="bg-black rounded-3xl overflow-hidden border border-white/10 shadow-[0_0_50px_rgba(0,0,0,0.5)]">
                            <img :src="currentImage" class="w-full max-h-[80vh] object-contain bg-black">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer Credit --}}
        <div class="py-6 px-8 border-t border-white/5 flex justify-center">
            <span class="text-[9px] text-gray-700 font-black uppercase tracking-[0.4em]">Powered by eGeneration</span>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        body { background-color: #050505; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: #050505; }
        ::-webkit-scrollbar-thumb { background: #1a1a1a; border-radius: 10px; }
    </style>
</x-app-layout>