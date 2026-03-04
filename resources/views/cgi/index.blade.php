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
        class="fixed top-6 right-6 z-[2500] flex flex-col gap-3 w-80">
        
        <template x-for="n in notifications" :key="n.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-8"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="px-5 py-4 rounded-xl shadow-2xl border flex items-center gap-3 backdrop-blur-xl transition-all"
                 :class="{
                    'bg-emerald-500/10 border-emerald-500/20 text-emerald-400': n.type === 'success',
                    'bg-red-500/10 border-red-500/20 text-red-400': n.type === 'error',
                    'bg-blue-500/10 border-blue-500/20 text-blue-400': n.type === 'info'
                 }">
                <div class="flex-1 text-[10px] font-black uppercase tracking-widest leading-none" x-text="n.message"></div>
                <button @click="remove(n.id)" class="text-white/20 hover:text-white">✕</button>
            </div>
        </template>
    </div>

    {{-- Main Workspace --}}
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen" x-data="{ brandingModal: false, activeGenId: null, activeImageUrl: '', activeVideoUrl: '', isUploadingLogo: false, activePreviewUrl: '' }">
        
        {{-- Slim Top Toolbar --}}
        <div class="flex items-center justify-between px-8 py-4 border-b border-white/5 bg-[#0a0a0a]">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-blue-600 rounded-full shadow-[0_0_10px_rgba(37,99,235,0.5)]"></span>
                    CGI Directive Studio
                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">Neural Asset Pipeline v3.2</p>
            </div>
            <a href="{{ route('cgi.create') }}"
                class="flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black rounded-md transition-all uppercase tracking-widest shadow-lg shadow-blue-600/20">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                New Directive
            </a>
        </div>

        <div class="p-8">
            <div class="bg-[#0a0a0a] border border-white/5 rounded-xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white/[0.02] border-b border-white/5">
                            <tr class="text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                                <th class="px-8 py-5">Directive Identity</th>
                                <th class="px-8 py-5 text-center">Neural Prompts</th>
                                <th class="px-8 py-5">Render Engine</th>
                                <th class="px-8 py-5 text-right">Control</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/[0.03]">
                            @foreach($generations as $gen)
                            <tr x-data="{
                                status: '{{ $gen->status }}',
                                imageStatus: '{{ $gen->image_url ? 'completed' : $gen->image_status }}',
                                videoStatus: '{{ $gen->video_url ? 'completed' : ($gen->video_status ?? 'pending') }}',
                                openModal: null, isEditing: false, isSaving: false, isTriggering: false, isVideoTriggering: false,
                                liveImagePrompt: @js($gen->image_prompt),
                                liveVideoPrompt: @js($gen->video_prompt),
                                liveAudioPrompt: @js($gen->audio_prompt),
                                inputImage: @js($gen->image_prompt), 
                                inputVideo: @js($gen->video_prompt), 
                                inputAudio: @js($gen->audio_prompt),
                                imageUrl: '{{ $gen->image_url }}', 
                                videoUrl: '{{ $gen->video_url }}',
                                brandedImageUrl: '{{ $gen->branded_image_url ?? '' }}',
                                brandedVideoUrl: '{{ $gen->branded_video_url ?? '' }}',
                                isBranding: ('{{ $gen->branded_image_url ?? '' }}' !== '' && '{{ $gen->branded_video_url ?? '' }}' !== '') 
                                            ? false 
                                            : (sessionStorage.getItem('branding_{{ $gen->id }}') === 'true'),

                                checkAndReload() {
                                    let loading = false;
                                    if (this.status === 'processing') loading = true;
                                    if (this.imageStatus === 'making' && !this.imageUrl) loading = true;
                                    if (this.videoStatus === 'making' && !this.videoUrl) loading = true;
                                    if (this.isBranding && (!this.brandedImageUrl || !this.brandedVideoUrl)) loading = true;

                                    if (loading) {
                                        setTimeout(() => { location.reload(); }, 5000);
                                    }
                                },

                                async saveChanges(){
                                    if(this.isSaving) return;
                                    this.isSaving = true;
                                    try {
                                        const response = await fetch('/cgi/{{ $gen->id }}/update-prompts', {
                                            method: 'PUT',
                                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                            body: JSON.stringify({ 
                                                image_prompt: this.inputImage, 
                                                video_prompt: this.inputVideo, 
                                                audio_prompt: this.inputAudio 
                                            })
                                        });
                                        const result = await response.json();
                                        if(response.ok && result.success){
                                            this.liveImagePrompt = result.image_prompt;
                                            this.liveVideoPrompt = result.video_prompt;
                                            this.liveAudioPrompt = result.audio_prompt;
                                            this.isEditing = false;
                                            $dispatch('notify', { message: 'Directive Synced Successfully', type: 'success' });
                                        }
                                    } catch(e) { $dispatch('notify', { message: 'Network Sync Error', type: 'error' }); }
                                    finally { this.isSaving = false; }
                                },

                                async triggerMakePicture(){
                                    if(!confirm('Initiate Image Render?')) return;
                                    this.isTriggering = true;
                                    try {
                                        const response = await fetch('/cgi/{{ $gen->id }}/make-picture', {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                        });
                                        const data = await response.json();
                                        if(data.success) { 
                                            this.imageStatus = 'making'; 
                                            $dispatch('notify', { message: 'Image Generation Queued', type: 'info' });
                                            this.checkAndReload(); 
                                        }
                                    } catch(e) { $dispatch('notify', { message: 'Render Server Offline', type: 'error' }); }
                                    finally { this.isTriggering = false; }
                                },

                                async triggerMakeVideo(){
                                    if(!confirm('Initiate Video Pipeline?')) return;
                                    this.isVideoTriggering = true;
                                    try {
                                        const response = await fetch('/cgi/{{ $gen->id }}/make-video', {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                        });
                                        const data = await response.json();
                                        if(data.success) { 
                                            this.videoStatus = 'making'; 
                                            $dispatch('notify', { message: 'Video Synthesis Started', type: 'info' });
                                            this.checkAndReload();
                                        }
                                    } catch(e) { $dispatch('notify', { message: 'Pipeline Error', type: 'error' }); }
                                    finally { this.isVideoTriggering = false; }
                                }
                            }"
                            x-init="
                                if(brandedImageUrl && brandedVideoUrl) {
                                    sessionStorage.removeItem('branding_{{ $gen->id }}');
                                    isBranding = false;
                                }
                                checkAndReload();
                            "
                            @start-branding.window="
                                if($event.detail === '{{ $gen->id }}') {
                                    isBranding = true;
                                    checkAndReload();
                                }
                            "
                            class="hover:bg-white/[0.01] transition-colors"
                            >

                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-black text-gray-100 uppercase tracking-wider">{{ $gen->product_name }}</span>
                                        <span class="text-[10px] text-gray-500 mt-0.5 font-bold italic">{{ $gen->marketing_angle }}</span>
                                        <span class="mt-2 text-[8px] font-black text-blue-500/80 uppercase tracking-[0.2em]">{{ $gen->visual_prop }}</span>
                                    </div>
                                </td>

                                <td class="px-8 py-6 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button @click="openModal='image'; isEditing=false;" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-[9px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Image Prompt</button>
                                        <button @click="openModal='video'; isEditing=false;" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-[9px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Video Prompt</button>
                                        <button @click="openModal='audio'; isEditing=false;" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-[9px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Audio Prompt</button>
                                    </div>
                                </td>

                                <td class="px-8 py-6">
                                    @if($gen->status == 'processing')
                                        <div class="flex items-center gap-3 px-4 py-2 bg-yellow-500/5 border border-yellow-500/10 rounded-lg">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(234,179,8,0.6)]"></span>
                                            <span class="text-[9px] font-black text-yellow-500 uppercase tracking-widest">Generating DNA</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-3">
                                            <button @click="imageUrl ? (openModal='preview', activePreviewUrl=imageUrl) : triggerMakePicture()" :disabled="imageStatus==='making' || isTriggering"
                                                class="h-9 px-5 text-[10px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-2 border shadow-lg"
                                                :class="{
                                                    'bg-emerald-500 border-emerald-500 text-black animate-pulse shadow-[0_0_15px_rgba(16,185,129,0.4)]': imageStatus === 'making' && !imageUrl,
                                                    'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white': imageUrl,
                                                    'bg-white text-black border-transparent hover:bg-blue-600 hover:text-white': !imageUrl && imageStatus !== 'making'
                                                }">
                                                <span x-text="(imageStatus==='making' && !imageUrl) ? 'RENDERING...' : (imageUrl ? 'View Pic' : 'Make Pic')"></span>
                                            </button>

                                            <button @click="videoUrl ? (openModal='videoPreview', activePreviewUrl=videoUrl) : triggerMakeVideo()" :disabled="videoStatus==='making' || isVideoTriggering || !imageUrl"
                                                class="h-9 px-5 text-[10px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-2 border disabled:opacity-10 shadow-lg"
                                                :class="{
                                                    'bg-pink-500 border-pink-500 text-black animate-pulse shadow-[0_0_15px_rgba(236,72,153,0.4)]': videoStatus === 'making' && !videoUrl,
                                                    'bg-pink-500/10 border-pink-500/20 text-pink-400 hover:bg-pink-500 hover:text-white shadow-pink-500/10': videoUrl,
                                                    'bg-[#1a1a1a] text-gray-300 border-white/10 hover:bg-white hover:text-black': !videoUrl && videoStatus !== 'making'
                                                }">
                                                <span x-text="(videoStatus==='making' && !videoUrl) ? 'SYNTHESIZING...' : (videoUrl ? 'View Video' : 'Make Video')"></span>
                                            </button>

                                            <template x-if="imageUrl && videoUrl && (!brandedImageUrl || !brandedVideoUrl) && !isBranding">
                                                <button @click="brandingModal = true; activeGenId = '{{ $gen->id }}'; activeImageUrl = imageUrl; activeVideoUrl = videoUrl;" 
                                                    class="h-9 px-5 bg-white/5 border border-white/10 hover:border-blue-500/50 text-gray-400 hover:text-blue-400 rounded transition-all uppercase tracking-widest text-[9px] font-black flex items-center gap-2 shadow-lg">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    Add Logo
                                                </button>
                                            </template>

                                            <template x-if="isBranding && (!brandedImageUrl || !brandedVideoUrl)">
                                                <button disabled class="h-9 px-5 bg-blue-600 border border-blue-500 text-white rounded transition-all uppercase tracking-widest text-[9px] font-black flex items-center gap-2 shadow-lg animate-pulse shadow-[0_0_15px_rgba(37,99,235,0.4)]">
                                                    BRANDING...
                                                </button>
                                            </template>

                                            <template x-if="brandedImageUrl && brandedVideoUrl">
                                                <div class="flex items-center gap-3">
                                                    <button @click="openModal='brandedPreview'" class="h-9 px-5 bg-blue-500/10 border border-blue-500/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded transition-all uppercase tracking-widest text-[9px] font-black shadow-lg">
                                                        Branded Pic
                                                    </button>
                                                    <button @click="openModal='brandedVideoPreview'" class="h-9 px-5 bg-purple-500/10 border border-purple-500/20 text-purple-400 hover:bg-purple-600 hover:text-white rounded transition-all uppercase tracking-widest text-[9px] font-black shadow-lg">
                                                        Branded Video
                                                    </button>
                                                </div>
                                            </template>

                                            <button 
                                                class="h-9 px-5 text-[10px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-2 border bg-indigo-600/10 border-indigo-600/20 text-indigo-400 hover:bg-indigo-600 hover:text-white shadow-lg shadow-indigo-900/10"
                                                @click="$dispatch('notify', { message: 'Social Media Integrations Coming Soon', type: 'info' })">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                                Post Assets
                                            </button>
                                        </div>
                                    @endif

                                    <template x-teleport="body">
                                        <div x-show="openModal" class="fixed inset-0 z-[999] flex items-center justify-center p-6 bg-black/95 backdrop-blur-md" x-cloak>
                                            <div x-show="['image','video','audio'].includes(openModal)" class="bg-[#0a0a0a] border border-white/10 w-full max-w-xl rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                                                <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                                                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]" x-text="openModal.toUpperCase() + ' DIRECTIVE DEFINITION'"></h3>
                                                    <button @click="openModal=null" class="text-gray-500 hover:text-white text-lg">✕</button>
                                                </div>
                                                <div class="p-8">
                                                    <div x-show="!isEditing" class="bg-black p-5 rounded border border-white/5 font-mono text-xs text-gray-400 max-h-[40vh] overflow-y-auto whitespace-pre-wrap leading-relaxed shadow-inner" x-text="openModal==='image' ? liveImagePrompt : (openModal==='video' ? liveVideoPrompt : liveAudioPrompt)"></div>
                                                    <div x-show="isEditing">
                                                        <template x-if="openModal==='image'"><textarea x-model="inputImage" class="w-full h-48 bg-black border border-white/10 rounded p-5 text-white font-mono text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all"></textarea></template>
                                                        <template x-if="openModal==='video'"><textarea x-model="inputVideo" class="w-full h-48 bg-black border border-white/10 rounded p-5 text-white font-mono text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all"></textarea></template>
                                                        <template x-if="openModal==='audio'"><textarea x-model="inputAudio" class="w-full h-48 bg-black border border-white/10 rounded p-5 text-white font-mono text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all"></textarea></template>
                                                    </div>
                                                </div>
                                                <div class="px-8 py-5 border-t border-white/5 flex justify-end gap-3 bg-white/[0.01]">
                                                    <template x-if="(openModal==='image' && !imageUrl) || ( (openModal==='video' || openModal==='audio') && !videoUrl )">
                                                        <div class="flex gap-2">
                                                            <button @click="isEditing=!isEditing" class="px-5 py-2.5 bg-gray-800 text-white rounded text-[10px] font-black uppercase tracking-widest hover:bg-gray-700 transition-colors"><span x-text="isEditing?'Cancel':'Modify Prompt'"></span></button>
                                                            <button x-show="isEditing" @click="saveChanges()" :disabled="isSaving" class="px-5 py-2.5 bg-blue-600 text-white rounded text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-600/20">Sync Data</button>
                                                        </div>
                                                    </template>
                                                    <template x-if="(openModal==='image' && imageUrl) || ( (openModal==='video' || openModal==='audio') && videoUrl )">
                                                        <span class="text-[9px] font-black text-gray-600 italic uppercase tracking-widest">Directive Finalized & Locked</span>
                                                    </template>
                                                </div>
                                            </div>

                                            <div x-show="['preview', 'videoPreview', 'brandedPreview', 'brandedVideoPreview'].includes(openModal)" class="relative w-full max-w-4xl animate-in fade-in slide-in-from-bottom-4 duration-300">
                                                <button @click="openModal=null" class="absolute -top-12 right-0 text-white text-[10px] font-black uppercase tracking-[0.2em] bg-white/5 px-4 py-2 rounded-full hover:bg-red-500 transition-all">Close Pipeline ✕</button>
                                                <div class="bg-black border border-white/10 rounded-xl overflow-hidden shadow-2xl">
                                                    <template x-if="openModal==='preview'">
                                                        <img :src="imageUrl" class="w-full max-h-[80vh] object-contain">
                                                    </template>
                                                    <template x-if="openModal==='videoPreview'">
                                                        <video :src="videoUrl" class="w-full max-h-[80vh] object-contain" controls autoplay loop playsinline></video>
                                                    </template>
                                                    <template x-if="openModal==='brandedPreview'">
                                                        <img :src="brandedImageUrl" class="w-full max-h-[80vh] object-contain">
                                                    </template>
                                                    <template x-if="openModal==='brandedVideoPreview'">
                                                        <video :src="brandedVideoUrl" class="w-full max-h-[80vh] object-contain" controls autoplay loop playsinline></video>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </td>

                                <td class="px-8 py-6 text-right">
                                    <form action="{{ route('cgi.destroy', $gen->id) }}" method="POST" onsubmit="return confirm('Purge directive and all associated assets?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="group flex items-center justify-center h-9 w-9 bg-white/5 border border-white/10 hover:bg-red-500/10 hover:border-red-500/30 rounded transition-all ml-auto">
                                            <svg class="w-4 h-4 text-gray-500 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- BRANDING UPLOAD MODAL --}}
        <template x-teleport="body">
            <div x-show="brandingModal" 
                 x-data="{ logoPreview: null }"
                 @close-branding.window="logoPreview = null; brandingModal = false"
                 class="fixed inset-0 z-[2100] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl" x-cloak>
                <div class="bg-[#0a0a0a] border border-white/10 w-full max-w-md rounded-2xl p-8 shadow-2xl animate-in zoom-in duration-300" @click.away="$dispatch('close-branding')">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-white font-black uppercase tracking-[0.2em] text-sm">Apply Brand Identity</h2>
                            <p class="text-gray-500 text-[9px] uppercase font-bold mt-1">Overlay Logo on Rendered Assets</p>
                        </div>
                        <button @click="$dispatch('close-branding')" class="text-gray-600 hover:text-white transition-colors">✕</button>
                    </div>
                    
                    <form @submit.prevent="
                        isUploadingLogo = true;
                        let formData = new FormData($el);
                        formData.append('id', activeGenId);
                        formData.append('logo', $el.querySelector('input[name=logo]').files[0]);

                        fetch('/cgi/apply-branding', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                $dispatch('close-branding');
                                sessionStorage.setItem('branding_' + activeGenId, 'true');
                                $dispatch('start-branding', activeGenId);
                                $dispatch('notify', { message: 'Neural Branding Initiated', type: 'success' });
                            } else {
                                $dispatch('notify', { message: data.message || 'Branding Failed', type: 'error' });
                            }
                        })
                        .catch(err => {
                            $dispatch('notify', { message: 'Server Connection Error', type: 'error' });
                        })
                        .finally(() => isUploadingLogo = false);
                    ">
                        <div class="relative group border-2 border-dashed border-white/5 rounded-xl p-10 text-center hover:border-blue-500/30 transition-all bg-white/[0.01]">
                            {{-- Clickable Area triggers the input --}}
                            <input type="file" name="logo" required id="logoInput"
                                   @change="const file = $event.target.files[0]; if (file) { logoPreview = URL.createObjectURL(file); }"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            
                            {{-- State 1: No Logo Selected --}}
                            <div x-show="!logoPreview" class="pointer-events-none">
                                <svg class="w-10 h-10 text-gray-700 mx-auto mb-4 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest block">Upload PNG/SVG Logo</span>
                            </div>

                            {{-- State 2: Logo Selected (Click to change) --}}
                            <div x-show="logoPreview" class="relative group/preview pointer-events-none">
                                <img :src="logoPreview" class="max-h-32 mx-auto object-contain rounded-lg">
                                
                                {{-- Change text on hover --}}
                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-lg opacity-0 group-hover/preview:opacity-100 transition-opacity">
                                    <span class="text-[9px] font-black text-white uppercase tracking-widest">Click to Change Identity</span>
                                </div>
                                
                                <p class="text-[8px] font-black text-blue-500 mt-4 uppercase tracking-[0.2em]">Identity Detected</p>
                            </div>
                        </div>

                        <div class="mt-8">
                            <button type="submit" :disabled="isUploadingLogo" class="w-full py-4 bg-blue-600 text-white text-[10px] font-black rounded-lg uppercase tracking-widest shadow-lg shadow-blue-600/20 disabled:opacity-50 flex items-center justify-center gap-3">
                                <span x-text="isUploadingLogo ? 'CONNECTING...' : 'START BRANDING RENDER'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        body { background-color: #050505; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: #050505; }
        ::-webkit-scrollbar-thumb { background: #1a1a1a; border-radius: 10px; }
    </style>
</x-app-layout>