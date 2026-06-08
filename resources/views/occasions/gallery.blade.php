<x-app-layout>
    @push('vite-scripts')
        @vite(['resources/js/gallery-download.js'])
    @endpush

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
        class="fixed top-6 right-6 z-[5000] flex flex-col gap-3 w-80 pointer-events-none">
        <template x-for="n in notifications" :key="n.id">
            <div class="pointer-events-auto px-5 py-4 rounded-xl shadow-2xl border flex items-center gap-3 backdrop-blur-xl bg-[#0a0a0a]/90 text-[10px] font-black uppercase tracking-widest"
                 :class="n.type === 'success' ? 'border-emerald-500/20 text-emerald-400' : n.type === 'error' ? 'border-red-500/20 text-red-400' : 'border-pink-500/20 text-pink-400'">
                <span class="flex-1" x-text="n.message"></span>
                <button type="button" @click="remove(n.id)" class="text-white/30 hover:text-white">✕</button>
            </div>
        </template>
    </div>

    <div class="max-w-full mx-auto bg-[#050505] min-h-screen" x-data="Object.assign(typeof window.galleryDownloadState === 'function' ? window.galleryDownloadState() : {}, {
            lightboxOpen: false, 
            activeImage: '', 
            activeTitle: '', 
            activePrompt: '',

            openActiveDownload() {
                let safe = (this.activeTitle || 'occasion_masterpiece').replace(/[^a-z0-9]/gi, '_').toLowerCase();
                if (!safe) safe = 'occasion_masterpiece';
                this.openDownloadPicker(this.activeImage, safe);
            }
        })">
        
        {{-- Slim Top Toolbar --}}
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b border-white/5 bg-[#0a0a0a] sticky top-0 z-40 backdrop-blur-md">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-pink-500 rounded-full shadow-[0_0_10px_rgba(236,72,153,0.5)]"></span>
                    Masterpiece Gallery
                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">Occasion Studio Assets</p>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('occasions.index') }}" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-white rounded-lg text-[10px] font-black uppercase tracking-[0.15em] transition-all border border-white/10">
                    Back to Studio
                </a>
                <a href="{{ route('occasions.create') }}" class="flex items-center gap-2 px-4 py-2 bg-pink-600 hover:bg-pink-500 text-white text-[9px] font-black rounded-md transition-all uppercase tracking-widest shadow-lg shadow-pink-600/20">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                    New Campaign
                </a>
            </div>
        </div>

        {{-- Filter Tabs --}}
        <div class="px-4 sm:px-6 lg:px-8 pt-6">
            <div class="flex items-center gap-3 border-b border-white/5 pb-4 overflow-x-auto custom-scrollbar">
                
                <a href="{{ route('occasions.gallery', ['tab' => 'all']) }}" 
                   class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap {{ $tab === 'all' ? 'bg-pink-600 text-white shadow-[0_0_15px_rgba(236,72,153,0.3)]' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white border border-white/5' }}">
                    All Assets
                </a>

                <a href="{{ route('occasions.gallery', ['tab' => 'branded']) }}" 
                   class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap {{ $tab === 'branded' ? 'bg-blue-600 text-white shadow-[0_0_15px_rgba(37,99,235,0.3)]' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white border border-white/5' }}">
                    <span class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Branded Only
                    </span>
                </a>

                <a href="{{ route('occasions.gallery', ['tab' => 'non_branded']) }}" 
                   class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap {{ $tab === 'non_branded' ? 'bg-emerald-600 text-white shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white border border-white/5' }}">
                    <span class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Raw Images
                    </span>
                </a>

                <a href="{{ route('occasions.gallery', ['tab' => 'merge']) }}" 
                   class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all whitespace-nowrap {{ $tab === 'merge' ? 'bg-violet-600 text-white shadow-[0_0_15px_rgba(124,58,237,0.3)]' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white border border-white/5' }}">
                    <span class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        Merge Library
                    </span>
                </a>

            </div>
        </div>

        {{-- Gallery Grid --}}
        <div class="p-4 sm:px-6 lg:px-8 pb-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-4">
                
                @forelse($assets as $item)
                    <div class="group relative bg-[#111] rounded-xl overflow-hidden border border-white/5 shadow-xl hover:shadow-pink-500/10 transition-all duration-500 aspect-video cursor-pointer"
                         @click="lightboxOpen = true; activeImage = @js($item->url); activeTitle = @js($item->model->occasion_identity); activePrompt = @js($item->model->visual_direction ?? '');">
                        
                        {{-- Image --}}
                        <img src="{{ $item->url }}" alt="{{ $item->model->occasion_identity }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 ease-out" loading="lazy"
                             onerror="this.classList.add('opacity-30'); this.alt='Image unavailable';">
                        
                        {{-- Top Badges --}}
                        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                            @if($item->isMerged)
                                <span class="px-2 py-1 bg-violet-500/80 backdrop-blur-md text-white text-[8px] font-black uppercase tracking-widest rounded shadow-lg border border-violet-400/50">
                                    Merged
                                </span>
                            @elseif($item->isBranded)
                                <span class="px-2 py-1 bg-blue-500/80 backdrop-blur-md text-white text-[8px] font-black uppercase tracking-widest rounded shadow-lg border border-blue-400/50">
                                    Branded
                                </span>
                            @else
                                <span class="px-2 py-1 bg-emerald-500/80 backdrop-blur-md text-white text-[8px] font-black uppercase tracking-widest rounded shadow-lg border border-emerald-400/50">
                                    Original
                                </span>
                            @endif
                        </div>

                        {{-- Hover Overlay --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-5">
                            <h3 class="text-white text-xs font-black uppercase tracking-widest mb-1">{{ $item->model->occasion_identity }}</h3>
                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mb-3">{{ date('F Y', mktime(0, 0, 0, $item->model->target_month, 1)) }}</p>
                            
                            <div class="flex items-center gap-2">
                                <span class="w-full text-center py-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm text-white text-[9px] font-black uppercase tracking-widest rounded transition-colors border border-white/20">
                                    View Full Screen
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-32 flex flex-col items-center justify-center border border-white/5 border-dashed rounded-2xl bg-[#0a0a0a]">
                        <svg class="w-12 h-12 text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <h2 class="text-white text-sm font-black uppercase tracking-[0.2em]">No Masterpieces Found</h2>
                        <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-2">No assets match your current filter.</p>
                    </div>
                @endforelse
            </div>
            
            {{ $assets->links('vendor.pagination.gallery') }}
        </div>

        {{-- Cinematic Lightbox Viewer --}}
        <template x-teleport="body">
            <div x-show="lightboxOpen" class="fixed inset-0 z-[3000] flex items-center justify-center bg-black/95 backdrop-blur-xl" x-cloak>
                {{-- Close Button --}}
                <button @click="lightboxOpen = false" class="absolute top-6 right-6 text-white/50 hover:text-white transition-colors bg-white/5 hover:bg-red-500/20 rounded-full p-3 border border-white/10 group z-50">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <div class="flex flex-col lg:flex-row w-full max-w-7xl h-[90vh] mx-4 border border-white/10 rounded-2xl overflow-hidden shadow-2xl bg-[#050505]" @click.away="lightboxOpen = false">
                    
                    {{-- Image Area --}}
                    <div class="w-full lg:w-2/3 h-64 lg:h-full bg-black relative flex items-center justify-center p-4">
                        <img :src="activeImage" class="max-w-full max-h-full object-contain drop-shadow-2xl rounded-lg">
                    </div>

                    {{-- Info Area --}}
                    <div class="w-full lg:w-1/3 h-full bg-[#0a0a0a] border-l border-white/5 flex flex-col">
                        <div class="p-8 flex-1 overflow-y-auto custom-scrollbar">
                            <h2 class="text-pink-500 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Campaign Identity</h2>
                            <h3 class="text-white text-lg font-black tracking-wider leading-tight mb-8" x-text="activeTitle"></h3>

                            <h2 class="text-emerald-500 text-[10px] font-black uppercase tracking-[0.2em] mb-2">Visual DNA / Prompt</h2>
                            <p class="text-gray-400 text-xs leading-relaxed font-mono" x-text="activePrompt"></p>
                        </div>
                        
                        <div class="p-6 border-t border-white/5 bg-white/[0.02]">
                            <button @click="openActiveDownload()" 
                                    :disabled="isDownloading"
                                    class="w-full py-3.5 bg-white text-black hover:bg-pink-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-colors flex items-center justify-center gap-2 shadow-lg shadow-white/5 disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="isDownloading">
                                    <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </template>
                                <template x-if="!isDownloading">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                </template>
                                <span x-text="isDownloading ? 'DOWNLOADING...' : 'Download High-Res'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        @include('partials.gallery-download-picker')
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #ec4899; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #be185d; }
    </style>
</x-app-layout>