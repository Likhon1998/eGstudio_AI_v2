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
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen flex flex-col" 
         x-data="Object.assign(typeof window.galleryDownloadState === 'function' ? window.galleryDownloadState() : {}, {
            openModal: false, 
            currentImage: '', 
            filter: 'all',
            clientFilter: 'all', 

            submittingId: null,
            async submitForApproval(genId, mediaUrl, mediaType, variant, isBranded) {
                this.submittingId = mediaUrl;
                try {
                    const response = await fetch('{{ route('approvals.submit') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ cgi_generation_id: genId, media_url: mediaUrl, media_type: mediaType, variant: variant, is_branded: isBranded })
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        $dispatch('notify', { message: 'Sent for client approval', type: 'success' });
                        setTimeout(() => location.reload(), 900);
                    } else {
                        $dispatch('notify', { message: data.message || 'Submission failed', type: 'error' });
                    }
                } catch (e) {
                    $dispatch('notify', { message: 'Server error while submitting', type: 'error' });
                } finally {
                    this.submittingId = null;
                }
            }
         })">
        
        {{-- Header & Filter Toolbar --}}
        <div class="flex flex-col xl:flex-row items-center justify-between px-8 py-6 border-b border-white/5 bg-[#0a0a0a] gap-6">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-emerald-600 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                    Neural Image Repository
                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">
                    High-Fidelity Synthetic Stills
                    @if($assets->total() > 0)
                        · <span class="text-emerald-500/80">{{ number_format($assets->total()) }} images from database</span>
                    @endif
                </p>
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
                <button @click="filter = 'merged'" 
                    :class="filter === 'merged' ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20' : 'text-gray-500 hover:text-gray-300'"
                    class="px-5 py-2 text-[9px] font-black uppercase tracking-widest rounded-lg transition-all">
                    Merged Image
                </button>
            </div>

            {{-- Right Controls (Dropdown + Dashboard Button) --}}
            <div class="flex flex-col sm:flex-row items-center gap-4">
                
                {{-- Client Dropdown (Strictly visible to admins only) --}}
                @if($isAdmin && isset($clients) && $clients->count() > 0)
                <div class="relative w-full sm:w-auto">
                    <select x-model="clientFilter" 
                            class="w-full sm:w-64 appearance-none bg-white/5 border border-white/10 text-white pl-4 pr-10 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all cursor-pointer">
                        <option value="all" class="bg-[#0a0a0a] text-white font-bold">Show All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" class="bg-[#0a0a0a] text-white">
                                {{ $client->name ?? 'Client #'.$client->id }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-white/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
                @endif

                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto text-center px-5 py-2.5 bg-white/5 text-gray-400 hover:text-white text-[10px] font-black rounded-md transition-all uppercase tracking-widest border border-white/5 hover:border-emerald-500/50">
                    Dashboard
                </a>
            </div>
        </div>

        <div class="p-8 flex-1">
            @if($assets->total() === 0)
                <div class="col-span-full py-32 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-[2rem] bg-white/[0.01]">
                    <h3 class="text-[11px] font-black text-gray-600 uppercase tracking-[0.3em]">No Static Assets Detected</h3>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    @foreach($assets as $asset)
                        @include('partials.gallery.cgi-image-asset-card', ['asset' => $asset, 'requiresApproval' => $requiresApproval])
                    @endforeach
                </div>

                {{ $assets->links('vendor.pagination.gallery') }}
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
                        <div class="bg-black rounded-3xl overflow-hidden border border-white/10 shadow-2xl relative">
                            <img :src="currentImage" class="w-full max-h-[80vh] object-contain bg-black">
                            <div class="absolute bottom-4 right-4">
                                <button type="button" @click="openDownloadPicker(currentImage, 'neural-still')"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-[9px] font-black uppercase tracking-widest rounded-lg shadow-lg">
                                    Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="py-6 px-8 border-t border-white/5 flex justify-center">
            <span class="text-[9px] text-gray-700 font-black uppercase tracking-[0.4em]">Powered by eGeneration</span>
        </div>

        @include('partials.gallery-download-picker')
    </div>

    <style>
        [x-cloak] { display: none !important; }
        body { background-color: #050505; }
    </style>
</x-app-layout>