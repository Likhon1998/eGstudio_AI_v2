<x-app-layout>
    {{-- Global Notification System (Toasts) --}}
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
                 :class="n.type === 'success' ? 'border-emerald-500/20 text-emerald-400' : 'border-pink-500/20 text-pink-400'">
                <div class="flex-1 text-[10px] font-black uppercase tracking-widest leading-none" x-text="n.message"></div>
                <button @click="remove(n.id)" class="text-white/20 hover:text-white">✕</button>
            </div>
        </template>
    </div>

    {{-- Main Workspace --}}
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen flex flex-col" x-data="{ openModal: false, currentVideo: '' }">
        
        {{-- Slim Flush Header Toolbar --}}
        <div class="flex items-center justify-between px-8 py-4 border-b border-white/5 bg-[#0a0a0a]">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-pink-600 rounded-full shadow-[0_0_10px_rgba(219,39,119,0.5)]"></span>
                    Video Production Gallery
                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">Neural Cinematic Repository</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="px-5 py-2 bg-white/5 text-gray-400 hover:text-white text-[10px] font-black rounded-md transition-all uppercase tracking-widest border border-white/5">
                    Dashboard
                </a>
            </div>
        </div>

        {{-- Content Area --}}
        <div class="p-8 flex-1">
            @if($videos->isEmpty())
                <div class="col-span-full py-32 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-[2rem] bg-white/[0.01]">
                    <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-[11px] font-black text-gray-600 uppercase tracking-[0.3em]">No Rendered Assets Detected</h3>
                    <p class="text-[9px] text-gray-800 mt-2 uppercase font-bold">The pipeline is currently empty.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    @foreach($videos as $video)
                        <div class="group bg-[#0a0a0a] border border-white/5 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-pink-500/30">
                            
                            {{-- Thumbnail Container --}}
                            <div class="relative aspect-video bg-black cursor-pointer overflow-hidden" 
                                 @click="openModal = true; currentVideo = '{{ $video->video_url }}'; $dispatch('notify', {message: 'Initializing Neural Player', type: 'info'})">
                                <img src="{{ $video->image_url }}" class="w-full h-full object-cover opacity-50 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500" alt="Thumbnail">
                                
                                {{-- Play Overlay --}}
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-12 h-12 bg-pink-600 text-white rounded-full flex items-center justify-center shadow-[0_0_20px_rgba(219,39,119,0.4)] group-hover:scale-110 transition-transform duration-300">
                                        <svg class="w-5 h-5 fill-current ml-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>

                                {{-- Meta Tag --}}
                                <div class="absolute top-4 left-4">
                                    <span class="px-2 py-1 bg-black/60 backdrop-blur-md border border-white/10 text-[8px] font-black text-white uppercase tracking-widest rounded-md">
                                        4K Synths
                                    </span>
                                </div>
                            </div>

                            {{-- Details --}}
                            <div class="p-6">
                                <div class="mb-1">
                                    <h3 class="text-[12px] font-black text-white uppercase tracking-wider truncate">{{ $video->product_name }}</h3>
                                </div>
                                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-tighter mb-4">{{ $video->visual_prop }}</p>
                                
                                <div class="flex justify-between items-center pt-4 border-t border-white/5">
                                    <span class="text-[9px] font-black text-pink-500 uppercase tracking-widest px-2 py-1 bg-pink-500/5 border border-pink-500/10 rounded">
                                        Rendered
                                    </span>
                                    <a href="{{ $video->video_url }}" download @click="$dispatch('notify', {message: 'Downloading Asset...', type: 'success'})" class="text-gray-600 hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Neural Video Player Modal --}}
            <template x-teleport="body">
                <div x-show="openModal" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="fixed inset-0 z-[1000] flex items-center justify-center p-6 bg-black/95 backdrop-blur-2xl" 
                     x-cloak>
                    <div class="relative w-full max-w-5xl" @click.away="openModal = false">
                        <button @click="openModal = false" class="absolute -top-12 right-0 text-white font-black hover:text-pink-500 transition-colors uppercase text-[10px] tracking-[0.2em]">Close Player ✕</button>
                        <div class="bg-black rounded-3xl overflow-hidden border border-white/10 shadow-[0_0_50px_rgba(0,0,0,0.5)]">
                            <video :src="currentVideo" class="w-full max-h-[80vh] bg-black" controls autoplay loop playsinline></video>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer Credit --}}
        <div class="py-8 border-t border-white/5 flex flex-col items-center justify-center bg-[#0a0a0a]">
            <span class="text-[10px] text-gray-700 font-black uppercase tracking-[0.4em] mb-1">Powered by</span>
            <span class="text-[11px] text-white font-black uppercase tracking-[0.2em] opacity-80">eGeneration</span>
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