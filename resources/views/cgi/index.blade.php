@php
    $currentUser = auth()->user();
    $isAdmin = $currentUser->isAdmin();

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
    
    // FIXED: Added variables for specific Image and Video branding credits
    $bImageCredits = $activeWallet->branding_image_credits ?? 0;
    $bVideoCredits = $activeWallet->branding_video_credits ?? 0;
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
        x-data="{ 
            brandingModal: false, 
            footerModal: false,
            mergeModal: false,
            activeGenId: null, 
            brandingTarget: null, 
            activeImageUrl: '', 
            activeVideoUrl: '', 
            activeBrandedImageUrl: '', 
            activeBrandedVideoUrl: '', 
            activeMergedImageUrl: '',
            isUploadingLogo: false, 
            activePreviewUrl: '',
            templateUrl: '',
            templateFile: null,
            templatePreview: null,
            selectedTemplatePath: null,
            selectedTemplateId: null,
            isMerging: false,
            mergeKind: 'image',
            studioTab: 'directives',
            @include('partials.merge-template-alpine', ['templateAssets' => $templateAssets ?? collect()])

            footerLeftLogo: null,
            footerRightLogo: null,
            footerLeftPreview: null,
            footerRightPreview: null,
            isAddingFooter: false,
            hasBrandingImageCredits: @json($isAdmin || $bImageCredits > 0),
            hasBrandingVideoCredits: @json($isAdmin || $bVideoCredits > 0),

            async triggerMergeTemplate() {
                const isVideo = this.mergeKind === 'video';
                if (isVideo && !this.hasBrandingVideoCredits) {
                    $dispatch('notify', { message: 'Insufficient Video Branding Credits.', type: 'error' });
                    return;
                }
                if (!isVideo && !this.hasBrandingImageCredits) {
                    $dispatch('notify', { message: 'Insufficient Image Branding Credits.', type: 'error' });
                    return;
                }
                if (!this.selectedTemplateId) {
                    $dispatch('notify', { message: 'Select or upload a template first.', type: 'error' });
                    return;
                }

                this.isMerging = true;
                const route = isVideo ? '{{ route('cgi.mergeVideoTemplate') }}' : '{{ route('cgi.mergeTemplate') }}';
                const formData = new FormData();
                formData.append('id', this.activeGenId);
                formData.append('template_asset_id', this.selectedTemplateId);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch(route, {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        if (isVideo) {
                            sessionStorage.setItem('merge_video_' + this.activeGenId, 'true');
                            $dispatch('start-merge-video-pipeline', this.activeGenId);
                            $dispatch('notify', { message: 'Video Merge Pipeline Started!', type: 'success' });
                        } else {
                            sessionStorage.setItem('merge_' + this.activeGenId, 'true');
                            $dispatch('start-merge-pipeline', this.activeGenId);
                            $dispatch('notify', { message: 'Merge Pipeline Started!', type: 'success' });
                        }
                        this.mergeModal = false;
                        this.resetTemplateSelection();
                    } else {
                        $dispatch('notify', { message: result.message || 'Failed to trigger merge.', type: 'error' });
                    }
                } catch (e) {
                    $dispatch('notify', { message: 'Network Error', type: 'error' });
                } finally {
                    this.isMerging = false;
                }
            },

            async triggerAddFooter() {
                if (!this.hasBrandingImageCredits) {
                    $dispatch('notify', { message: 'Insufficient Image Branding Credits.', type: 'error' });
                    return;
                }
                if (!this.footerLeftLogo && !this.footerRightLogo) {
                    $dispatch('notify', { message: 'Please upload at least one logo.', type: 'error' });
                    return;
                }

                this.isAddingFooter = true;
                const formData = new FormData();
                formData.append('id', this.activeGenId);
                if (this.footerLeftLogo) formData.append('logo_left', this.footerLeftLogo);
                if (this.footerRightLogo) formData.append('logo_right', this.footerRightLogo);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch('{{ route('cgi.addFooter') }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    });
                    const result = await response.json();
                    if (result.success) {
                        sessionStorage.setItem('footer_' + this.activeGenId, 'true');
                        $dispatch('start-footer-pipeline', this.activeGenId);
                        $dispatch('notify', { message: 'Footer Pipeline Started!', type: 'success' });
                        this.footerModal = false;
                    } else {
                        $dispatch('notify', { message: result.message || 'Footer failed.', type: 'error' });
                    }
                } catch (e) {
                    $dispatch('notify', { message: 'Network Error', type: 'error' });
                } finally {
                    this.isAddingFooter = false;
                }
            }
        }">

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
                {{-- POST HISTORY BUTTON --}}
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

        @php
            $showApprovalTab = ($requiresApproval ?? false) || (($approvalHistory['stats']['total'] ?? 0) > 0);
        @endphp
        @if($showApprovalTab)
            @include('partials.studio-tabs', [
                'accent' => 'blue',
                'approvalHistory' => $approvalHistory,
                'showApprovalTab' => true,
            ])
        @endif

        <div x-show="!@json($showApprovalTab) || studioTab === 'directives'" class="p-4 sm:p-6">
            <div class="bg-[#0a0a0a] border border-white/5 rounded-xl overflow-hidden shadow-2xl">

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">

                        <thead class="bg-white/[0.02] border-b border-white/5">

                            <tr class="text-[9px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                                {{-- SHRUNK IDENTITY COLUMN --}}
                                <th class="px-4 sm:px-6 py-4 w-1/5">Directive Identity</th>
                                {{-- SHRUNK NEURAL PROMPT COLUMN --}}
                                <th class="px-4 sm:px-6 py-4 text-center w-28">Neural Prompts</th>
                                {{-- MASSIVE RENDER ENGINE COLUMN --}}
                                <th class="px-4 sm:px-6 py-4 w-[50%] min-w-[400px]">Render Engine</th>

                                @can('delete_images')
                                    <th class="px-4 sm:px-6 py-4 text-right w-16">Control</th>
                                @endcan

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/[0.03]">

                            @foreach($generations as $gen)

                                @php
                                    $reqAppr = $requiresApproval ?? false;
                                    $mImgMeta = ($reqAppr && $gen->merged_image_url)
                                        ? \App\Http\Controllers\ApprovalController::mergedApprovalMeta('cgi', $gen->id, 'image')
                                        : ['status' => '', 'comment' => ''];
                                    $mVidMeta = ($reqAppr && $gen->merged_video_url)
                                        ? \App\Http\Controllers\ApprovalController::mergedApprovalMeta('cgi', $gen->id, 'video')
                                        : ['status' => '', 'comment' => ''];
                                @endphp

                                <tr x-data="{
                                    requiresApproval: {{ $reqAppr ? 'true' : 'false' }},
                                    mergedImageStatus: '{{ $mImgMeta['status'] }}',
                                    mergedImageNote: @js($mImgMeta['comment']),
                                    mergedVideoStatus: '{{ $mVidMeta['status'] }}',
                                    mergedVideoNote: @js($mVidMeta['comment']),
                                    status: '{{ $gen->status }}',

                                    imageStatus: '{{ $gen->image_url ? 'completed' : $gen->image_status }}',
                                    videoStatus: '{{ $gen->video_url ? 'completed' : ($gen->video_status ?? 'pending') }}',
                                    footerStatus: '{{ $gen->footer_image_url ? 'completed' : ($gen->footer_status ?? 'pending') }}',
                                    mergeStatus: '{{ $gen->merged_image_url ? 'completed' : ($gen->merge_status ?? 'pending') }}',
                                    mergeVideoStatus: '{{ $gen->merged_video_url ? 'completed' : ($gen->merge_video_status ?? 'pending') }}',
                                    videoErrorMessage: @js($gen->video_error_message ?? ''),

                                    openModal: null, showProductReferenceImage: false,
                                    productReferenceImageUrl: @js($gen->product_image ? (str_starts_with($gen->product_image, 'http') ? $gen->product_image : asset('storage/' . $gen->product_image)) : null),
                                    isEditing: false, isSaving: false, isTriggering: false, isVideoTriggering: false,
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
                                    footerImageUrl: '{{ $gen->footer_image_url ? (str_starts_with($gen->footer_image_url, "http") ? $gen->footer_image_url : asset("storage/" . $gen->footer_image_url)) : "" }}',
                                    mergedImageUrl: '{{ $gen->merged_image_url ? (str_starts_with($gen->merged_image_url, "http") ? $gen->merged_image_url : asset("storage/" . $gen->merged_image_url)) : "" }}',
                                    mergedVideoUrl: '{{ $gen->merged_video_url ? (str_starts_with($gen->merged_video_url, "http") ? $gen->merged_video_url : asset("storage/" . $gen->merged_video_url)) : "" }}',
                                    
                                    {{-- Split tracking for independent branding --}}
                                    isBrandingImage: ('{{ $gen->branded_image_url ?? '' }}' !== '') 
                                                ? false 
                                                : (sessionStorage.getItem('branding_img_{{ $gen->id }}') === 'true'),
                                                
                                    isBrandingVideo: ('{{ $gen->branded_video_url ?? '' }}' !== '') 
                                                ? false 
                                                : (sessionStorage.getItem('branding_vid_{{ $gen->id }}') === 'true'),
                                    
                                    isAddingFooterLocal: ('{{ $gen->footer_image_url ?? '' }}' !== '') 
                                                ? false 
                                                : (sessionStorage.getItem('footer_{{ $gen->id }}') === 'true'),

                                    isMergingLocal: ('{{ $gen->merged_image_url ?? '' }}' !== '' || '{{ $gen->merge_status }}' === 'completed') 
                                                ? false 
                                                : (sessionStorage.getItem('merge_{{ $gen->id }}') === 'true'),

                                    isMergingVideoLocal: ('{{ $gen->merged_video_url ?? '' }}' !== '' || '{{ $gen->merge_video_status }}' === 'completed') 
                                                ? false 
                                                : (sessionStorage.getItem('merge_video_{{ $gen->id }}') === 'true'),

                                    {{-- FIXED CREDIT AUTHORIZATION CHECKS (Uses Active Wallet Variables) --}}
                                    hasImageCredits: {{ ($isAdmin || $imageCredits > 0) ? 'true' : 'false' }},
                                    hasVideoCredits: {{ ($isAdmin || $videoCredits > 0) ? 'true' : 'false' }},
                                    hasBrandingImageCredits: {{ ($isAdmin || $bImageCredits > 0) ? 'true' : 'false' }},
                                    hasBrandingVideoCredits: {{ ($isAdmin || $bVideoCredits > 0) ? 'true' : 'false' }},
                                    hasSocialCredits: {{ ($isAdmin || $socialCredits > 0) ? 'true' : 'false' }},

                                    {{-- Post creation state --}}
                                    productName: @js($gen->product_name),
                                    marketingAngle: @js($gen->marketing_angle ?? ''),
                                    postType: '',
                                    postUrl: '',
                                    postCaption: '',
                                    isPublishing: false,
                                    isBrandedPost: false,
                                    isGeneratingCaption: false,
                                    captionFromAI: false,
                                    captionLangPickerOpen: false,
                                    captionLanguages: @js($captionLanguages ?? []),

                                    buildDefaultCaption() {
                                        const lines = [];
                                        if (this.productName) lines.push(this.productName);
                                        if (this.marketingAngle) {
                                            const benefits = this.marketingAngle.split(',').map(s => s.trim()).filter(Boolean);
                                            if (benefits.length) lines.push(benefits.join(' • '));
                                        }
                                        return lines.join('\n\n');
                                    },

                                    resizePostCaption() {
                                        this.$nextTick(() => {
                                            requestAnimationFrame(() => {
                                                const el = this.$refs.postCaptionInput;
                                                if (!el) return;
                                                el.style.height = 'auto';
                                                const h = Math.min(el.scrollHeight + 4, 280);
                                                el.style.height = Math.max(h, 88) + 'px';
                                            });
                                        });
                                    },

                                    applyCaptionFallback(fallback) {
                                        const text = (fallback || '').trim() || this.buildDefaultCaption();
                                        this.postCaption = text;
                                        this.captionFromAI = false;
                                        this.resizePostCaption();
                                    },

                                    openCaptionLanguagePicker() {
                                        if (!this.postUrl) {
                                            $dispatch('notify', { message: 'Select media to caption first.', type: 'error' });
                                            return;
                                        }
                                        if (!Array.isArray(this.captionLanguages) || this.captionLanguages.length === 0) {
                                            $dispatch('notify', { message: 'Caption languages unavailable — refresh or contact support.', type: 'error' });
                                            return;
                                        }
                                        this.captionLangPickerOpen = true;
                                    },

                                    async fetchAutoCaption(captionLanguage) {
                                        if (!this.postUrl || !captionLanguage || this.isGeneratingCaption) return;

                                        this.captionLangPickerOpen = false;
                                        this.isGeneratingCaption = true;
                                        try {
                                            const res = await fetch('{{ route('cgi.generateCaption', $gen->id) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'Accept': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                },
                                                body: JSON.stringify({
                                                    media_url: this.postUrl,
                                                    media_type: this.postType,
                                                    is_branded: this.isBrandedPost,
                                                    caption_language: captionLanguage,
                                                })
                                            });
                                            const data = await res.json();
                                            if (data.success && (data.caption || '').trim()) {
                                                this.postCaption = data.caption.trim();
                                                this.captionFromAI = true;
                                                this.resizePostCaption();
                                                return;
                                            }
                                            this.applyCaptionFallback(data.fallback_caption);
                                            if (!res.ok) {
                                                $dispatch('notify', { message: data.message || 'Using directive caption (AI unavailable).', type: 'warning' });
                                            }
                                        } catch (e) {
                                            this.applyCaptionFallback();
                                            $dispatch('notify', { message: 'Caption engine offline — using directive text.', type: 'warning' });
                                        } finally {
                                            this.isGeneratingCaption = false;
                                        }
                                    },

                                    openCreatePost(type, url, branded = false) {
                                        this.postType = type;
                                        this.postUrl = url;
                                        this.isBrandedPost = branded;
                                        this.postCaption = '';
                                        this.captionFromAI = false;
                                        this.switchModal('createPost');
                                    },

                                    canPublishNow() {
                                        if (!this.requiresApproval) return true;
                                        if (this.postUrl === this.mergedImageUrl) return this.mergedImageStatus === 'approved';
                                        if (this.postUrl === this.mergedVideoUrl) return this.mergedVideoStatus === 'approved';
                                        return true;
                                    },

                                    publishBlockedMessage() {
                                        if (this.postUrl === this.mergedImageUrl) {
                                            if (this.mergedImageStatus === 'rejected') return 'Rejected by approver: ' + (this.mergedImageNote || 'see note');
                                            return 'Awaiting approver approval before posting merged picture.';
                                        }
                                        if (this.postUrl === this.mergedVideoUrl) {
                                            if (this.mergedVideoStatus === 'rejected') return 'Rejected by approver: ' + (this.mergedVideoNote || 'see note');
                                            return 'Awaiting approver approval before posting merged video.';
                                        }
                                        return 'Only approved merged pictures and merged videos can be published with approver sign-off.';
                                    },

                                    activeApproverNote() {
                                        if (!this.requiresApproval) return '';
                                        if (this.postUrl === this.mergedImageUrl && this.mergedImageStatus === 'approved') return this.mergedImageNote || '';
                                        if (this.postUrl === this.mergedVideoUrl && this.mergedVideoStatus === 'approved') return this.mergedVideoNote || '';
                                        return '';
                                    },

                                    async publishPostToBackend() {
                                        if(this.isPublishing) return;

                                        if(!this.postUrl) {
                                            $dispatch('notify', { message: 'No media found to publish!', type: 'error' });
                                            return;
                                        }

                                        if (!this.canPublishNow()) {
                                            $dispatch('notify', { message: this.publishBlockedMessage(), type: 'error' });
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
                                        this.showProductReferenceImage = false;
                                        this.captionLangPickerOpen = false;
                                        document.querySelectorAll('video').forEach(v => v.pause());
                                    },

                                    switchModal(target) {
                                        this.openModal = target;
                                        document.querySelectorAll('video').forEach(v => v.pause());
                                    },

                                    checkAndReload() {
                                        let loading = false;
                                        
                                        // 1. Initial DNA/Rendering
                                        // Compare Unix epoch seconds (timezone & parser proof) instead of
                                        // parsing the formatted datetime string, which breaks across timezones.
                                        const isRecent = ((Date.now() / 1000) - {{ $gen->created_at->timestamp }}) < 600;
                                        if (this.status === 'processing' && isRecent) loading = true;
                                        
                                        // 2. Base Rendering Checks
                                        if (this.imageStatus === 'making' && !this.imageUrl) loading = true;
                                        if (this.videoStatus === 'making' && !this.videoUrl) loading = true;
                                        
                                        // 3. Branding / Footer Pipelines (Decoupled from DB status, relies on Session Storage)
                                        if (this.isBrandingImage && !this.brandedImageUrl) loading = true;
                                        if (this.isBrandingVideo && !this.brandedVideoUrl) loading = true;
                                        if (this.isAddingFooterLocal && !this.footerImageUrl) loading = true;
                                        if (this.isMergingLocal && !this.mergedImageUrl && this.mergeStatus !== 'completed') loading = true;
                                        if (this.isMergingVideoLocal && !this.mergedVideoUrl && this.mergeVideoStatus !== 'completed') loading = true;
                                        
                                        if (loading) {
                                            console.log('Neural Pipeline Active: Scheduling sync in 5s...');
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
                                                this.videoErrorMessage = ''; // clear error state
                                                $dispatch('notify', { message: 'Video Synthesis Started', type: 'info' });
                                                this.checkAndReload();
                                            }
                                        } catch(e) { $dispatch('notify', { message: 'Pipeline Error', type: 'error' });
                                        }
                                        finally { this.isVideoTriggering = false;
                                        }
                                    }
                                }" x-init="

                                        if(brandedImageUrl) {
                                            sessionStorage.removeItem('branding_img_{{ $gen->id }}');
                                            isBrandingImage = false;
                                        }
                                        if(brandedVideoUrl) {
                                            sessionStorage.removeItem('branding_vid_{{ $gen->id }}');
                                            isBrandingVideo = false;
                                        }
                                        if(footerImageUrl) {
                                            sessionStorage.removeItem('footer_{{ $gen->id }}');
                                            isAddingFooterLocal = false;
                                        }
                                        if(mergedImageUrl || mergeStatus === 'completed') {
                                            sessionStorage.removeItem('merge_{{ $gen->id }}');
                                            isMergingLocal = false;
                                        }
                                        if(mergedVideoUrl || mergeVideoStatus === 'completed') {
                                            sessionStorage.removeItem('merge_video_{{ $gen->id }}');
                                            isMergingVideoLocal = false;
                                        }
                                        checkAndReload();
                                    " 
                                    @start-branding-image.window="
                                        if($event.detail == '{{ $gen->id }}') {
                                            isBrandingImage = true;
                                            checkAndReload();
                                        }
                                    "
                                    @start-branding-video.window="
                                        if($event.detail == '{{ $gen->id }}') {
                                            isBrandingVideo = true;
                                            checkAndReload();
                                        }
                                    "
                                    @start-footer-pipeline.window="
                                        if($event.detail == '{{ $gen->id }}') {
                                            isAddingFooterLocal = true;
                                            checkAndReload();
                                        }
                                    "
                                    @start-merge-pipeline.window="
                                        if($event.detail == '{{ $gen->id }}') {
                                            isMergingLocal = true;
                                            checkAndReload();
                                        }
                                    "
                                    @start-merge-video-pipeline.window="
                                        if($event.detail == '{{ $gen->id }}') {
                                            isMergingVideoLocal = true;
                                            checkAndReload();
                                        }
                                    "
                                    class="hover:bg-white/[0.01] transition-colors">



                                    {{-- SHRUNK IDENTITY COLUMN --}}
                                    <td class="px-4 sm:px-6 py-4 align-top w-1/5">
                                        {{-- CLICKABLE DIRECTIVE IDENTITY (Triggers Details Modal) --}}


                                        <div @click="switchModal('details')"
                                            class="flex flex-col cursor-pointer group p-2 -mx-2 rounded-xl hover:bg-white/[0.03] border border-transparent hover:border-white/5 transition-all">
                                            <div class="flex items-center justify-between gap-2">

                                                {{-- TEXT SHRUNK TO 11px --}}
                                                <span
                                                    class="text-[11px] font-black text-gray-100 uppercase tracking-wider leading-tight">{{ $gen->product_name }}</span>

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

                                            {{-- TEXT SHRUNK TO 8px --}}
                                            <span
                                                class="text-[8px] text-gray-500 mt-0.5 font-bold italic line-clamp-1">{{ $gen->marketing_angle }}</span>

                                            {{-- TEXT SHRUNK TO 7px --}}
                                            <span
                                                class="mt-1 text-[7px] font-black text-blue-500/80 uppercase tracking-[0.2em] line-clamp-1">{{ $gen->visual_prop }}</span>

                                        </div>

                                    </td>


                                    {{-- SHRUNK NEURAL PROMPT COLUMN --}}
                                    <td class="px-4 sm:px-6 py-4 text-center align-top w-28">
                                        <div class="flex items-center justify-center gap-1 flex-wrap">


                                            {{-- BUTTON TEXT SHRUNK TO 7px --}}
                                            <button @click="switchModal('image'); isEditing=false;"
                                                class="px-2 py-1 bg-white/5 hover:bg-white/10 text-[7px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Image</button>
                                            <button @click="switchModal('video'); isEditing=false;"
                                                class="px-2 py-1 bg-white/5 hover:bg-white/10 text-[7px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Video</button>
                                            <button @click="switchModal('audio'); isEditing=false;"
                                                class="px-2 py-1 bg-white/5 hover:bg-white/10 text-[7px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Audio</button>
                                        </div>
                                    </td>


                                    {{-- EXPANDED RENDER ENGINE COLUMN --}}
                                    <td class="px-4 sm:px-6 py-4 align-top w-[50%] min-w-[400px]">
                                        @if($gen->status == 'processing')
                                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-yellow-500/5 border border-yellow-500/10 rounded-lg">
                                                <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(234,179,8,0.6)]"></span>
                                                <span class="text-[8px] font-black text-yellow-500 uppercase tracking-widest">Generating DNA</span>
                                            </div>
                                        @else
                                            <div class="flex flex-col gap-3">
                                                {{-- IMAGE RENDERING CONTROLS --}}
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    @can('generate_images')
                                                        {{-- ALL TEXT IN BUTTONS SHRUNK TO 8px --}}
                                                        <button
                                                            @click="if(!imageUrl && !hasImageCredits) { $dispatch('notify', {message: 'Insufficient Image Credits! Please upgrade plan.', type: 'error'}); return; }; imageUrl ? (switchModal('preview'), activePreviewUrl=imageUrl) : triggerMakePicture()"
                                                            :disabled="imageStatus==='making' || isTriggering || (!imageUrl && !hasImageCredits)"
                                                            class="h-8 min-w-[95px] px-2 text-[8px] font-black rounded transition-all uppercase tracking-widest flex items-center justify-center gap-1.5 border shadow-lg"
                                                            :class="{
                                                                    'bg-emerald-500 border-emerald-500 text-black animate-pulse shadow-[0_0_15px_rgba(16,185,129,0.4)]': imageStatus === 'making' && !imageUrl,
                                                                    'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white': imageUrl,
                                                                    'bg-white text-black border-transparent hover:bg-blue-600 hover:text-white': !imageUrl && imageStatus !== 'making' && hasImageCredits,
                                                                    'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed': !imageUrl && imageStatus !== 'making' && !hasImageCredits
                                                                }">
                                                            <span x-text="(imageStatus==='making' && !imageUrl) ? 'RENDERING' : (imageUrl ? 'View Image' : (hasImageCredits ? 'Make Image' : '0 Cr'))"></span>
                                                        </button>
                                                    @endcan
                                                    
                                                    {{-- ========================================================
                                                         INLINE BRANDING BUTTONS FOR IMAGE (SAFE TEMPLATE LOGIC)
                                                         ======================================================== --}}
                                                    
                                                    {{-- 1. Ready to Brand Button --}}
                                                    <template x-if="imageUrl !== '' && !isBrandingImage && brandedImageUrl === ''">
                                                        <button
                                                            @click="if(!hasBrandingImageCredits) { $dispatch('notify', {message: 'Insufficient Credits', type: 'error'}); return; }; brandingModal = true; activeGenId = '{{ $gen->id }}'; activeImageUrl = imageUrl; brandingTarget = 'image';"
                                                            class="h-8 px-2 rounded-lg border border-blue-500/30 bg-blue-500/10 text-blue-400 hover:bg-blue-600 hover:text-white transition-all text-[8px] font-black uppercase tracking-widest shadow-lg flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                            <span>Add Logo</span>
                                                        </button>
                                                    </template>

                                                    {{-- 2. Branding Loading State --}}
                                                    <template x-if="imageUrl !== '' && isBrandingImage && brandedImageUrl === ''">
                                                        <button disabled
                                                            class="h-8 px-2 bg-blue-600 border border-blue-500 text-white rounded-lg text-[8px] font-black uppercase tracking-widest animate-pulse shadow-lg">
                                                            Logo...
                                                        </button>
                                                    </template>

                                                    {{-- 3. Branded Pic Ready View --}}
                                                    <template x-if="imageUrl !== '' && brandedImageUrl !== ''">
                                                        <button @click="switchModal('brandedPreview')"
                                                            class="h-8 px-2 bg-blue-500/10 border border-blue-500/30 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg text-[8px] font-black uppercase tracking-widest shadow-lg transition-all">
                                                            Branded
                                                        </button>
                                                    </template>

                                                    {{-- 4. Add Footer Button --}}
                                                    <template x-if="imageUrl !== '' && !footerImageUrl && !isAddingFooterLocal && footerStatus !== 'making'">
                                                        <button
                                                            @click="if(!hasBrandingImageCredits) { $dispatch('notify', {message: 'Insufficient Image Branding Credits', type: 'error'}); return; } footerModal = true; activeGenId = '{{ $gen->id }}'; activeImageUrl = imageUrl;"
                                                            :disabled="!hasBrandingImageCredits"
                                                            class="h-8 px-2 rounded transition-all uppercase tracking-widest text-[8px] font-black flex items-center gap-1.5 shadow-lg border"
                                                            :class="hasBrandingImageCredits ? 'bg-emerald-600/10 border-emerald-500/20 hover:border-emerald-500/50 text-emerald-400 hover:bg-emerald-600 hover:text-white' : 'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed'">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                            <span x-text="hasBrandingImageCredits ? 'Add Footer' : '0 Brand Credits'"></span>
                                                        </button>
                                                    </template>

                                                    {{-- Footer Loading State --}}
                                                    <template x-if="imageUrl !== '' && !footerImageUrl && isAddingFooterLocal">
                                                        <button disabled
                                                            class="h-8 px-2 bg-emerald-600 border border-emerald-500 text-white rounded transition-all uppercase tracking-widest text-[8px] font-black flex items-center gap-1.5 shadow-lg animate-pulse shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                                                            FOOTERING...
                                                        </button>
                                                    </template>

                                                    {{-- Footer Ready View --}}
                                                    <template x-if="imageUrl !== '' && footerImageUrl !== ''">
                                                        <button @click="switchModal('footerPreview')"
                                                            class="h-8 px-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 hover:bg-emerald-600 hover:text-white rounded transition-all uppercase tracking-widest text-[8px] font-black shadow-lg">
                                                            Footered Pic
                                                        </button>
                                                    </template>

                                                    {{-- 5. Merge with Template Button --}}
                                                    <template x-if="imageUrl !== '' && !mergedImageUrl && !isMergingLocal">
                                                        <button
                                                            @click="if(!hasBrandingImageCredits) { $dispatch('notify', {message: 'Insufficient Image Branding Credits', type: 'error'}); return; } resetTemplateSelection(); mergeModal = true; activeGenId = '{{ $gen->id }}'; activeImageUrl = imageUrl;"
                                                            :disabled="!hasBrandingImageCredits"
                                                            class="h-8 px-2 rounded transition-all uppercase tracking-widest text-[8px] font-black flex items-center gap-1.5 shadow-lg border"
                                                            :class="hasBrandingImageCredits ? 'bg-orange-600/10 border-orange-500/20 hover:border-orange-500/50 text-orange-400 hover:bg-orange-600 hover:text-white' : 'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed'">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                            <span x-text="hasBrandingImageCredits ? 'Merge Template' : '0 Brand Credits'"></span>
                                                        </button>
                                                    </template>

                                                    {{-- Merge Loading State --}}
                                                    <template x-if="imageUrl !== '' && mergedImageUrl === '' && mergeStatus !== 'completed' && isMergingLocal">
                                                        <button disabled
                                                            class="h-8 px-2 bg-orange-600 border border-orange-500 text-white rounded transition-all uppercase tracking-widest text-[8px] font-black flex items-center gap-1.5 shadow-lg animate-pulse shadow-[0_0_15px_rgba(249,115,22,0.4)]">
                                                            MERGING...
                                                        </button>
                                                    </template>

                                                    {{-- Merge Ready View --}}
                                                    <template x-if="imageUrl !== '' && (mergedImageUrl !== '' || mergeStatus === 'completed')">
                                                        <button @click="switchModal('mergedPreview')"
                                                            class="h-8 px-2 bg-orange-500/10 border border-orange-500/20 text-orange-400 hover:bg-orange-600 hover:text-white rounded transition-all uppercase tracking-widest text-[8px] font-black shadow-lg">
                                                            View Merged Image
                                                        </button>
                                                    </template>

                                                    {{-- Merged image approval status --}}
                                                    <template x-if="requiresApproval && (mergedImageUrl !== '' || mergeStatus === 'completed')">
                                                        <span class="h-8 px-2 inline-flex items-center rounded uppercase tracking-widest text-[8px] font-black border"
                                                            :class="mergedImageStatus === 'approved' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : (mergedImageStatus === 'rejected' ? 'bg-red-500/10 border-red-500/30 text-red-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400')"
                                                            :title="mergedImageNote || ''"
                                                            x-text="mergedImageStatus === 'approved' ? '✓ Approved' : (mergedImageStatus === 'rejected' ? '✕ Rejected' : '⏳ Awaiting Approval')"></span>
                                                    </template>

                                                </div>

                                                @include('partials.approver-note', [
                                                    'requiresApproval' => $reqAppr,
                                                    'status' => $mImgMeta['status'],
                                                    'comment' => $mImgMeta['comment'],
                                                ])

                                                {{-- VIDEO RENDERING CONTROLS --}}
                                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                                    
                                                    {{-- Make/View/Retry Video --}}
                                                    @can('generate_videos')
                                                        <button
                                                            @click="if(!videoUrl && !hasVideoCredits) { $dispatch('notify', {message: 'Insufficient Video Credits! Please upgrade plan.', type: 'error'}); return; }; videoUrl ? (switchModal('videoPreview'), activePreviewUrl=videoUrl) : triggerMakeVideo()"
                                                            :disabled="videoStatus==='making' || isVideoTriggering || !imageUrl || (!videoUrl && !hasVideoCredits && videoStatus !== 'failed')"
                                                            class="h-8 px-2 text-[8px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-1.5 border disabled:opacity-10 shadow-lg"
                                                            :class="{
                                                                    'bg-red-500/10 border-red-500/30 text-red-400 hover:bg-red-600 hover:text-white shadow-red-500/10': videoStatus === 'failed' && !videoUrl,
                                                                    'bg-pink-500 border-pink-500 text-black animate-pulse shadow-[0_0_15px_rgba(236,72,153,0.4)]': videoStatus === 'making' && !videoUrl,
                                                                    'bg-pink-500/10 border-pink-500/20 text-pink-400 hover:bg-pink-500 hover:text-white shadow-pink-500/10': videoUrl,
                                                                    'bg-[#1a1a1a] text-gray-300 border-white/10 hover:bg-white hover:text-black': !videoUrl && videoStatus !== 'making' && videoStatus !== 'failed' && hasVideoCredits,
                                                                    'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed': !videoUrl && videoStatus !== 'making' && videoStatus !== 'failed' && !hasVideoCredits
                                                                }">
                                                            <span
                                                                x-text="videoStatus === 'failed' && !videoUrl ? 'RETRY VIDEO' : ((videoStatus==='making' && !videoUrl) ? 'SYNTHESIZING...' : (videoUrl ? 'View Video' : (hasVideoCredits ? 'Make Video' : '0 Credits')))"></span>
                                                        </button>
                                                        
                                                        {{-- FAILED STATUS INDICATOR / ERROR VIEWER --}}
                                                        <template x-if="videoStatus === 'failed' && !videoUrl">
                                                            <button @click="switchModal('videoError')" class="h-8 px-2 bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-600 hover:text-white rounded transition-all shadow-lg flex items-center justify-center group" title="View Error Details">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                            </button>
                                                        </template>
                                                    @endcan
                                                    
                                                    {{-- ========================================================
                                                         INLINE BRANDING BUTTONS FOR VIDEO (SAFE TEMPLATE LOGIC)
                                                         ======================================================== --}}
                                                    
                                                    {{-- 1. Ready to Brand Button --}}
                                                    <template x-if="videoUrl !== '' && !isBrandingVideo && brandedVideoUrl === ''">
                                                        <button
                                                            @click="if(!hasBrandingVideoCredits) { $dispatch('notify', {message: 'Insufficient Video Branding Credits!', type: 'error'}); return; }; brandingModal = true; activeGenId = '{{ $gen->id }}'; activeVideoUrl = videoUrl; brandingTarget = 'video';"
                                                            :disabled="!hasBrandingVideoCredits"
                                                            class="h-8 px-2 rounded transition-all uppercase tracking-widest text-[8px] font-black flex items-center gap-1.5 shadow-lg border"
                                                            :class="hasBrandingVideoCredits ? 'bg-purple-500/10 border-purple-500/20 hover:border-purple-500/50 text-purple-400 hover:bg-purple-600 hover:text-white' : 'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed'">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                            <span x-text="hasBrandingVideoCredits ? 'Add Logo in Video' : '0 Credits'"></span>
                                                        </button>
                                                    </template>

                                                    {{-- 2. Branding Loading State --}}
                                                    <template x-if="videoUrl !== '' && isBrandingVideo && brandedVideoUrl === ''">
                                                        <button disabled
                                                            class="h-8 px-2 bg-purple-600 border border-purple-500 text-white rounded transition-all uppercase tracking-widest text-[8px] font-black flex items-center gap-1.5 shadow-lg animate-pulse shadow-[0_0_15px_rgba(147,51,234,0.4)]">
                                                            BRANDING VID...
                                                        </button>
                                                    </template>

                                                    {{-- 3. Branded Video Ready View --}}
                                                    <template x-if="videoUrl !== '' && brandedVideoUrl !== ''">
                                                        <button @click="switchModal('brandedVideoPreview')"
                                                            class="h-8 px-2 bg-purple-500/10 border border-purple-500/20 text-purple-400 hover:bg-purple-600 hover:text-white rounded transition-all uppercase tracking-widest text-[8px] font-black shadow-lg">
                                                            Branded Vid
                                                        </button>
                                                    </template>

                                                    {{-- 4. Merge Video with Template Button --}}
                                                    <template x-if="videoUrl !== '' && !mergedVideoUrl && !isMergingVideoLocal">
                                                        <button
                                                            @click="if(!hasBrandingVideoCredits) { $dispatch('notify', {message: 'Insufficient Video Branding Credits', type: 'error'}); return; } resetTemplateSelection(); mergeKind = 'video'; mergeModal = true; activeGenId = '{{ $gen->id }}'; activeVideoUrl = videoUrl;"
                                                            :disabled="!hasBrandingVideoCredits"
                                                            class="h-8 px-2 rounded transition-all uppercase tracking-widest text-[8px] font-black flex items-center gap-1.5 shadow-lg border"
                                                            :class="hasBrandingVideoCredits ? 'bg-orange-600/10 border-orange-500/20 hover:border-orange-500/50 text-orange-400 hover:bg-orange-600 hover:text-white' : 'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed'">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                            <span x-text="hasBrandingVideoCredits ? 'Merge Video' : '0 Brand Credits'"></span>
                                                        </button>
                                                    </template>

                                                    {{-- Merge Video Loading State --}}
                                                    <template x-if="videoUrl !== '' && mergedVideoUrl === '' && mergeVideoStatus !== 'completed' && isMergingVideoLocal">
                                                        <button disabled
                                                            class="h-8 px-2 bg-orange-600 border border-orange-500 text-white rounded transition-all uppercase tracking-widest text-[8px] font-black flex items-center gap-1.5 shadow-lg animate-pulse shadow-[0_0_15px_rgba(249,115,22,0.4)]">
                                                            MERGING VID...
                                                        </button>
                                                    </template>

                                                    {{-- Merge Video Ready View --}}
                                                    <template x-if="videoUrl !== '' && (mergedVideoUrl !== '' || mergeVideoStatus === 'completed')">
                                                        <button @click="switchModal('mergedVideoPreview')"
                                                            class="h-8 px-2 bg-orange-500/10 border border-orange-500/20 text-orange-400 hover:bg-orange-600 hover:text-white rounded transition-all uppercase tracking-widest text-[8px] font-black shadow-lg">
                                                            View Merged Vid
                                                        </button>
                                                    </template>

                                                    {{-- Merged video approval status --}}
                                                    <template x-if="requiresApproval && (mergedVideoUrl !== '' || mergeVideoStatus === 'completed')">
                                                        <span class="h-8 px-2 inline-flex items-center rounded uppercase tracking-widest text-[8px] font-black border"
                                                            :class="mergedVideoStatus === 'approved' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : (mergedVideoStatus === 'rejected' ? 'bg-red-500/10 border-red-500/30 text-red-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400')"
                                                            :title="mergedVideoNote || ''"
                                                            x-text="mergedVideoStatus === 'approved' ? '✓ Approved' : (mergedVideoStatus === 'rejected' ? '✕ Rejected' : '⏳ Awaiting Approval')"></span>
                                                    </template>

                                                </div>

                                                @include('partials.approver-note', [
                                                    'requiresApproval' => $reqAppr,
                                                    'status' => $mVidMeta['status'],
                                                    'comment' => $mVidMeta['comment'],
                                                ])

                                                {{-- SOCIAL BROADCASTER --}}
                                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                                    @can('publish_to_social')
                                                        <button
                                                            class="h-8 px-2 text-[8px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-1.5 border shadow-lg"
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


                                            </div>
                                        @endif


                                        <template x-teleport="body">

                                            <div x-show="openModal"
                                                class="fixed inset-0 z-[999] flex items-center justify-center p-3 sm:p-5 bg-black/95 backdrop-blur-md overflow-hidden"
                                                x-cloak>

                                                {{-- ========================================================
                                                     NEW MODAL: VIDEO ERROR VIEWER 
                                                     ======================================================== --}}
                                                <div x-show="openModal === 'videoError'"
                                                     class="bg-[#0a0a0a] border border-red-500/30 w-full max-w-md rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200"
                                                     @click.away="closeModal()">
                                                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-red-500/5">
                                                        <h3 class="text-[10px] font-black text-red-400 uppercase tracking-[0.3em] flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                            Video Generation Error
                                                        </h3>
                                                        <button @click="closeModal()" class="text-gray-500 hover:text-white text-lg">✕</button>
                                                    </div>
                                                    <div class="p-6 bg-black/40">
                                                        <div class="flex items-start gap-4">
                                                            <div class="bg-red-500/10 p-3 rounded-full border border-red-500/20">
                                                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            </div>
                                                            <div>
                                                                <h4 class="text-white text-sm font-bold mb-1 tracking-wide">Pipeline Terminated</h4>
                                                                <p class="text-gray-400 text-xs leading-relaxed mb-3">Google's Generative AI engine encountered a critical error during synthesis. Details are provided below:</p>
                                                                <div class="bg-[#111] border border-white/5 p-3 rounded-lg">
                                                                    <p class="text-red-400 text-[11px] font-mono leading-relaxed break-words" x-text="videoErrorMessage || 'Internal Server Error. The AI node failed to respond.'"></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-end gap-3">
                                                        <button @click="closeModal()" class="px-5 py-2 text-gray-400 hover:text-white text-[10px] font-black uppercase tracking-widest transition-colors">Dismiss</button>
                                                        <button @click="triggerMakeVideo(); closeModal()" class="px-5 py-2.5 bg-red-600 text-white rounded text-[10px] font-black uppercase tracking-widest hover:bg-red-500 transition-colors shadow-lg shadow-red-600/20">Retry Synthesis</button>
                                                    </div>
                                                </div>

                                                {{-- DETAILS MODAL (New User Settings View) --}}

                                                <div x-show="openModal === 'details'"
                                                    class="bg-[#0a0a0a] border border-white/10 w-full max-w-4xl rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200 relative"
                                                    @click.away="if (!showProductReferenceImage) closeModal()">

                                                    @php
                                                        $detailBusiness = \App\Support\CgiBusinessPresets::get($gen->business_type ?? 'lighting') ?? [];
                                                        $detailBusinessLabel = ($detailBusiness['icon'] ?? '') . ' ' . ($detailBusiness['label'] ?? ucfirst($gen->business_type ?? 'lighting'));
                                                        $detailProductImageUrl = $gen->product_image
                                                            ? (str_starts_with($gen->product_image, 'http')
                                                                ? $gen->product_image
                                                                : asset('storage/' . $gen->product_image))
                                                            : null;
                                                        $detailStep01 = $detailBusiness['step01_label'] ?? 'Your product name';
                                                        $detailStep02 = $detailBusiness['step02_label'] ?? 'Your product photo';
                                                        $detailStep03 = $detailBusiness['step03_label'] ?? 'Marketing headline';
                                                        $detailStep04 = $detailBusiness['step04_label'] ?? 'How it is used in real life';
                                                        $detailStep05 = $detailBusiness['step05_label'] ?? 'Which room or place';
                                                        $detailStep06 = $detailBusiness['step06_label'] ?? 'Video camera movement';
                                                        $detailStep07 = $detailBusiness['step07_label'] ?? 'Where product sits on poster';
                                                        $detailStep08 = $detailBusiness['step08_label'] ?? 'Light mood & colors';
                                                    @endphp

                                                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                                                        <div>
                                                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">
                                                                Directive Configuration</h3>
                                                            <p class="text-[9px] font-bold text-blue-400/80 uppercase tracking-widest mt-1">{{ trim($detailBusinessLabel) }}</p>
                                                        </div>
                                                        <button @click="closeModal()"
                                                            class="text-gray-500 hover:text-white text-lg">✕</button>
                                                    </div>

                                                    <div class="p-6 sm:p-8 space-y-6 bg-black/40 max-h-[75vh] overflow-y-auto custom-scrollbar">

                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                                            <div>
                                                                <p class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">01. {{ $detailStep01 }}</p>
                                                                <p class="text-sm font-black text-white">{{ $gen->product_name }}</p>
                                                            </div>

                                                            <div>
                                                                <p class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-2">02. {{ $detailStep02 }}</p>
                                                                @if($detailProductImageUrl)
                                                                    <button type="button" @click="showProductReferenceImage = true"
                                                                       class="inline-block group rounded-xl overflow-hidden border border-white/10 bg-black/50 hover:border-blue-500/40 transition-all max-w-[200px] text-left">
                                                                        <img src="{{ $detailProductImageUrl }}" alt="Product reference for {{ $gen->product_name }}"
                                                                             class="w-full h-auto max-h-40 object-contain p-2 group-hover:scale-[1.02] transition-transform">
                                                                        <p class="px-2 py-1.5 text-[8px] font-black uppercase tracking-widest text-gray-500 border-t border-white/5 group-hover:text-blue-400 text-center">View full size</p>
                                                                    </button>
                                                                @else
                                                                    <p class="text-xs font-bold text-gray-500 italic">No reference image saved</p>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <p class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">03. {{ $detailStep03 }}</p>
                                                            <p class="text-xs font-bold text-gray-300 italic leading-relaxed">{{ $gen->marketing_angle }}</p>
                                                        </div>

                                                        <div>
                                                            <p class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">04. {{ $detailStep04 }}</p>
                                                            <p class="text-xs font-bold text-gray-300 leading-relaxed bg-white/[0.03] border border-white/10 rounded-lg px-3 py-2.5">{{ $gen->visual_prop }}</p>
                                                        </div>

                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                                            <div>
                                                                <p class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">05. {{ $detailStep05 }}</p>
                                                                <p class="text-xs font-bold text-gray-300 leading-relaxed">{{ $gen->atmosphere }}</p>
                                                            </div>
                                                            <div>
                                                                <p class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">06. {{ $detailStep06 }}</p>
                                                                <p class="text-xs font-bold text-gray-300 leading-relaxed">{{ $gen->camera_motion }}</p>
                                                            </div>
                                                            <div>
                                                                <p class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">07. {{ $detailStep07 }}</p>
                                                                <p class="text-xs font-bold text-gray-300 leading-relaxed">{{ $gen->composition }}</p>
                                                            </div>
                                                            <div>
                                                                <p class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1">08. {{ $detailStep08 }}</p>
                                                                <p class="text-xs font-bold text-gray-300 leading-relaxed">{{ $gen->lighting_style }}</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="px-6 sm:px-8 py-5 border-t border-white/5 bg-white/[0.01] flex justify-end">
                                                        <button @click="closeModal()"
                                                            class="px-5 py-2.5 bg-gray-800 text-white rounded text-[10px] font-black uppercase tracking-widest hover:bg-gray-700 transition-colors">Close View</button>
                                                    </div>

                                                    {{-- Product reference lightbox --}}
                                                    <div x-show="showProductReferenceImage && productReferenceImageUrl" x-cloak
                                                         class="fixed inset-0 z-[1000] flex flex-col items-center justify-center p-4 sm:p-8 bg-black/95 backdrop-blur-md"
                                                         @keydown.escape.window="showProductReferenceImage = false"
                                                         @click.self="showProductReferenceImage = false">
                                                        <button type="button" @click="showProductReferenceImage = false"
                                                            class="absolute top-4 right-4 sm:top-6 sm:right-6 text-white text-[10px] font-black uppercase tracking-[0.2em] bg-white/10 hover:bg-white/20 px-4 py-2 rounded-full transition-all">
                                                            Close ✕
                                                        </button>
                                                        <img :src="productReferenceImageUrl" alt="Product reference"
                                                             class="w-full max-w-4xl max-h-[80vh] object-contain rounded-xl border border-white/10 shadow-2xl bg-black"
                                                             @click.stop>
                                                        <p class="mt-4 text-[9px] font-black uppercase tracking-widest text-gray-500">Product Reference · {{ $gen->product_name }}</p>
                                                    </div>
                                                </div>



                                                <div x-show="['image','video','audio'].includes(openModal)"
                                                    class="cgi-prompt-modal bg-[#0a0a0a] border border-white/10 w-[min(96vw,1400px)] max-w-none rounded-xl shadow-2xl overflow-visible animate-in fade-in zoom-in duration-200">

                                                    <div
                                                        class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02] rounded-t-xl">

                                                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]"
                                                            x-text="openModal.toUpperCase() + ' DIRECTIVE DEFINITION'"></h3>

                                                        <button @click="closeModal()"
                                                            class="text-gray-500 hover:text-white text-lg">✕</button>
                                                    </div>


                                                    <div class="p-5 sm:p-7 overflow-visible">
                                                        <div x-show="!isEditing"
                                                            class="bg-black p-5 sm:p-6 rounded-xl border border-white/5 font-mono text-[13px] text-gray-200 whitespace-pre-wrap break-words leading-[1.65] shadow-inner overflow-visible"
                                                            x-text="openModal==='image' ? liveImagePrompt : (openModal==='video' ? liveVideoPrompt : liveAudioPrompt)">
                                                        </div>
                                                        <div x-show="isEditing" class="overflow-visible">

                                                            <template x-if="openModal==='image'"><textarea
                                                                    x-model="inputImage"
                                                                    x-ref="promptEditImage"
                                                                    rows="1"
                                                                    @input="window.cgiGrowField($refs.promptEditImage)"
                                                                    x-init="$nextTick(() => window.cgiGrowField($refs.promptEditImage))"
                                                                    class="w-full min-h-[8rem] bg-black border border-white/10 rounded-xl p-5 text-white font-mono text-[13px] focus:ring-1 focus:ring-blue-500 outline-none transition-all resize-none overflow-visible whitespace-pre-wrap break-words leading-[1.65]"></textarea></template>

                                                            <template x-if="openModal==='video'"><textarea
                                                                    x-model="inputVideo"
                                                                    x-ref="promptEditVideo"
                                                                    rows="1"
                                                                    @input="window.cgiGrowField($refs.promptEditVideo)"
                                                                    x-init="$nextTick(() => window.cgiGrowField($refs.promptEditVideo))"
                                                                    class="w-full min-h-[8rem] bg-black border border-white/10 rounded-xl p-5 text-white font-mono text-[13px] focus:ring-1 focus:ring-blue-500 outline-none transition-all resize-none overflow-visible whitespace-pre-wrap break-words leading-[1.65]"></textarea></template>

                                                            <template x-if="openModal==='audio'"><textarea
                                                                    x-model="inputAudio"
                                                                    x-ref="promptEditAudio"
                                                                    rows="1"
                                                                    @input="window.cgiGrowField($refs.promptEditAudio)"
                                                                    x-init="$nextTick(() => window.cgiGrowField($refs.promptEditAudio))"
                                                                    class="w-full min-h-[8rem] bg-black border border-white/10 rounded-xl p-5 text-white font-mono text-[13px] focus:ring-1 focus:ring-blue-500 outline-none transition-all resize-none overflow-visible whitespace-pre-wrap break-words leading-[1.65]"></textarea></template>


                                                        </div>
                                                    </div>


                                                    <div
                                                        class="px-6 sm:px-8 py-4 border-t border-white/5 flex justify-end gap-3 bg-white/[0.01] rounded-b-xl">
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


                                                <div x-show="['preview', 'videoPreview', 'brandedPreview', 'brandedVideoPreview', 'footerPreview', 'mergedPreview', 'mergedVideoPreview'].includes(openModal)"
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

                                                        <template x-if="openModal==='footerPreview'">
                                                            <img :src="footerImageUrl"
                                                                class="w-full max-h-[80vh] object-contain">
                                                        </template>

                                                        <template x-if="openModal==='mergedPreview'">
                                                            <img :src="mergedImageUrl"
                                                                class="w-full max-h-[80vh] object-contain">
                                                        </template>

                                                        <template x-if="openModal==='mergedVideoPreview'">
                                                            <video :src="mergedVideoUrl"
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
                                                        <template x-if="requiresApproval">
                                                            <p class="text-[9px] text-amber-400/90 font-bold uppercase tracking-widest text-center -mb-1">Approver sign-off required for merged picture only</p>
                                                        </template>

                                                        <button
                                                            @click="openCreatePost('image', brandedImageUrl, true)"
                                                            :disabled="!brandedImageUrl"
                                                            class="w-full py-4 bg-blue-600/10 border border-blue-500/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-blue-600/10 disabled:hover:text-blue-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                            With Logo
                                                        </button>

                                                        <button
                                                            @click="openCreatePost('image', imageUrl, false)"
                                                            :disabled="!imageUrl"
                                                            class="w-full py-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-emerald-500/10 disabled:hover:text-emerald-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                            Without Logo
                                                        </button>

                                                        <button
                                                            @click="if(requiresApproval && mergedImageStatus !== 'approved') { $dispatch('notify', { message: mergedImageStatus === 'rejected' ? 'Rejected by approver: ' + (mergedImageNote || 'see note') : 'Awaiting client approval before posting.', type: 'error' }); return; } openCreatePost('image', mergedImageUrl, false)"
                                                            :disabled="!mergedImageUrl || (requiresApproval && mergedImageStatus !== 'approved')"
                                                            class="w-full py-4 bg-orange-500/10 border border-orange-500/20 text-orange-400 hover:bg-orange-600 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-orange-500/10 disabled:hover:text-orange-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                            <span x-text="(requiresApproval && mergedImageStatus !== 'approved') ? (mergedImageStatus === 'rejected' ? 'Merged — Rejected' : 'Merged — Needs Approval') : 'Merged Template'"></span>
                                                        </button>
                                                        <template x-if="requiresApproval && mergedImageUrl && mergedImageStatus !== 'approved'">
                                                            <p class="text-[8px] font-bold uppercase tracking-widest text-center"
                                                               :class="mergedImageStatus === 'rejected' ? 'text-red-400' : 'text-amber-400'"
                                                               x-text="mergedImageStatus === 'rejected' ? ('Rejected: ' + (mergedImageNote || 'see approver note')) : 'Locked until your approver signs off'"></p>
                                                        </template>
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
                                                        <template x-if="requiresApproval">
                                                            <p class="text-[9px] text-amber-400/90 font-bold uppercase tracking-widest text-center -mb-1">Approver sign-off required for merged video only</p>
                                                        </template>

                                                        <button
                                                            @click="openCreatePost('video', brandedVideoUrl, true)"
                                                            :disabled="!brandedVideoUrl"
                                                            class="w-full py-4 bg-pink-500/10 border border-pink-500/20 text-pink-400 hover:bg-pink-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-pink-500/10 disabled:hover:text-pink-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                            With Logo
                                                        </button>

                                                        <button
                                                            @click="openCreatePost('video', videoUrl, false)"
                                                            :disabled="!videoUrl"
                                                            class="w-full py-4 bg-purple-500/10 border border-purple-500/20 text-purple-400 hover:bg-purple-500 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-purple-500/10 disabled:hover:text-purple-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                            Without Logo
                                                        </button>

                                                        <button
                                                            @click="if(requiresApproval && mergedVideoStatus !== 'approved') { $dispatch('notify', { message: mergedVideoStatus === 'rejected' ? 'Rejected by approver: ' + (mergedVideoNote || 'see note') : 'Awaiting client approval before posting.', type: 'error' }); return; } openCreatePost('video', mergedVideoUrl, false)"
                                                            :disabled="!mergedVideoUrl || (requiresApproval && mergedVideoStatus !== 'approved')"
                                                            class="w-full py-4 bg-orange-500/10 border border-orange-500/20 text-orange-400 hover:bg-orange-600 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-orange-500/10 disabled:hover:text-orange-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                            <span x-text="(requiresApproval && mergedVideoStatus !== 'approved') ? (mergedVideoStatus === 'rejected' ? 'Merged Vid — Rejected' : 'Merged Vid — Needs Approval') : 'Merged Template (Video)'"></span>
                                                        </button>
                                                        <template x-if="requiresApproval && mergedVideoUrl && mergedVideoStatus !== 'approved'">
                                                            <p class="text-[8px] font-bold uppercase tracking-widest text-center"
                                                               :class="mergedVideoStatus === 'rejected' ? 'text-red-400' : 'text-amber-400'"
                                                               x-text="mergedVideoStatus === 'rejected' ? ('Rejected: ' + (mergedVideoNote || 'see approver note')) : 'Locked until your approver signs off'"></p>
                                                        </template>

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
                                                    x-effect="openModal === 'createPost' && resizePostCaption()"
                                                    class="relative bg-[#0a0a0a] border border-white/10 w-full max-w-lg rounded-xl shadow-2xl flex flex-col max-h-[min(90vh,920px)] animate-in fade-in zoom-in duration-200"
                                                    :class="captionLangPickerOpen ? 'overflow-visible' : 'overflow-hidden'"
                                                    @click.away="captionLangPickerOpen ? (captionLangPickerOpen = false) : closeModal()">

                                                    <div class="flex flex-col flex-1 min-h-0 transition-all duration-300"
                                                         :class="captionLangPickerOpen ? 'blur-md brightness-[0.65] pointer-events-none select-none' : ''">

                                                    <div
                                                        class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02] shrink-0">
                                                        <h3
                                                            class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">
                                                            Create Post</h3>
                                                        <button @click="closeModal()"
                                                            class="text-gray-500 hover:text-white text-lg">✕</button>
                                                    </div>

                                                    <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain bg-black/40 custom-scrollbar relative">
                                                        <div x-show="isGeneratingCaption"
                                                             x-transition.opacity
                                                             class="absolute inset-0 z-20 flex items-center justify-center bg-black/60 backdrop-blur-sm pointer-events-none"
                                                             x-cloak>
                                                            <div class="flex flex-col items-center gap-3 px-6 py-5 rounded-xl border border-indigo-500/30 bg-[#0a0a0a]/95">
                                                                <svg class="animate-spin h-7 w-7 text-indigo-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                                <span class="text-[9px] font-black uppercase tracking-widest text-indigo-300">Synthesizing caption…</span>
                                                            </div>
                                                        </div>

                                                        <div class="p-6 flex flex-col gap-4" :class="isGeneratingCaption ? 'opacity-40 pointer-events-none' : ''">

                                                            {{-- Unified Header and Input Area --}}
                                                            <div class="flex flex-col gap-3 shrink-0">
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

                                                                <div class="flex justify-between items-center gap-2">
                                                                    <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Caption</label>
                                                                    <button type="button"
                                                                        @click="openCaptionLanguagePicker()"
                                                                        :disabled="isGeneratingCaption || !postUrl"
                                                                        class="text-[9px] font-black uppercase tracking-widest border border-white/10 text-indigo-400 hover:bg-white/5 px-2.5 py-1 rounded transition-all disabled:opacity-40 flex items-center gap-1">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                                        <span x-text="isGeneratingCaption ? 'Writing…' : 'AI Auto-Caption'"></span>
                                                                    </button>
                                                                </div>

                                                                <textarea
                                                                    x-ref="postCaptionInput"
                                                                    x-model="postCaption"
                                                                    @input="resizePostCaption(); captionFromAI = false"
                                                                    x-init="resizePostCaption()"
                                                                    rows="3"
                                                                    :disabled="isGeneratingCaption"
                                                                    class="w-full bg-white/[0.03] border border-white/10 rounded-lg text-gray-100 text-[14px] outline-none resize-y placeholder-gray-500 focus:ring-1 focus:ring-blue-500/40 focus:border-blue-500/40 p-3 leading-relaxed min-h-[88px] max-h-[280px] overflow-y-auto disabled:opacity-60"
                                                                    placeholder="Click AI Auto-Caption or write your own…"></textarea>
                                                                <p class="text-[8px] text-indigo-400/80 font-bold uppercase tracking-widest"
                                                                   x-show="captionFromAI && postCaption">
                                                                    AI caption — edit before publishing
                                                                </p>
                                                                <p class="text-[8px] text-gray-600 font-bold uppercase tracking-widest"
                                                                   x-show="!captionFromAI && !postCaption">
                                                                    Use AI Auto-Caption or type a caption to publish
                                                                </p>
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

                                                            <template x-if="activeApproverNote()">
                                                                <div class="px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                                                                    <p class="text-[8px] font-black text-emerald-400 uppercase tracking-widest mb-1">Approver note</p>
                                                                    <p class="text-[11px] text-emerald-100/90 italic leading-relaxed" x-text="activeApproverNote()"></p>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-between items-center shrink-0">
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
                                                        <button @click="publishPostToBackend()" :disabled="isPublishing || isGeneratingCaption || !postCaption || !canPublishNow()"
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

                                                    </div>{{-- end blurred create-post content --}}

                                                    {{-- Language picker overlay (inside modal — avoids nested x-teleport on deploy) --}}
                                                    <div x-show="captionLangPickerOpen"
                                                         x-transition:enter="transition ease-out duration-200"
                                                         x-transition:enter-start="opacity-0"
                                                         x-transition:enter-end="opacity-100"
                                                         x-transition:leave="transition ease-in duration-150"
                                                         x-transition:leave-start="opacity-100"
                                                         x-transition:leave-end="opacity-0"
                                                         @click.self="captionLangPickerOpen = false"
                                                         @keydown.escape.window="captionLangPickerOpen = false"
                                                         class="absolute inset-0 z-[1001] flex items-center justify-center p-4 sm:p-6 bg-black/70 backdrop-blur-xl"
                                                         x-cloak>
                                                        <div class="w-full max-w-md max-h-[min(640px,calc(100vh-3rem))] flex flex-col rounded-2xl border border-blue-500/30 bg-gradient-to-b from-[#12121a] via-[#0d0d12] to-[#0a0a0a] shadow-[0_28px_90px_-16px_rgba(0,0,0,0.85)] overflow-hidden"
                                                             @click.stop>
                                                            <div class="px-6 pt-6 pb-5 border-b border-white/[0.06] shrink-0">
                                                                <div class="flex items-start justify-between gap-4">
                                                                    <div class="flex items-center gap-3 min-w-0">
                                                                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-500/20 to-indigo-500/20 border border-blue-500/30 flex items-center justify-center shrink-0">
                                                                            <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
                                                                        </div>
                                                                        <div class="min-w-0">
                                                                            <p class="text-[10px] font-black text-blue-400/90 uppercase tracking-[0.2em]">AI Caption</p>
                                                                            <h4 class="text-base font-bold text-white tracking-tight mt-0.5">Select post language</h4>
                                                                            <p class="text-[11px] text-gray-500 mt-1 leading-relaxed">Your caption will be written in the language you choose.</p>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button" @click="captionLangPickerOpen = false"
                                                                            class="shrink-0 w-8 h-8 rounded-lg border border-white/10 text-gray-500 hover:text-white hover:bg-white/5 flex items-center justify-center transition-colors"
                                                                            aria-label="Close">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="p-5 sm:p-6 overflow-y-auto custom-scrollbar flex-1 min-h-0">
                                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                                    <template x-for="opt in captionLanguages" :key="opt.value">
                                                                        <button type="button"
                                                                            @click="fetchAutoCaption(opt.value)"
                                                                            class="group flex items-center justify-between gap-3 w-full px-4 py-3.5 rounded-xl border border-white/[0.08] bg-white/[0.02] hover:bg-blue-500/10 hover:border-blue-500/35 focus:outline-none focus:ring-2 focus:ring-blue-500/30 transition-all text-left">
                                                                            <span class="text-[13px] font-semibold text-gray-100 group-hover:text-white" x-text="opt.label"></span>
                                                                            <span class="text-[12px] text-gray-500 group-hover:text-blue-200/90 font-medium" x-text="opt.native || ''"></span>
                                                                        </button>
                                                                    </template>
                                                                </div>
                                                            </div>

                                                            <div class="px-6 py-4 border-t border-white/[0.06] bg-black/30 flex justify-end shrink-0">
                                                                <button type="button" @click="captionLangPickerOpen = false"
                                                                        class="text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-gray-300 px-4 py-2 rounded-lg hover:bg-white/5 transition-colors">
                                                                    Cancel
                                                                </button>
                                                            </div>
                                                        </div>
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

                @if($generations->hasPages())
                    <div class="px-4 sm:px-6 py-5 border-t border-white/5">
                        {{ $generations->links('vendor.pagination.gallery') }}
                    </div>
                @elseif($generations->total() > 0)
                    <div class="px-4 sm:px-6 py-4 border-t border-white/5">
                        <p class="text-center text-[9px] font-bold text-gray-600 uppercase tracking-widest">
                            Showing {{ $generations->total() }} {{ $generations->total() === 1 ? 'directive' : 'directives' }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        @if($showApprovalTab)
            <div x-show="studioTab === 'approval'" x-cloak class="border-t border-white/5">
                @include('partials.approval-history-studio', ['approvalHistory' => $approvalHistory, 'accent' => 'blue'])
            </div>
        @endif

        {{-- BRANDING UPLOAD MODAL (library + placement, like Occasion Studio) --}}
        <template x-teleport="body">
            <div x-show="brandingModal"
                x-data="{
                    logoPreview: null,
                    logoPlacement: 'bottom_right',
                    selectedLogoId: null,
                    selectedLogoName: '',
                    logoDropdownOpen: false,
                    logoSearch: '',
                    resetBrandingLogo() {
                        this.logoPreview = null;
                        this.selectedLogoId = null;
                        this.selectedLogoName = '';
                        this.logoSearch = '';
                        if (this.$refs.brandingLogoInput) this.$refs.brandingLogoInput.value = '';
                    },
                    selectLibraryLogo(id, url, name) {
                        this.selectedLogoId = id;
                        this.selectedLogoName = name;
                        this.logoPreview = url;
                        this.logoDropdownOpen = false;
                        if (this.$refs.brandingLogoInput) this.$refs.brandingLogoInput.value = '';
                    },
                    handleLogoUpload(event) {
                        const file = event.target.files[0];
                        if (!file) return;
                        this.selectedLogoId = null;
                        this.selectedLogoName = file.name;
                        this.logoPreview = URL.createObjectURL(file);
                    },
                    async submitBranding() {
                        if (!this.logoPreview) {
                            $dispatch('notify', { message: 'Please select or upload a logo.', type: 'error' });
                            return;
                        }
                        isUploadingLogo = true;
                        const formData = new FormData();
                        formData.append('id', activeGenId);
                        formData.append('placement', this.logoPlacement);
                        formData.append('_token', '{{ csrf_token() }}');
                        if (this.selectedLogoId) {
                            formData.append('logo_id', this.selectedLogoId);
                        } else if (this.$refs.brandingLogoInput?.files?.[0]) {
                            formData.append('logo', this.$refs.brandingLogoInput.files[0]);
                        } else {
                            isUploadingLogo = false;
                            $dispatch('notify', { message: 'Please select or upload a logo.', type: 'error' });
                            return;
                        }
                        const endpoint = brandingTarget === 'image' ? '/cgi/apply-branding-image' : '/cgi/apply-branding-video';
                        try {
                            const res = await fetch(endpoint, {
                                method: 'POST',
                                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                body: formData,
                            });
                            const data = await res.json();
                            if (data.success) {
                                $dispatch('close-branding');
                                if (brandingTarget === 'image') {
                                    sessionStorage.setItem('branding_img_' + activeGenId, 'true');
                                    $dispatch('start-branding-image', activeGenId);
                                } else {
                                    sessionStorage.setItem('branding_vid_' + activeGenId, 'true');
                                    $dispatch('start-branding-video', activeGenId);
                                }
                                $dispatch('notify', { message: 'Branding pipeline started!', type: 'success' });
                            } else {
                                $dispatch('notify', { message: data.message || 'System error', type: 'error' });
                            }
                        } catch (e) {
                            $dispatch('notify', { message: 'Network failure', type: 'error' });
                        } finally {
                            isUploadingLogo = false;
                        }
                    }
                }"
                @close-branding.window="resetBrandingLogo(); logoPlacement = 'bottom_right'; brandingModal = false"
                class="fixed inset-0 z-[2100] flex items-center justify-center p-4 sm:p-6 bg-black/90 backdrop-blur-xl"
                x-cloak>
                <div class="bg-[#0a0a0a] border border-white/10 w-full max-w-md rounded-xl shadow-2xl overflow-hidden"
                    @click.away="$dispatch('close-branding')">
                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
                        <div>
                            <h2 class="text-[10px] font-black text-blue-400 uppercase tracking-[0.3em]">Apply Branding Logo</h2>
                            <p class="text-gray-500 text-[9px] uppercase font-bold mt-1"
                               x-text="brandingTarget === 'image' ? 'Image pipeline · 1 image brand credit' : 'Video pipeline · 1 video brand credit'"></p>
                        </div>
                        <button type="button" @click="$dispatch('close-branding')" class="text-gray-500 hover:text-white text-lg">✕</button>
                    </div>

                    <div class="p-6 space-y-5 bg-black/40">
                        @if(($userLogos ?? collect())->isNotEmpty())
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Saved Logos</label>
                                <div class="relative" @click.away="logoDropdownOpen = false">
                                    <button type="button" @click="logoDropdownOpen = !logoDropdownOpen"
                                            class="w-full bg-black border border-white/10 rounded-lg p-3 flex items-center gap-3 hover:border-blue-500/40 transition-all text-left">
                                        <template x-if="logoPreview && selectedLogoId">
                                            <img :src="logoPreview" alt="" class="w-8 h-8 object-contain rounded border border-white/10 bg-white/5 shrink-0">
                                        </template>
                                        <template x-if="!selectedLogoId">
                                            <span class="w-8 h-8 rounded border border-dashed border-white/20 flex items-center justify-center text-gray-600 text-[10px] shrink-0">+</span>
                                        </template>
                                        <span class="flex-1 text-xs font-bold truncate"
                                              :class="selectedLogoName ? 'text-white' : 'text-gray-500'"
                                              x-text="selectedLogoName || 'Select from your logo library...'"></span>
                                        <svg class="w-4 h-4 text-gray-500 shrink-0 transition-transform" :class="logoDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div x-show="logoDropdownOpen" x-cloak
                                         class="absolute left-0 right-0 top-full mt-1 bg-[#111] border border-white/10 rounded-lg shadow-2xl z-50 overflow-hidden">
                                        <div class="p-2 border-b border-white/5">
                                            <input type="text" x-model="logoSearch" placeholder="Search logos..."
                                                   class="w-full bg-black border border-white/10 rounded-md px-3 py-2 text-white text-[10px] outline-none focus:border-blue-500">
                                        </div>
                                        <div class="max-h-40 overflow-y-auto custom-scrollbar p-1">
                                            @foreach($userLogos ?? [] as $logo)
                                                <button type="button"
                                                        x-show="@js(strtolower($logo->name)).includes(logoSearch.toLowerCase())"
                                                        @click="selectLibraryLogo({{ $logo->id }}, @js($logo->url), @js($logo->name))"
                                                        class="w-full flex items-center gap-2 p-2 rounded hover:bg-blue-500/20 transition-colors text-left">
                                                    <img src="{{ $logo->url }}" alt="" class="w-8 h-8 object-contain rounded border border-white/10 bg-white/5 shrink-0">
                                                    <span class="text-[10px] font-bold text-gray-300 truncate">{{ $logo->name }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-px flex-1 bg-white/5"></div>
                                <span class="text-[8px] font-black text-gray-600 uppercase tracking-widest">Or upload new</span>
                                <div class="h-px flex-1 bg-white/5"></div>
                            </div>
                        @endif

                        <div>
                            <label class="flex flex-col items-center justify-center w-full h-40 border-2 border-white/10 border-dashed rounded-lg cursor-pointer bg-black hover:bg-white/[0.02] transition-all overflow-hidden"
                                   :class="logoPreview && !selectedLogoId ? 'border-blue-500/50' : 'hover:border-blue-500/50'">
                                <div x-show="!logoPreview" class="flex flex-col items-center justify-center pt-5 pb-6 pointer-events-none">
                                    <svg class="w-8 h-8 mb-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    <p class="text-[10px] text-gray-400"><span class="font-bold text-blue-400">Click to upload</span> PNG / JPG / SVG</p>
                                </div>
                                <div x-show="logoPreview && !selectedLogoId" class="w-full h-full flex items-center justify-center p-3 relative group pointer-events-none">
                                    <img :src="logoPreview" alt="" class="max-w-full max-h-full object-contain drop-shadow-2xl z-10">
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-20">
                                        <span class="text-[10px] font-black text-white uppercase tracking-widest bg-white/10 px-3 py-1.5 rounded backdrop-blur-sm border border-white/20">Change Logo</span>
                                    </div>
                                </div>
                                <div x-show="logoPreview && selectedLogoId" class="w-full h-full flex flex-col items-center justify-center p-3 pointer-events-none">
                                    <img :src="logoPreview" alt="" class="max-h-24 object-contain drop-shadow-2xl mb-2">
                                    <span class="text-[9px] font-black text-emerald-400 uppercase tracking-widest">Library logo selected</span>
                                </div>
                                <input type="file" x-ref="brandingLogoInput" @change="handleLogoUpload($event)" class="hidden" accept="image/png,image/jpeg,image/jpg,image/svg+xml">
                            </label>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Logo Placement</label>
                            <select x-model="logoPlacement" class="w-full bg-black border border-white/10 rounded-lg p-3 text-white text-xs focus:border-blue-500 outline-none font-bold cursor-pointer">
                                <option value="bottom_right">Bottom Right</option>
                                <option value="bottom_left">Bottom Left</option>
                                <option value="top_right">Top Right</option>
                                <option value="top_left">Top Left</option>
                                <option value="center">Center Watermark</option>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-end gap-3">
                        <button type="button" @click="$dispatch('close-branding')" class="px-4 py-2 bg-transparent text-gray-400 hover:text-white rounded text-[10px] font-black uppercase tracking-widest transition-colors">Cancel</button>
                        <button type="button" @click="submitBranding()" :disabled="!logoPreview || isUploadingLogo"
                                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded text-[10px] font-black uppercase tracking-widest shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-text="isUploadingLogo ? 'Processing...' : 'Generate Branding'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- ADD FOOTER MODAL --}}
        <template x-teleport="body">
            <div x-show="footerModal" x-cloak class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm">
                <div class="bg-[#0a0a0a] border border-white/10 w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden transform transition-all"
                     x-show="footerModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    
                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                        <h3 class="text-[11px] font-black text-emerald-400 uppercase tracking-[0.3em] flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            Add Footer Branding
                        </h3>
                        <button @click="footerModal = false" class="text-gray-500 hover:text-white transition-colors">✕</button>
                    </div>

                    <div class="p-8 space-y-8 bg-black/40">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            {{-- Left Logo Slot --}}
                            <div class="space-y-3">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Footer Left Logo</label>
                                <label class="flex flex-col items-center justify-center w-full h-48 border-2 border-white/5 border-dashed rounded-xl cursor-pointer bg-[#111] hover:bg-white/[0.02] transition-all overflow-hidden group relative" :class="footerLeftPreview ? 'border-emerald-500/50' : 'hover:border-emerald-500/50'">
                                    <div x-show="!footerLeftPreview" class="flex flex-col items-center justify-center">
                                        <div class="p-3 bg-gray-800/50 rounded-full mb-2 group-hover:bg-emerald-900/20 transition-colors">
                                            <svg class="w-6 h-6 text-gray-500 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </div>
                                        <p class="text-[9px] text-gray-500 font-black uppercase">Upload Left Logo</p>
                                    </div>
                                    <img x-show="footerLeftPreview" :src="footerLeftPreview" class="w-full h-full object-contain p-4 transition-transform group-hover:scale-105">
                                    <input type="file" class="hidden" @change="handleFooterLogo('left', $event)" accept="image/*">
                                </label>
                            </div>

                            {{-- Right Logo Slot --}}
                            <div class="space-y-3">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Footer Right Logo</label>
                                <label class="flex flex-col items-center justify-center w-full h-48 border-2 border-white/5 border-dashed rounded-xl cursor-pointer bg-[#111] hover:bg-white/[0.02] transition-all overflow-hidden group relative" :class="footerRightPreview ? 'border-emerald-500/50' : 'hover:border-emerald-500/50'">
                                    <div x-show="!footerRightPreview" class="flex flex-col items-center justify-center">
                                        <div class="p-3 bg-gray-800/50 rounded-full mb-2 group-hover:bg-emerald-900/20 transition-colors">
                                            <svg class="w-6 h-6 text-gray-500 group-hover:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </div>
                                        <p class="text-[9px] text-gray-500 font-black uppercase">Upload Right Logo</p>
                                    </div>
                                    <img x-show="footerRightPreview" :src="footerRightPreview" class="w-full h-full object-contain p-4 transition-transform group-hover:scale-105">
                                    <input type="file" class="hidden" @change="handleFooterLogo('right', $event)" accept="image/*">
                                </label>
                            </div>
                        </div>

                        <div class="bg-emerald-500/5 border border-emerald-500/10 p-4 rounded-xl">
                            <p class="text-[10px] text-emerald-400/80 leading-relaxed text-center font-medium italic">
                                "This will generate a clean branded footer at the bottom of your asset. You can provide one or both logos."
                            </p>
                        </div>
                    </div>

                    <div class="px-8 py-5 border-t border-white/5 bg-white/[0.01] flex justify-end gap-3">
                        <button @click="footerModal = false" class="px-5 py-2 text-gray-400 hover:text-white rounded text-[10px] font-black uppercase tracking-widest transition-colors">Cancel</button>
                        <button @click="triggerAddFooter()" :disabled="isAddingFooter || (!footerLeftLogo && !footerRightLogo)" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2 disabled:opacity-50">
                            <span x-show="!isAddingFooter">Initialize Footer Pipeline</span>
                            <span x-show="isAddingFooter" class="flex items-center gap-2">
                                <svg class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        @include('partials.merge-template-modal')
    </div>

    <script>
        window.cgiGrowField = window.cgiGrowField || function (el) {
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = Math.max(el.scrollHeight, 160) + 'px';
        };
    </script>

    {{-- STUDIO DOCUMENTATION & STYLESHEET --}}
    <style>
        /* * CGI DIRECTIVE STUDIO - NEURAL ASSET PIPELINE v3.2
         * GLOBAL STYLESHEET & COMPONENT ARCHITECTURE
         * =========================================================
         * This section defines the structural aesthetic, transition 
         * animations, and dark-mode optimization parameters.
         */

        [x-cloak] {
            display: none !important;
        }

        .cgi-prompt-modal,
        .cgi-prompt-modal .overflow-visible {
            overflow: visible !important;
        }

        .cgi-prompt-modal textarea {
            overflow: hidden !important;
            scrollbar-width: none;
        }

        .cgi-prompt-modal textarea::-webkit-scrollbar {
            display: none;
        }

        body {
            background-color: #050505;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Custom Scrollbar Implementation */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: #050505;
        }

        ::-webkit-scrollbar-thumb {
            background: #1a1a1a;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #2a2a2a;
        }

        /* * NEURAL MODAL ANIMATIONS 
         * =========================================================
         * Smooth entry/exit transitions for all teleported UI blocks.
         */
        .animate-in {
            animation-duration: 0.2s;
            animation-fill-mode: both;
            animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
        }

        .fade-in {
            animation-name: fadeIn;
        }

        .zoom-in {
            animation-name: zoomIn;
        }

        .slide-in-from-bottom-4 {
            animation-name: slideInBottom;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes zoomIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @keyframes slideInBottom {
            from { transform: translateY(1rem); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* * FORM INPUT FOCUS STATES
         * =========================================================
         */
        textarea:focus, input[type="text"]:focus {
            box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.5);
            border-color: rgba(59, 130, 246, 0.5) !important;
        }

        /* * BUTTON PULSE FX
         * =========================================================
         */
        .btn-pulse {
            position: relative;
        }
        
        .btn-pulse::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border-radius: inherit;
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            animation: pulse-ring 2s infinite cubic-bezier(0.66, 0, 0, 1);
        }

        @keyframes pulse-ring {
            to {
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }
        }
         
         .pipeline-glow {
             filter: drop-shadow(0 0 8px rgba(37,99,235,0.4));
         }
         
         .glass-panel {
             background: rgba(10, 10, 10, 0.7);
             backdrop-filter: blur(12px);
             -webkit-backdrop-filter: blur(12px);
             border: 1px solid rgba(255, 255, 255, 0.05);
         }
         
         .text-gradient {
             background: linear-gradient(to right, #60a5fa, #a78bfa);
             -webkit-background-clip: text;
             -webkit-text-fill-color: transparent;
         }
    </style>
</x-app-layout>