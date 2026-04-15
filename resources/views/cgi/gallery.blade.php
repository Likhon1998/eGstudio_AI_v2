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
                 :class="n.type === 'success' ? 'border-emerald-500/20 text-emerald-400' : 'border-pink-500/20 text-pink-400'">
                <div class="flex-1 text-[10px] font-black uppercase tracking-widest leading-none" x-text="n.message"></div>
                <button @click="remove(n.id)" class="text-white/20 hover:text-white">✕</button>
            </div>
        </template>
    </div>

    {{-- Gallery Workspace --}}
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen flex flex-col" 
         x-data="{ openModal: false, currentVideo: '', filter: 'all' }">
        
        {{-- Header & Filter Bar --}}
        <div class="flex flex-col md:flex-row items-center justify-between px-8 py-6 border-b border-white/5 bg-[#0a0a0a] gap-6">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-pink-600 rounded-full shadow-[0_0_10px_rgba(219,39,119,0.5)]"></span>
                    Neural Video Gallery
                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">Asset Pipeline Repository</p>
            </div>

            {{-- Filter Controller --}}
            <div class="flex items-center bg-white/5 p-1 rounded-xl border border-white/5 backdrop-blur-md">
                <button @click="filter = 'all'" 
                    :class="filter === 'all' ? 'bg-white/10 text-white' : 'text-gray-500 hover:text-gray-300'"
                    class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all">
                    All Videos
                </button>
                
                <button @click="filter = 'branded'" 
                    :class="filter === 'branded' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-gray-500 hover:text-gray-300'"
                    class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all">
                    Branded (With Logo)
                </button>
                
                <button @click="filter = 'raw'" 
                    :class="filter === 'raw' ? 'bg-pink-600 text-white shadow-lg shadow-pink-600/20' : 'text-gray-500 hover:text-gray-300'"
                    class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all">
                    Raw (No Logo)
                </button>
            </div>

            <a href="{{ route('dashboard') }}" class="px-6 py-2 bg-white text-black text-[10px] font-black rounded-lg transition-all uppercase tracking-widest hover:bg-pink-600 hover:text-white">Dashboard</a>
        </div>

        {{-- Content Grid --}}
        <div class="p-8 flex-1">
            @if($videos->isEmpty())
                <div class="col-span-full py-32 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-[2rem] bg-white/[0.01]">
                    <h3 class="text-[11px] font-black text-gray-600 uppercase tracking-[0.3em]">No Rendered Assets</h3>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    @foreach($videos as $video)
                        
                        {{-- 1. Branded Video Card --}}
                        @if($video->branded_video_url)
                        <div x-show="filter === 'all' || filter === 'branded'" 
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="group bg-[#0a0a0a] border border-blue-500/20 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-blue-500">
                            <div class="relative aspect-video bg-black cursor-pointer" @click="openModal=true; currentVideo='{{ str_starts_with($video->branded_video_url, 'http') ? $video->branded_video_url : asset('storage/' . $video->branded_video_url) }}'">
                                <img src="{{ str_starts_with($video->branded_image_url, 'http') ? $video->branded_image_url : asset('storage/' . $video->branded_image_url) }}" class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-2xl"><svg class="w-5 h-5 fill-current ml-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                                </div>
                                <span class="absolute top-4 left-4 px-2 py-1 bg-blue-600 text-[8px] font-black text-white uppercase rounded tracking-widest">Logo Applied</span>
                            </div>
                            <div class="p-5">
                                <h3 class="text-[11px] font-black text-white uppercase truncate">{{ $video->product_name }}</h3>
                                <div class="mt-4 pt-4 border-t border-white/5 flex justify-between items-center">
                                    <span class="text-[8px] text-blue-500 font-bold uppercase tracking-widest">Master Production</span>
                                    <a href="{{ str_starts_with($video->branded_video_url, 'http') ? $video->branded_video_url : asset('storage/' . $video->branded_video_url) }}" download @click.stop class="text-gray-600 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- 2. Raw Video Card --}}
                        @if($video->video_url)
                        <div x-show="filter === 'all' || filter === 'raw'" 
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="group bg-[#0a0a0a] border border-pink-500/10 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-pink-500/40">
                            <div class="relative aspect-video bg-black cursor-pointer" @click="openModal=true; currentVideo='{{ str_starts_with($video->video_url, 'http') ? $video->video_url : asset('storage/' . $video->video_url) }}'">
                                <img src="{{ str_starts_with($video->image_url, 'http') ? $video->image_url : asset('storage/' . $video->image_url) }}" class="w-full h-full object-cover opacity-40 group-hover:opacity-100 transition-opacity">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-12 h-12 bg-pink-600 text-white rounded-full flex items-center justify-center shadow-2xl"><svg class="w-5 h-5 fill-current ml-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                                </div>
                                <span class="absolute top-4 left-4 px-2 py-1 bg-pink-600 text-[8px] font-black text-white uppercase rounded tracking-widest">No Logo</span>
                            </div>
                            <div class="p-5">
                                <h3 class="text-[11px] font-black text-white uppercase truncate">{{ $video->product_name }}</h3>
                                <div class="mt-4 pt-4 border-t border-white/5 flex justify-between items-center">
                                    <span class="text-[8px] text-gray-500 font-bold uppercase tracking-widest">Raw Render</span>
                                    <a href="{{ str_starts_with($video->video_url, 'http') ? $video->video_url : asset('storage/' . $video->video_url) }}" download @click.stop class="text-gray-600 hover:text-white"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                                </div>
                            </div>
                        </div>
                        @endif

                    @endforeach
                </div>
            @endif

            {{-- Video Player Modal --}}
            <template x-teleport="body">
                <div x-show="openModal" class="fixed inset-0 z-[1000] flex items-center justify-center p-6 bg-black/95 backdrop-blur-2xl" x-cloak>
                    {{-- Notice: added currentVideo = '' to @click.away so it clears the source! --}}
                    <div class="relative w-full max-w-5xl" @click.away="openModal = false; currentVideo = '';">
                        <button @click="openModal = false; currentVideo = '';" class="absolute -top-12 right-0 text-white font-black hover:text-pink-500 uppercase text-[10px] tracking-[0.2em]">Close ✕</button>
                        <div class="bg-black rounded-3xl overflow-hidden border border-white/10 shadow-2xl">
                            {{-- By wrapping in x-if, Alpine destroys the video when the modal closes --}}
                            <template x-if="openModal">
                                <video :src="currentVideo" class="w-full max-h-[80vh]" controls autoplay loop playsinline></video>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
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