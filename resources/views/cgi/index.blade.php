@php
    $currentUser = auth()->user();
    $isAdmin = $currentUser->role === 'admin';

    // NEW PHP LOGIC: Check UserPackage for real credits
    $activeWallet = \App\Models\UserPackage::where('user_id', $currentUser->id)
        ->where('is_active_selection', 'true')
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })
        ->first();

    // Pull from active wallet, fallback to 0
    $imageCredits = $activeWallet->image_credits ?? 0;
    $videoCredits = $activeWallet->video_credits ?? 0;
    $brandingCredits = $activeWallet->branding_credits ?? 0;
    $socialCredits = $activeWallet->social_post_credits ?? 0;
@endphp

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
        }" @notify.window="add($event.detail.message, $event.detail.type)"
        class="fixed top-6 right-6 z-[2500] flex flex-col gap-3 w-80">


        <template x-for="n in notifications" :key="n.id">

            <div x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform translate-x-8"
                x-transition:enter-end="opacity-100 transform translate-x-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="px-5 py-4 rounded-xl shadow-2xl border flex items-center gap-3 backdrop-blur-xl transition-all"
                :class="{
                    'bg-emerald-500/10 border-emerald-500/20 text-emerald-400': n.type === 'success',
         
                    'bg-red-500/10 border-red-500/20 text-red-400': n.type === 'error',
          
                    'bg-blue-500/10 border-blue-500/20 text-blue-400': n.type === 'info'
                 }">
                <div class="flex-1 text-[10px] font-black uppercase tracking-widest leading-none" x-text="n.message">
                </div>

                <button @click="remove(n.id)" class="text-white/20 hover:text-white">✕</button>
            </div>
        </template>
    </div>


    {{-- Main Workspace --}}
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen"
        x-data="{ brandingModal: false, activeGenId: null, activeImageUrl: '', activeVideoUrl: '', isUploadingLogo: false, activePreviewUrl: '' }">

        {{-- Slim Top Toolbar --}}
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b border-white/5 bg-[#0a0a0a]">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-blue-600 rounded-full shadow-[0_0_10px_rgba(37,99,235,0.5)]"></span>
                    CGI Directive Studio

                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">Neural Asset Pipeline
                    v3.2</p>

            </div>

            <div class="flex items-center gap-3">
                {{-- NEW POST HISTORY BUTTON --}}
                <a href="{{ route('cgi.post_history') }}"
                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600/10 hover:bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 text-[9px] font-black rounded-md transition-all uppercase tracking-widest shadow-lg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Post History
                </a>

                @can('access_cgi_generator')
                <a href="{{ route('cgi.create') }}"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-[9px] font-black rounded-md transition-all uppercase tracking-widest shadow-lg shadow-blue-600/20">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                    New Directive
                </a>
                @endcan
            </div>
        </div>

        <div class="p-4 sm:p-6">
            <div class="bg-[#0a0a0a] border border-white/5 rounded-xl overflow-hidden shadow-2xl">

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">

                        <thead class="bg-white/[0.02] border-b border-white/5">

                            <tr class="text-[9px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                                <th class="px-4 sm:px-6 py-4">Directive Identity</th>


                                <th class="px-4 sm:px-6 py-4 text-center">Neural Prompts</th>
                                <th class="px-4 sm:px-6 py-4">Render Engine</th>

                                @can('delete_images')
                                    <th class="px-4 sm:px-6 py-4 text-right w-16">Control</th>
                                @endcan


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

                                    imageUrl: '{{ $gen->image_url ? (str_starts_with($gen->image_url, "http") ? $gen->image_url : asset("storage/" . $gen->image_url)) : "" }}', 

                                    videoUrl: '{{ $gen->video_url ? (str_starts_with($gen->video_url, "http") ? $gen->video_url : asset("storage/" . $gen->video_url)) : "" }}',

                                    brandedImageUrl: '{{ $gen->branded_image_url ? (str_starts_with($gen->branded_image_url, "http") ? $gen->branded_image_url : asset("storage/" . $gen->branded_image_url)) : "" }}',
                                    brandedVideoUrl: '{{ $gen->branded_video_url ? (str_starts_with($gen->branded_video_url, "http") ? $gen->branded_video_url : asset("storage/" . $gen->branded_video_url)) : "" }}',
                                    isBranding: ('{{ $gen->branded_image_url ?? '' }}' !== '' && '{{ $gen->branded_video_url ?? '' }}' !== '') 
                                                ? false 
                                                : (sessionStorage.getItem('branding_{{ $gen->id }}') === 'true'),

                                    {{-- CREDIT AUTHORIZATION CHECKS --}}
                                    hasImageCredits: {{ ($isAdmin || $imageCredits > 0) ? 'true' : 'false' }},
                                    hasVideoCredits: {{ ($isAdmin || $videoCredits > 0) ? 'true' : 'false' }},
                                    hasBrandingCredits: {{ ($isAdmin || $brandingCredits > 0) ? 'true' : 'false' }},
                                    hasSocialCredits: {{ ($isAdmin || $socialCredits > 0) ? 'true' : 'false' }},

                                    {{-- Post creation state --}}
                                    postType: '',
                                    postUrl: '',
                                    postCaption: '',
                                    isPublishing: false,
                                    isBrandedPost: false,

                                    async publishPostToBackend() {
                                        if(this.isPublishing) return;

                                        if(!this.postUrl) {
                                            $dispatch('notify', { message: 'No media found to publish!', type: 'error' });
                                            return;
                                        }

                                        this.isPublishing = true;

                                        try {
                                            const response = await fetch(`/cgi/{{ $gen->id }}/publish`, {
                                                method: 'POST',
                                                headers: { 
                                                    'Content-Type': 'application/json',
                                                    'Accept': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                },
                                                body: JSON.stringify({
                                                    caption: this.postCaption,
                                                    media_url: this.postUrl,
                                                    type: this.postType,
                                                    is_branded: this.isBrandedPost
                                                })
                                            });

                                            const data = await response.json();

                                            if (response.ok && data.success) {
                                                $dispatch('notify', { message: 'Saved to Database & Sent to Social Media!', type: 'success' });
                                                this.closeModal();
                                                this.postCaption = '';
                                            } else {
                                                $dispatch('notify', { message: data.message || 'Publishing Failed', type: 'error' });
                                            }
                                        } catch(e) {
                                            $dispatch('notify', { message: 'Server communication error', type: 'error' });
                                        } finally {
                                            this.isPublishing = false;
                                        }
                                    },

                                    closeModal() {
                                        this.openModal = null;
                                        document.querySelectorAll('video').forEach(v => v.pause());
                                    },

                                    switchModal(target) {
                                        this.openModal = target;
                                        document.querySelectorAll('video').forEach(v => v.pause());
                                    },

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
                                        } catch(e) { $dispatch('notify', { message: 'Network Sync Error', type: 'error' });
                                        }
                                        finally { this.isSaving = false;
                                        }
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
                                        } catch(e) { $dispatch('notify', { message: 'Render Server Offline', type: 'error' });
                                        }
                                        finally { this.isTriggering = false;
                                        }
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
                                        } catch(e) { $dispatch('notify', { message: 'Pipeline Error', type: 'error' });
                                        }
                                        finally { this.isVideoTriggering = false;
                                        }
                                    }
                                }" x-init="

                                        if(brandedImageUrl && brandedVideoUrl) {
                                            sessionStorage.removeItem('branding_{{ $gen->id }}');
                                            isBranding = false;
                                        }
                                        checkAndReload();
                                    " @start-branding.window="
                                    if($event.detail === '{{ $gen->id }}') {

                                        isBranding = true;
                                        checkAndReload();
                                    }
                                " class="hover:bg-white/[0.01] transition-colors">



                                    <td class="px-4 sm:px-6 py-4 align-top w-1/4">
                                        {{-- CLICKABLE DIRECTIVE IDENTITY (Triggers Details Modal) --}}


                                        <div @click="switchModal('details')"
                                            class="flex flex-col cursor-pointer group p-2 -mx-2 rounded-xl hover:bg-white/[0.03] border border-transparent hover:border-white/5 transition-all">
                                            <div class="flex items-center justify-between gap-2">

                                                <span
                                                    class="text-xs font-black text-gray-100 uppercase tracking-wider leading-tight">{{ $gen->product_name }}</span>

                                                {{-- Hover Eye Icon --}}


                                                <svg class="w-3.5 h-3.5 text-gray-500 opacity-0 group-hover:opacity-100 group-hover:text-blue-400 transition-all flex-shrink-0"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>


                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                    </path>

                                                </svg>

                                            </div>

                                            <span
                                                class="text-[9px] text-gray-500 mt-0.5 font-bold italic line-clamp-1">{{ $gen->marketing_angle }}</span>

                                            <span
                                                class="mt-1 text-[8px] font-black text-blue-500/80 uppercase tracking-[0.2em] line-clamp-1">{{ $gen->visual_prop }}</span>

                                        </div>

                                    </td>


                                    <td class="px-4 sm:px-6 py-4 text-center align-top">
                                        <div class="flex items-center justify-center gap-1 flex-wrap">


                                            <button @click="switchModal('image'); isEditing=false;"
                                                class="px-2.5 py-1.5 bg-white/5 hover:bg-white/10 text-[8px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Image</button>
                                            <button @click="switchModal('video'); isEditing=false;"
                                                class="px-2.5 py-1.5 bg-white/5 hover:bg-white/10 text-[8px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Video</button>
                                            <button @click="switchModal('audio'); isEditing=false;"
                                                class="px-2.5 py-1.5 bg-white/5 hover:bg-white/10 text-[8px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Audio</button>
                                        </div>
                                    </td>


                                    <td class="px-4 sm:px-6 py-4 align-top">
                                        @if($gen->status == 'processing')

                                            <div
                                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-yellow-500/5 border border-yellow-500/10 rounded-lg">
                                                <span
                                                    class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(234,179,8,0.6)]"></span>

                                                <span
                                                    class="text-[8px] font-black text-yellow-500 uppercase tracking-widest">Generating
                                                    DNA</span>
                                            </div>
                                        @else


                                            {{-- ADDED FLEX-WRAP TO PREVENT HORIZONTAL SCROLLING --}}

                                            <div class="flex flex-wrap items-center gap-2">

                                                @can('generate_images')
                                                    <button
                                                        @click="if(!imageUrl && !hasImageCredits) { $dispatch('notify', {message: 'Insufficient Image Credits! Please upgrade plan.', type: 'error'}); return; }; imageUrl ? (switchModal('preview'), activePreviewUrl=imageUrl) : triggerMakePicture()"
                                                        :disabled="imageStatus==='making' || isTriggering || (!imageUrl && !hasImageCredits)"
                                                        class="h-8 px-3 text-[9px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-1.5 border shadow-lg"
                                                        :class="{
                                                                'bg-emerald-500 border-emerald-500 text-black animate-pulse shadow-[0_0_15px_rgba(16,185,129,0.4)]': imageStatus === 'making' && !imageUrl,
                                                                'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white': imageUrl,
                                                                'bg-white text-black border-transparent hover:bg-blue-600 hover:text-white': !imageUrl && imageStatus !== 'making' && hasImageCredits,
                                                                'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed': !imageUrl && imageStatus !== 'making' && !hasImageCredits
                                                            }">
                                                        <span
                                                            x-text="(imageStatus==='making' && !imageUrl) ? 'RENDERING...' : (imageUrl ? 'View Pic' : (hasImageCredits ? 'Make Pic' : '0 Credits'))"></span>
                                                    </button>
                                                @else
                                                    <template x-if="imageUrl">
                                                        <button @click="switchModal('preview'); activePreviewUrl=imageUrl"
                                                            class="h-8 px-3 text-[9px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-1.5 border shadow-lg bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white">
                                                            <span>View Pic</span>
                                                        </button>
                                                    </template>
                                                    <template x-if="!imageUrl">
                                                        <button disabled
                                                            class="h-8 px-3 text-[9px] font-black rounded uppercase tracking-widest flex items-center gap-1.5 border bg-white/5 border-white/10 text-gray-600 cursor-not-allowed">
                                                            <span>Restricted</span>
                                                        </button>
                                                    </template>
                                                @endcan

                                                @can('generate_videos')
                                                    <button
                                                        @click="if(!videoUrl && !hasVideoCredits) { $dispatch('notify', {message: 'Insufficient Video Credits! Please upgrade plan.', type: 'error'}); return; }; videoUrl ? (switchModal('videoPreview'), activePreviewUrl=videoUrl) : triggerMakeVideo()"
                                                        :disabled="videoStatus==='making' || isVideoTriggering || !imageUrl || (!videoUrl && !hasVideoCredits)"
                                                        class="h-8 px-3 text-[9px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-1.5 border disabled:opacity-10 shadow-lg"
                                                        :class="{
                                                                'bg-pink-500 border-pink-500 text-black animate-pulse shadow-[0_0_15px_rgba(236,72,153,0.4)]': videoStatus === 'making' && !videoUrl,
                                                                'bg-pink-500/10 border-pink-500/20 text-pink-400 hover:bg-pink-500 hover:text-white shadow-pink-500/10': videoUrl,
                                                                'bg-[#1a1a1a] text-gray-300 border-white/10 hover:bg-white hover:text-black': !videoUrl && videoStatus !== 'making' && hasVideoCredits,
                                                                'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed': !videoUrl && videoStatus !== 'making' && !hasVideoCredits
                                                            }">
                                                        <span
                                                            x-text="(videoStatus==='making' && !videoUrl) ? 'SYNTHESIZING...' : (videoUrl ? 'View Video' : (hasVideoCredits ? 'Make Video' : '0 Credits'))"></span>
                                                    </button>
                                                @else
                                                    <template x-if="videoUrl">
                                                        <button @click="switchModal('videoPreview'); activePreviewUrl=videoUrl"
                                                            class="h-8 px-3 text-[9px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-1.5 border shadow-lg bg-pink-500/10 border-pink-500/20 text-pink-400 hover:bg-pink-500 hover:text-white shadow-pink-500/10">
                                                            <span>View Video</span>
                                                        </button>
                                                    </template>
                                                    <template x-if="!videoUrl">
                                                        <button disabled
                                                            class="h-8 px-3 text-[9px] font-black rounded uppercase tracking-widest flex items-center gap-1.5 border bg-white/5 border-white/10 text-gray-600 cursor-not-allowed">
                                                            <span>Restricted</span>
                                                        </button>
                                                    </template>
                                                @endcan

                                                @can('apply_branding')
                                                    <template
                                                        x-if="imageUrl && videoUrl && (!brandedImageUrl || !brandedVideoUrl) && !isBranding">
                                                        <button
                                                            @click="if(!hasBrandingCredits) { $dispatch('notify', {message: 'Insufficient Branding Credits!', type: 'error'}); return; }; brandingModal = true; activeGenId = '{{ $gen->id }}'; activeImageUrl = imageUrl; activeVideoUrl = videoUrl;"
                                                            :disabled="!hasBrandingCredits"
                                                            class="h-8 px-3 rounded transition-all uppercase tracking-widest text-[9px] font-black flex items-center gap-1.5 shadow-lg border"
                                                            :class="hasBrandingCredits ? 'bg-white/5 border-white/10 hover:border-blue-500/50 text-gray-400 hover:text-blue-400' : 'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed'">

                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2.5"
                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                                </path>
                                                            </svg>
                                                            <span x-text="hasBrandingCredits ? 'Add Logo' : '0 Credits'"></span>
                                                        </button>
                                                    </template>
                                                @else
                                                    <template x-if="imageUrl && videoUrl && (!brandedImageUrl || !brandedVideoUrl) && !isBranding">
                                                        <button disabled
                                                            class="h-8 px-3 text-[9px] font-black rounded uppercase tracking-widest flex items-center gap-1.5 border bg-white/5 border-white/10 text-gray-600 cursor-not-allowed">
                                                            <span>Restricted</span>
                                                        </button>
                                                    </template>
                                                @endcan


                                                <template x-if="isBranding && (!brandedImageUrl || !brandedVideoUrl)">

                                                    <button disabled
                                                        class="h-8 px-3 bg-blue-600 border border-blue-500 text-white rounded transition-all uppercase tracking-widest text-[9px] font-black flex items-center gap-1.5 shadow-lg animate-pulse shadow-[0_0_15px_rgba(37,99,235,0.4)]">
                                                        BRANDING...


                                                    </button>
                                                </template>



                                                <template x-if="brandedImageUrl && brandedVideoUrl">
                                                    <div class="flex items-center gap-2 flex-wrap">


                                                        <button @click="switchModal('brandedPreview')"
                                                            class="h-8 px-3 bg-blue-500/10 border border-blue-500/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded transition-all uppercase tracking-widest text-[9px] font-black shadow-lg">

                                                            Branded Pic

                                                        </button>


                                                        <button @click="switchModal('brandedVideoPreview')"
                                                            class="h-8 px-3 bg-purple-500/10 border border-purple-500/20 text-purple-400 hover:bg-purple-600 hover:text-white rounded transition-all uppercase tracking-widest text-[9px] font-black shadow-lg">
                                                            Branded Video


                                                        </button>
                                                    </div>


                                                </template>


                                                @can('publish_to_social')
                                                    <button
                                                        class="h-8 px-3 text-[9px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-1.5 border shadow-lg"
                                                        :class="hasSocialCredits ? 'bg-indigo-600/10 border-indigo-600/20 text-indigo-400 hover:bg-indigo-600 hover:text-white shadow-indigo-900/10' : 'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed'"
                                                        :disabled="!hasSocialCredits"
                                                        @click="if(!hasSocialCredits) { $dispatch('notify', {message: 'Insufficient Social Post Credits!', type: 'error'}); return; }; switchModal('postAssets')">


                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z">
                                                            </path>
                                                        </svg>


                                                        <span x-text="hasSocialCredits ? 'Post Assets' : '0 Credits'"></span>
                                                    </button>
                                                @endcan


                                            </div>
                                        @endif


                                        <template x-teleport="body">

                                            <div x-show="openModal"
                                                class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6 bg-black/95 backdrop-blur-md"
                                                x-cloak>



                                                {{-- DETAILS MODAL (New User Settings View) --}}

                                                <div x-show="openModal === 'details'"
                                                    class="bg-[#0a0a0a] border border-white/10 w-full max-w-3xl rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200"
                                                    @click.away="closeModal()">


                                                    <div
                                                        class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">


                                                        <h3
                                                            class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">
                                                            Directive Configuration</h3>
                                                        <button @click="closeModal()"
                                                            class="text-gray-500 hover:text-white text-lg">✕</button>

                                                    </div>

                                                    <div class="p-8 grid grid-cols-1 sm:grid-cols-2 gap-8 bg-black/40">


                                                        <div>
                                                            <p
                                                                class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">
                                                                01. Product Identity</p>
                                                            <p class="text-sm font-black text-white">
                                                                {{ $gen->product_name }}</p>

                                                        </div>

                                                        <div>

                                                            <p
                                                                class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">
                                                                02. Marketing Angle</p>
                                                            <p class="text-xs font-bold text-gray-300 italic">
                                                                {{ $gen->marketing_angle }}</p>

                                                        </div>

                                                        <div class="sm:col-span-2">

                                                            <p
                                                                class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-2">
                                                                03. Visual Props</p>
                                                            <div class="flex flex-wrap gap-1.5">

                                                                @foreach(explode(',', $gen->visual_prop) as $prop)
                                                                    @if(trim($prop))

                                                                        <span
                                                                            class="px-2 py-1 bg-white/5 border border-white/10 rounded text-[9px] font-bold text-gray-300 uppercase">{{ trim($prop) }}</span>
                                                                    @endif
                                                                @endforeach


                                                            </div>
                                                        </div>


                                                        <div>


                                                            <p
                                                                class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">
                                                                04. Atmosphere</p>
                                                            <p class="text-xs font-bold text-gray-300">
                                                                {{ $gen->atmosphere }}</p>

                                                        </div>

                                                        <div>

                                                            <p
                                                                class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">
                                                                05. Camera Motion</p>
                                                            <p class="text-xs font-bold text-gray-300">
                                                                {{ $gen->camera_motion }}</p>

                                                        </div>

                                                        <div>

                                                            <p
                                                                class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">
                                                                06. Composition</p>
                                                            <p class="text-xs font-bold text-gray-300">
                                                                {{ $gen->composition }}</p>

                                                        </div>

                                                        <div>

                                                            <p
                                                                class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">
                                                                07. Lighting Style</p>
                                                            <p class="text-xs font-bold text-gray-300">
                                                                {{ $gen->lighting_style }}</p>

                                                        </div>

                                                    </div>

                                                    <div
                                                        class="px-8 py-5 border-t border-white/5 bg-white/[0.01] flex justify-end">

                                                        <button @click="closeModal()"
                                                            class="px-5 py-2.5 bg-gray-800 text-white rounded text-[10px] font-black uppercase tracking-widest hover:bg-gray-700 transition-colors">Close
                                                            View</button>


                                                    </div>
                                                </div>



                                                <div x-show="['image','video','audio'].includes(openModal)"
                                                    class="bg-[#0a0a0a] border border-white/10 w-full max-w-xl rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">

                                                    <div
                                                        class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">

                                                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]"
                                                            x-text="openModal.toUpperCase() + ' DIRECTIVE DEFINITION'"></h3>

                                                        <button @click="closeModal()"
                                                            class="text-gray-500 hover:text-white text-lg">✕</button>
                                                    </div>


                                                    <div class="p-8">
                                                        <div x-show="!isEditing"
                                                            class="bg-black p-5 rounded border border-white/5 font-mono text-xs text-gray-400 max-h-[40vh] overflow-y-auto whitespace-pre-wrap leading-relaxed shadow-inner"
                                                            x-text="openModal==='image' ? liveImagePrompt : (openModal==='video' ? liveVideoPrompt : liveAudioPrompt)">
                                                        </div>
                                                        <div x-show="isEditing">

                                                            <template x-if="openModal==='image'"><textarea
                                                                    x-model="inputImage"
                                                                    class="w-full h-48 bg-black border border-white/10 rounded p-5 text-white font-mono text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all"></textarea></template>

                                                            <template x-if="openModal==='video'"><textarea
                                                                    x-model="inputVideo"
                                                                    class="w-full h-48 bg-black border border-white/10 rounded p-5 text-white font-mono text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all"></textarea></template>

                                                            <template x-if="openModal==='audio'"><textarea
                                                                    x-model="inputAudio"
                                                                    class="w-full h-48 bg-black border border-white/10 rounded p-5 text-white font-mono text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all"></textarea></template>


                                                        </div>
                                                    </div>


                                                    <div
                                                        class="px-8 py-5 border-t border-white/5 flex justify-end gap-3 bg-white/[0.01]">
                                                        <template
                                                            x-if="(openModal==='image' && !imageUrl) || ( (openModal==='video' || openModal==='audio') && !videoUrl )">
                                                            <div class="flex gap-2">

                                                                <button @click="isEditing=!isEditing"
                                                                    class="px-5 py-2.5 bg-gray-800 text-white rounded text-[10px] font-black uppercase tracking-widest hover:bg-gray-700 transition-colors"><span
                                                                        x-text="isEditing?'Cancel':'Modify Prompt'"></span></button>

                                                                <button x-show="isEditing" @click="saveChanges()"
                                                                    :disabled="isSaving"
                                                                    class="px-5 py-2.5 bg-blue-600 text-white rounded text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-600/20">Sync
                                                                    Data</button>

                                                            </div>

                                                        </template>

                                                        <template
                                                            x-if="(openModal==='image' && imageUrl) || ( (openModal==='video' || openModal==='audio') && videoUrl )">
                                                            <span
                                                                class="text-[9px] font-black text-gray-600 italic uppercase tracking-widest">Directive
                                                                Finalized & Locked</span>

                                                        </template>

                                                    </div>

                                                </div>


                                                <div x-show="['preview', 'videoPreview', 'brandedPreview', 'brandedVideoPreview'].includes(openModal)"
                                                    class="relative w-full max-w-4xl animate-in fade-in slide-in-from-bottom-4 duration-300">


                                                    <button @click="closeModal()"
                                                        class="absolute -top-12 right-0 text-white text-[10px] font-black uppercase tracking-[0.2em] bg-white/5 px-4 py-2 rounded-full hover:bg-red-500 transition-all">Close
                                                        Pipeline ✕</button>
                                                    <div
                                                        class="bg-black border border-white/10 rounded-xl overflow-hidden shadow-2xl">


                                                        <template x-if="openModal==='preview'">
                                                            <img :src="imageUrl" class="w-full max-h-[80vh] object-contain">
                                                        </template>

                                                        <template x-if="openModal==='videoPreview'">

                                                            <video :src="videoUrl"
                                                                class="w-full max-h-[80vh] object-contain" controls autoplay
                                                                loop playsinline></video>

                                                        </template>

                                                        <template x-if="openModal==='brandedPreview'">

                                                            <img :src="brandedImageUrl"
                                                                class="w-full max-h-[80vh] object-contain">
                                                        </template>


                                                        <template x-if="openModal==='brandedVideoPreview'">

                                                            <video :src="brandedVideoUrl"
                                                                class="w-full max-h-[80vh] object-contain" controls autoplay
                                                                loop playsinline></video>
                                                        </template>

                                                    </div>

                                                </div>

                                                {{-- POST ASSETS MODAL --}}
                                                <div x-show="openModal === 'postAssets'"
                                                    class="bg-[#0a0a0a] border border-white/10 w-full max-w-sm rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200"
                                                    @click.away="closeModal()">
                                                    <div
                                                        class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">

                                                        <h3
                                                            class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">
                                                            Post Assets</h3>
                                                        <button @click="closeModal()"
                                                            class="text-gray-500 hover:text-white text-lg">✕</button>

                                                    </div>
                                                    <div class="p-6 flex flex-col gap-4 bg-black/40">

                                                        <button @click="switchModal('postImageOptions')"
                                                            class="w-full py-4 bg-blue-600/10 border border-blue-500/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2">

                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                                </path>
                                                            </svg>

                                                            Post Image
                                                        </button>

                                                        <button @click="switchModal('postVideoOptions')"
                                                            class="w-full py-4 bg-pink-500/10 border border-pink-500/20 text-pink-400 hover:bg-pink-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                                                                </path>
                                                            </svg>
                                                            Post Video

                                                        </button>
                                                    </div>

                                                </div>

                                                {{-- POST IMAGE OPTIONS MODAL --}}

                                                <div x-show="openModal === 'postImageOptions'"
                                                    class="bg-[#0a0a0a] border border-white/10 w-full max-w-sm rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200"
                                                    @click.away="closeModal()">
                                                    <div
                                                        class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">

                                                        <h3
                                                            class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">
                                                            Select Image Version</h3>

                                                        <button @click="closeModal()"
                                                            class="text-gray-500 hover:text-white text-lg">✕</button>
                                                    </div>

                                                    <div class="p-6 flex flex-col gap-4 bg-black/40">
                                                        <button
                                                            @click="postType = 'image'; isBrandedPost = true; postUrl = brandedImageUrl; switchModal('createPost')"
                                                            :disabled="!brandedImageUrl"
                                                            class="w-full py-4 bg-blue-600/10 border border-blue-500/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-blue-600/10 disabled:hover:text-blue-400">

                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                                </path>
                                                            </svg>
                                                            With Logo

                                                        </button>

                                                        <button
                                                            @click="postType = 'image'; isBrandedPost = false; postUrl = imageUrl; switchModal('createPost')"
                                                            :disabled="!imageUrl"
                                                            class="w-full py-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-emerald-500/10 disabled:hover:text-emerald-400">

                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                                </path>
                                                            </svg>
                                                            Without Logo

                                                        </button>
                                                    </div>

                                                    <div
                                                        class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-start">

                                                        <button @click="switchModal('postAssets')"
                                                            class="text-[9px] font-black text-gray-500 hover:text-gray-300 uppercase tracking-widest flex items-center gap-1 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                                            </svg>

                                                            Back
                                                        </button>

                                                    </div>
                                                </div>


                                                {{-- POST VIDEO OPTIONS MODAL --}}
                                                <div x-show="openModal === 'postVideoOptions'"
                                                    class="bg-[#0a0a0a] border border-white/10 w-full max-w-sm rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200"
                                                    @click.away="closeModal()">

                                                    <div
                                                        class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">

                                                        <h3
                                                            class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">
                                                            Select Video Version</h3>
                                                        <button @click="closeModal()"
                                                            class="text-gray-500 hover:text-white text-lg">✕</button>

                                                    </div>
                                                    <div class="p-6 flex flex-col gap-4 bg-black/40">

                                                        <button
                                                            @click="postType = 'video'; isBrandedPost = true; postUrl = brandedVideoUrl; switchModal('createPost')"
                                                            :disabled="!brandedVideoUrl"
                                                            class="w-full py-4 bg-pink-500/10 border border-pink-500/20 text-pink-400 hover:bg-pink-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-pink-500/10 disabled:hover:text-pink-400">

                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                                                                </path>
                                                            </svg>
                                                            With Logo

                                                        </button>

                                                        <button
                                                            @click="postType = 'video'; isBrandedPost = false; postUrl = videoUrl; switchModal('createPost')"
                                                            :disabled="!videoUrl"
                                                            class="w-full py-4 bg-purple-500/10 border border-purple-500/20 text-purple-400 hover:bg-purple-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-purple-500/10 disabled:hover:text-purple-400">

                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                                                                </path>
                                                            </svg>

                                                            Without Logo
                                                        </button>

                                                    </div>
                                                    <div
                                                        class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-start">

                                                        <button @click="switchModal('postAssets')"
                                                            class="text-[9px] font-black text-gray-500 hover:text-gray-300 uppercase tracking-widest flex items-center gap-1 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                                            </svg>

                                                            Back
                                                        </button>

                                                    </div>
                                                </div>

                                                {{-- CREATE POST (SOCIAL COMPOSER) MODAL --}}
                                                <div x-show="openModal === 'createPost'"
                                                    class="bg-[#0a0a0a] border border-white/10 w-full max-w-lg rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200"
                                                    @click.away="closeModal()">
                                                    <div
                                                        class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                                                        <h3
                                                            class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">
                                                            Create Post</h3>
                                                        <button @click="closeModal()"
                                                            class="text-gray-500 hover:text-white text-lg">✕</button>
                                                    </div>

                                                    <div class="p-0 flex flex-col bg-black/40">
                                                        <div class="p-6 flex flex-col gap-4">

                                                            {{-- Unified Header and Input Area --}}
                                                            <div class="flex flex-col gap-3">
                                                                <div class="flex items-center gap-3">
                                                                    <div
                                                                        class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-600 to-pink-500 p-[2px] shadow-lg">
                                                                        <div
                                                                            class="w-full h-full bg-[#0a0a0a] rounded-full flex items-center justify-center border border-black overflow-hidden">
                                                                            <svg class="w-5 h-5 text-gray-300" fill="none"
                                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    stroke-width="1.5"
                                                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                                                </path>
                                                                            </svg>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex flex-col">
                                                                        <span
                                                                            class="text-[12px] font-bold text-white tracking-wide">CGI
                                                                            Director</span>
                                                                        <div
                                                                            class="flex items-center gap-1 text-[10px] text-gray-400 font-medium">
                                                                            <svg class="w-3 h-3" fill="none"
                                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round" stroke-width="2"
                                                                                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                                </path>
                                                                            </svg>
                                                                            Public Broadcast
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <textarea x-model="postCaption"
                                                                    class="w-full bg-transparent text-gray-100 text-[15px] outline-none resize-none placeholder-gray-500 focus:ring-0 border-none p-0 leading-relaxed min-h-[80px]"
                                                                    placeholder="What's on your mind?"></textarea>
                                                            </div>

                                                            {{-- Media Preview attached seamlessly --}}
                                                            <div
                                                                class="rounded-xl overflow-hidden border border-white/10 bg-[#050505] flex justify-center items-center relative group shadow-lg">
                                                                <template x-if="postType === 'image'">
                                                                    <img :src="postUrl"
                                                                        class="w-full max-h-80 object-cover">
                                                                </template>
                                                                <template x-if="postType === 'video'">
                                                                    <video :src="postUrl"
                                                                        class="w-full max-h-80 object-cover" controls
                                                                        playsinline></video>
                                                                </template>
                                                                <div
                                                                    class="absolute top-3 right-3 bg-black/70 backdrop-blur-md px-2.5 py-1 rounded-md text-[9px] font-bold text-white uppercase tracking-wider border border-white/10 shadow-sm pointer-events-none">
                                                                    Preview
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-between items-center">
                                                        <button
                                                            @click="switchModal(postType === 'image' ? 'postImageOptions' : 'postVideoOptions')"
                                                            class="text-[9px] font-black text-gray-500 hover:text-gray-300 uppercase tracking-widest flex items-center gap-1 transition-colors">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                                            </svg>
                                                            Back
                                                        </button>

                                                        {{-- The updated publish button --}}
                                                        <button @click="publishPostToBackend()" :disabled="isPublishing"
                                                            class="px-8 py-2.5 bg-blue-600 text-white rounded text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-600/20 hover:bg-blue-500 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">

                                                            {{-- Normal Icon --}}
                                                            <svg x-show="!isPublishing" class="w-3.5 h-3.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8">
                                                                </path>
                                                            </svg>

                                                            {{-- Loading Spinner --}}
                                                            <svg x-show="isPublishing" class="w-3.5 h-3.5 animate-spin"
                                                                fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                    stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor"
                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                </path>
                                                            </svg>

                                                            <span
                                                                x-text="isPublishing ? 'PUBLISHING...' : 'PUBLISH'"></span>
                                                        </button>
                                                    </div>
                                                </div>

                                            </div>
                                        </template>
                                    </td>

                                    @can('delete_images')
                                        <td class="px-4 sm:px-6 py-4 text-right align-top w-16">
                                            <form action="{{ route('cgi.destroy', $gen->id) }}" method="POST"
                                                onsubmit="return confirm('Purge directive and all associated assets?');">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="group flex items-center justify-center h-8 w-8 bg-white/5 border border-white/10 hover:bg-red-500/10 hover:border-red-500/30 rounded transition-all ml-auto">
                                                    <svg class="w-3.5 h-3.5 text-gray-500 group-hover:text-red-500 transition-colors"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- BRANDING UPLOAD MODAL --}}
        <template x-teleport="body">
            <div x-show="brandingModal" x-data="{ logoPreview: null }"
                @close-branding.window="logoPreview = null; brandingModal = false"
                class="fixed inset-0 z-[2100] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl"
                x-cloak>
                <div class="bg-[#0a0a0a] border border-white/10 w-full max-w-md rounded-2xl p-8 shadow-2xl animate-in zoom-in duration-300"
                    @click.away="$dispatch('close-branding')">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-white font-black uppercase tracking-[0.2em] text-sm">Apply Brand Identity
                            </h2>
                            <p class="text-gray-500 text-[9px] uppercase font-bold mt-1">Overlay Logo on Rendered Assets
                            </p>
                        </div>
                        <button @click="$dispatch('close-branding')"
                            class="text-gray-600 hover:text-white transition-colors">✕</button>
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
                        <div
                            class="relative group border-2 border-dashed border-white/5 rounded-xl p-10 text-center hover:border-blue-500/30 transition-all bg-white/[0.01]">
                            <input type="file" name="logo" required id="logoInput"
                                @change="const file = $event.target.files[0]; if (file) { logoPreview = URL.createObjectURL(file); }"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            <div x-show="!logoPreview" class="pointer-events-none">
                                <svg class="w-10 h-10 text-gray-700 mx-auto mb-4 group-hover:text-blue-500 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                <span
                                    class="text-[10px] font-black text-gray-500 uppercase tracking-widest block">Upload
                                    PNG/SVG Logo</span>
                            </div>
                            <div x-show="logoPreview" class="relative group/preview pointer-events-none">
                                <img :src="logoPreview" class="max-h-32 mx-auto object-contain rounded-lg">
                                <div
                                    class="absolute inset-0 bg-black/60 flex items-center justify-center rounded-lg opacity-0 group-hover/preview:opacity-100 transition-opacity">
                                    <span class="text-[9px] font-black text-white uppercase tracking-widest">Click to
                                        Change Identity</span>
                                </div>
                                <p class="text-[8px] font-black text-blue-500 mt-4 uppercase tracking-[0.2em]">Identity
                                    Detected</p>
                            </div>
                        </div>
                        <div class="mt-8">
                            <button type="submit" :disabled="isUploadingLogo"
                                class="w-full py-4 bg-blue-600 text-white text-[10px] font-black rounded-lg uppercase tracking-widest shadow-lg shadow-blue-600/20 disabled:opacity-50 flex items-center justify-center gap-3">
                                <span x-text="isUploadingLogo ? 'CONNECTING...' : 'START BRANDING RENDER'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            background-color: #050505;
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: #050505;
        }

        ::-webkit-scrollbar-thumb {
            background: #1a1a1a;
            border-radius: 10px;
        }
    </style>
</x-app-layout>