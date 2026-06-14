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
                <div class="flex-1 text-[10px] font-black uppercase tracking-widest leading-none" x-text="n.message"></div>
                <button @click="remove(n.id)" class="text-white/20 hover:text-white">✕</button>
            </div>
        </template>
    </div>

    {{-- Main Workspace --}}
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen"
        x-data="{
            mergeModal: false,
            activeOccasionId: null,
            activeImageUrl: '',
            templateFile: null,
            templatePreview: null,
            selectedTemplatePath: null,
            selectedTemplateId: null,
            isMerging: false,
            studioTab: 'directives',
            hasBrandingImageCredits: @json((isset($wallet->is_admin) && $wallet->is_admin) || (($wallet->branding_image_credits ?? 0) > 0)),
            @include('partials.merge-template-alpine', ['templateAssets' => $templateAssets ?? collect()])

            async triggerMergeTemplate() {
                if (!this.hasBrandingImageCredits) {
                    $dispatch('notify', { message: 'Insufficient Image Branding Credits.', type: 'error' });
                    return;
                }
                if (!this.selectedTemplateId) {
                    $dispatch('notify', { message: 'Select or upload a template first.', type: 'error' });
                    return;
                }

                this.isMerging = true;
                const formData = new FormData();
                formData.append('id', this.activeOccasionId);
                formData.append('template_asset_id', this.selectedTemplateId);
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch('{{ route('occasions.mergeTemplate') }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    });
                    const result = await response.json();

                    if (result.success) {
                        sessionStorage.setItem('merge_' + this.activeOccasionId, 'true');
                        $dispatch('start-merge-pipeline', this.activeOccasionId);
                        $dispatch('notify', { message: 'Merge Pipeline Started!', type: 'success' });
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
            }
        }">
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b border-white/5 bg-[#0a0a0a]">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-pink-500 rounded-full shadow-[0_0_10px_rgba(236,72,153,0.5)]"></span>
                    Occasion Studio
                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">Campaign Directives</p>
            </div>

            <div class="flex items-center gap-4">
                @if($wallet)
                    @php
                        $isAdminWallet = isset($wallet->is_admin) && $wallet->is_admin;
                        $remPrompt = $wallet->prompt_credits ?? 0;
                        $remImage = $wallet->image_credits ?? 0;
                        $remBranded = $wallet->branding_image_credits ?? 0;
                        $remSocial = $wallet->social_post_credits ?? 0;
                        $allowPrompt = $walletAllowances['prompt'] ?? $remPrompt;
                        $allowImage = $walletAllowances['image'] ?? $remImage;
                        $allowBranded = $walletAllowances['branding_image'] ?? $remBranded;
                        $allowSocial = $walletAllowances['social_post'] ?? $remSocial;
                    @endphp
                    @can('view_billing')
                    <a href="{{ route('billing.index') }}" class="flex items-center gap-3 bg-black border border-pink-500/20 hover:border-pink-500/40 px-3 py-1.5 rounded shadow-xl transition-colors" title="View full usage in My Subscription">
                    @else
                    <div class="flex items-center gap-3 bg-black border border-pink-500/20 px-3 py-1.5 rounded shadow-xl">
                    @endcan
                        <div class="text-center px-2 border-r border-white/10">
                            <p class="text-[7px] text-gray-500 font-bold uppercase">Prompts</p>
                            <p class="text-[10px] font-black text-white font-mono">
                                @if($isAdminWallet) ∞ @else {{ $remPrompt }}<span class="text-[8px] text-gray-600">/{{ $allowPrompt }}</span> @endif
                            </p>
                        </div>
                        <div class="text-center px-2 border-r border-white/10">
                            <p class="text-[7px] text-gray-500 font-bold uppercase">Images</p>
                            <p class="text-[10px] font-black text-pink-400 font-mono">
                                @if($isAdminWallet) ∞ @else {{ $remImage }}<span class="text-[8px] text-gray-600">/{{ $allowImage }}</span> @endif
                            </p>
                        </div>
                        <div class="text-center px-2 border-r border-white/10" title="Logo, merge &amp; footer use Branding Image credits">
                            <p class="text-[7px] text-gray-500 font-bold uppercase">Branded</p>
                            <p class="text-[10px] font-black text-blue-400 font-mono">
                                @if($isAdminWallet) ∞ @else {{ $remBranded }}<span class="text-[8px] text-gray-600">/{{ $allowBranded }}</span> @endif
                            </p>
                        </div>
                        <div class="text-center px-2">
                            <p class="text-[7px] text-gray-500 font-bold uppercase">Posts</p>
                            <p class="text-[10px] font-black text-indigo-400 font-mono">
                                @if($isAdminWallet) ∞ @else {{ $remSocial }}<span class="text-[8px] text-gray-600">/{{ $allowSocial }}</span> @endif
                            </p>
                        </div>
                    @can('view_billing')
                    </a>
                    @else
                    </div>
                    @endcan
                @endif
                <a href="{{ route('occasions.gallery') }}" class="flex items-center gap-2 px-4 py-2 bg-[#111] hover:bg-white/10 text-white border border-white/10 text-[9px] font-black rounded-md transition-all uppercase tracking-widest">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Gallery
                </a>
                @if(!empty($canStartPipeline))
                    <a href="{{ route('occasions.create') }}" class="flex items-center gap-2 px-4 py-2 bg-pink-600 hover:bg-pink-500 text-white text-[9px] font-black rounded-md transition-all uppercase tracking-widest shadow-lg shadow-pink-600/20">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                        New Campaign
                    </a>
                @else
                    <button type="button"
                        @click="$dispatch('notify', { message: @js($pipelineBlockMessage ?? 'Insufficient Prompt Credits.'), type: 'error' })"
                        class="flex items-center gap-2 px-4 py-2 bg-white/5 border border-white/10 text-gray-500 text-[9px] font-black rounded-md uppercase tracking-widest cursor-not-allowed opacity-60"
                        title="{{ $pipelineBlockMessage ?? 'Insufficient Prompt Credits.' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                        New Campaign
                    </button>
                @endif
            </div>
        </div>

        @php
            $showApprovalTab = ($requiresApproval ?? false) || (($approvalHistory['stats']['total'] ?? 0) > 0);
            $showPostHistoryTab = true;
            $showStudioTabs = $showPostHistoryTab || $showApprovalTab;
        @endphp
        @if($showStudioTabs)
            @include('partials.studio-tabs', [
                'accent' => 'pink',
                'approvalHistory' => $approvalHistory,
                'showPostHistoryTab' => $showPostHistoryTab,
                'showApprovalTab' => $showApprovalTab,
                'postHistoryStats' => $postHistoryStats,
            ])
        @endif

        <div x-show="!@json($showStudioTabs) || studioTab === 'directives'" class="p-4 sm:p-6">
            @if($hasPending)
                <div
                    x-data="{
                        pollMs: 5000,
                        timer: null,
                        start() {
                            if (this.timer) return;
                            this.timer = setInterval(() => window.location.reload(), this.pollMs);
                        },
                        stop() {
                            if (this.timer) clearInterval(this.timer);
                            this.timer = null;
                        }
                    }"
                    @occasion-pipeline-halt.window="stop(); sessionStorage.removeItem('occasion_pipeline_poll')"
                    x-init="start(); sessionStorage.removeItem('occasion_pipeline_poll');"
                    class="mb-4 px-4 py-3 rounded-lg border border-yellow-500/20 bg-yellow-500/5 flex items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse shadow-[0_0_8px_rgba(234,179,8,0.6)] shrink-0"></span>
                        <p class="text-[9px] font-black text-yellow-500 uppercase tracking-widest truncate">
                            Neural pipeline active — syncing campaign DNA & renders every 5 seconds…
                        </p>
                    </div>
                    <span class="text-[8px] text-yellow-500/70 font-bold uppercase tracking-widest shrink-0 hidden sm:inline">Auto-refresh on</span>
                </div>
            @else
                {{-- DNA ready / idle — clear create-form poll flag; no auto page reload --}}
                <div
                    x-data="{ stop() {} }"
                    x-init="sessionStorage.removeItem('occasion_pipeline_poll'); window.dispatchEvent(new CustomEvent('occasion-pipeline-halt'));"
                    class="hidden"
                    aria-hidden="true"
                ></div>
            @endif

            @php
                $hasSocialCredits = auth()->user()->role === 'admin'
                    || ($wallet && (
                        (isset($wallet->is_admin) && $wallet->is_admin)
                        || (($wallet->social_post_credits ?? 0) > 0)
                    ));
            @endphp

            @if(session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: { message: @json(session('success')), type: 'success' }
                        }));
                    });
                </script>
            @endif

            @if(session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        sessionStorage.removeItem('occasion_pipeline_poll');
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: { message: @json(session('error')), type: 'error' }
                        }));
                    });
                </script>
            @endif

            <div class="bg-[#0a0a0a] border border-white/5 rounded-xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white/[0.02] border-b border-white/5">
                            <tr class="text-[9px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                                <th class="px-4 sm:px-6 py-4">Occasion Config</th>
                                <th class="px-4 sm:px-6 py-4 text-center">Neural Prompts</th>
                                <th class="px-4 sm:px-6 py-4">Render Engine</th>
                                <th class="px-4 sm:px-6 py-4 text-right w-16">Control</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.03]">
                            @forelse($occasions as $occasion)
                                @php
                                    $safeImageUrl = '';
                                    if (!empty($occasion->image_url)) {
                                        $safeImageUrl = str_starts_with($occasion->image_url, 'http') ? $occasion->image_url : asset('storage/' . $occasion->image_url);
                                    }
                                    $safeBrandedUrl = '';
                                    if (!empty($occasion->branded_image_url)) {
                                        $safeBrandedUrl = str_starts_with($occasion->branded_image_url, 'http') ? $occasion->branded_image_url : asset('storage/' . $occasion->branded_image_url);
                                    }
                                    $safeMergedUrl = '';
                                    if (!empty($occasion->merged_image_url)) {
                                        $safeMergedUrl = str_starts_with($occasion->merged_image_url, 'http') ? $occasion->merged_image_url : asset('storage/' . $occasion->merged_image_url);
                                    }

                                    $reqAppr = $requiresApproval ?? false;
                                    $mergedMeta = ($reqAppr && $occasion->merged_image_url)
                                        ? \App\Http\Controllers\ApprovalController::mergedApprovalMeta('occasion', $occasion->id, 'image')
                                        : ['status' => '', 'comment' => ''];
                                @endphp

                                <tr x-data="{
                                    status: @js($occasion->status),
                                    promptError: @js($occasion->prompt_error_message),
                                    isRetryingPrompt: false,
                                    promptPollTimer: null,
                                    promptPollToken: 0,
                                    imageStatus: @js(!empty($occasion->image_url) ? 'completed' : ($occasion->image_status ?? 'pending')),
                                    imagePollTimer: null,
                                    imagePollToken: 0,
                                    
                                    openModal: null, 
                                    isEditing: false, 
                                    isSaving: false, 
                                    isTriggering: false, 
                                    logoPlacement: 'bottom_right',
                                    logoPreviewUrl: null, 
                                    
                                    liveImagePrompt: @js($occasion->image_prompt),
                                    inputImage: @js($occasion->image_prompt), 

                                    imageUrl: @js($safeImageUrl),
                                    brandedUrl: @js($safeBrandedUrl),
                                    mergedImageUrl: @js($safeMergedUrl),
                                    mergeStatus: @js($occasion->merged_image_url ? 'completed' : ($occasion->merge_status ?? 'pending')),

                                    isAddingLogo: sessionStorage.getItem('branding_{{ $occasion->id }}') === 'true',
                                    isMergingLocal: ('{{ $occasion->merged_image_url ?? '' }}' !== '' || '{{ $occasion->merge_status }}' === 'completed')
                                        ? false
                                        : (sessionStorage.getItem('merge_{{ $occasion->id }}') === 'true'),

                                    hasImageCredits: {{ (isset($wallet->is_admin) && $wallet->is_admin) || ($wallet->image_credits > 0) ? 'true' : 'false' }},
                                    hasPromptCredits: @js($hasPromptCredits ?? false),
                                    hasBrandingImageCredits: {{ (isset($wallet->is_admin) && $wallet->is_admin) || (($wallet->branding_image_credits ?? 0) > 0) ? 'true' : 'false' }},
                                    hasSocialCredits: @js($hasSocialCredits),

                                    // ==========================================
                                    // SOCIAL POSTING (logo'd vs merged)
                                    // ==========================================
                                    postCaption: @js($occasion->custom_text ?? ''),
                                    postImageType: 'branded',
                                    postScheduledTime: '',
                                    isGeneratingCaption: false,
                                    isPublishing: false,
                                    captionFromAI: false,
                                    captionLangPickerOpen: false,
                                    captionLanguages: @js($captionLanguages ?? []),

                                    requiresApproval: {{ $reqAppr ? 'true' : 'false' }},
                                    mergedApprovalStatus: '{{ $mergedMeta['status'] }}',
                                    mergedApprovalNote: @js($mergedMeta['comment']),

                                    get canPostToSocial() {
                                        if (!this.hasSocialCredits) {
                                            return false;
                                        }
                                        if (this.requiresApproval) {
                                            return this.mergedImageUrl !== '' && this.mergedApprovalStatus === 'approved';
                                        }
                                        return this.brandedUrl !== '' || this.mergedImageUrl !== '';
                                    },

                                    get activePostImage() {
                                        if (this.postImageType === 'merged') return this.mergedImageUrl;
                                        return this.brandedUrl;
                                    },

                                    openPostFlow() {
                                        if (!this.hasSocialCredits) {
                                            $dispatch('notify', { message: 'Insufficient Social Post Credits!', type: 'error' });
                                            return;
                                        }
                                        if (this.requiresApproval) {
                                            if (!this.mergedImageUrl) {
                                                $dispatch('notify', { message: 'Merge a template before posting.', type: 'error' });
                                                return;
                                            }
                                            if (this.mergedApprovalStatus !== 'approved') {
                                                const msg = this.mergedApprovalStatus === 'rejected'
                                                    ? ('Rejected by approver: ' + (this.mergedApprovalNote || 'see note'))
                                                    : 'Awaiting approver approval before posting.';
                                                $dispatch('notify', { message: msg, type: 'error' });
                                                return;
                                            }
                                            this.postImageType = 'merged';
                                            this.switchModal('createPost');
                                            this.resizePostCaption();
                                            return;
                                        }
                                        if (!this.canPostToSocial) {
                                            $dispatch('notify', { message: 'Add a logo or merge a template before posting.', type: 'error' });
                                            return;
                                        }
                                        this.switchModal('postImageOptions');
                                    },

                                    resizePostCaption() {
                                        this.$nextTick(() => {
                                            requestAnimationFrame(() => {
                                                const el = this.$refs.postCaptionInput;
                                                if (!el) return;
                                                el.style.height = 'auto';
                                                const h = Math.min(el.scrollHeight + 4, 280);
                                                el.style.height = Math.max(h, 96) + 'px';
                                            });
                                        });
                                    },

                                    selectPostAsset(type) {
                                        if (this.requiresApproval && type !== 'merged') return;
                                        if (type === 'branded' && !this.brandedUrl) return;
                                        if (type === 'merged') {
                                            if (!this.mergedImageUrl) return;
                                            if (this.requiresApproval && this.mergedApprovalStatus !== 'approved') {
                                                $dispatch('notify', { message: this.mergedApprovalStatus === 'rejected' ? ('Rejected: ' + (this.mergedApprovalNote || 'see note')) : 'Awaiting approver approval.', type: 'error' });
                                                return;
                                            }
                                        }
                                        this.postImageType = type;
                                        this.switchModal('createPost');
                                        this.resizePostCaption();
                                    },

                                    handleLogoSelect(event) {
                                        const file = event.target.files[0];
                                        this.logoPreviewUrl = file ? URL.createObjectURL(file) : null;
                                    },

                                    closeModal() {
                                        this.openModal = null;
                                        this.captionLangPickerOpen = false;
                                        this.logoPreviewUrl = null;
                                        if (this.$refs.logoInput) this.$refs.logoInput.value = '';
                                    },
                                    switchModal(target) {
                                        this.openModal = target;
                                    },
                                    dnaFailed() {
                                        if (this.isRetryingPrompt) return false;
                                        return this.status === 'failed' || String(this.promptError || '').trim() !== '';
                                    },
                                    dnaPending() {
                                        return this.status === 'pending_prompt' && !this.dnaFailed() && !this.isRetryingPrompt;
                                    },
                                    pipelineReady() {
                                        return !this.dnaFailed() && !this.dnaPending() && !this.isRetryingPrompt;
                                    },
                                    stopPromptPolling() {
                                        this.promptPollToken += 1;
                                        if (this.promptPollTimer) {
                                            clearTimeout(this.promptPollTimer);
                                            this.promptPollTimer = null;
                                        }
                                    },
                                    schedulePromptPoll() {
                                        if (this.dnaFailed()) return;
                                        const token = this.promptPollToken;
                                        this.promptPollTimer = setTimeout(() => this.pollPromptStatus(token), 5000);
                                    },
                                    forceDnaFailed(message) {
                                        this.stopPromptPolling();
                                        this.status = 'failed';
                                        this.promptError = String(message || 'Prompt generation failed. Credits were not deducted.').trim();
                                        this.isRetryingPrompt = false;
                                        sessionStorage.removeItem('branding_{{ $occasion->id }}');
                                        sessionStorage.removeItem('merge_{{ $occasion->id }}');
                                        sessionStorage.removeItem('occasion_pipeline_poll');
                                        this.isAddingLogo = false;
                                        this.isMergingLocal = false;
                                        this.isTriggering = false;
                                        if (this.imageStatus === 'making') this.imageStatus = 'pending';
                                        window.dispatchEvent(new CustomEvent('occasion-pipeline-halt'));
                                    },
                                    haltPipelineOnDnaFailure() {
                                        if (!this.dnaFailed()) return;
                                        this.stopPromptPolling();
                                        sessionStorage.removeItem('branding_{{ $occasion->id }}');
                                        sessionStorage.removeItem('merge_{{ $occasion->id }}');
                                        sessionStorage.removeItem('occasion_pipeline_poll');
                                        this.isAddingLogo = false;
                                        this.isMergingLocal = false;
                                        this.isTriggering = false;
                                        if (this.imageStatus === 'making') this.imageStatus = 'pending';
                                        window.dispatchEvent(new CustomEvent('occasion-pipeline-halt'));
                                    },
                                    pipelineStillRunning() {
                                        if (this.isRetryingPrompt) return false;
                                        if (this.dnaFailed()) return false;
                                        if (this.dnaPending()) return true;
                                        // DNA ready, waiting for user to click Generate — do not auto-reload
                                        if (this.pipelineReady() && !this.imageUrl && this.imageStatus !== 'making') return false;
                                        if (this.imageStatus === 'making' && !this.imageUrl) return true;
                                        if (this.isAddingLogo && this.brandedUrl === '') return true;
                                        if (this.isMergingLocal && !this.mergedImageUrl && this.mergeStatus !== 'completed') return true;
                                        return false;
                                    },
                                    stopImagePolling() {
                                        this.imagePollToken += 1;
                                        if (this.imagePollTimer) {
                                            clearTimeout(this.imagePollTimer);
                                            this.imagePollTimer = null;
                                        }
                                    },
                                    scheduleImagePoll() {
                                        const token = this.imagePollToken;
                                        this.imagePollTimer = setTimeout(() => this.pollRenderStatus(token), 5000);
                                    },
                                    applyRenderStatus(data) {
                                        let becameReady = false;

                                        if (data.image_url) {
                                            if (!this.imageUrl) becameReady = true;
                                            this.imageUrl = data.image_url;
                                            this.imageStatus = data.image_status || 'completed';
                                        }

                                        if (data.branded_image_url) {
                                            this.brandedUrl = data.branded_image_url;
                                            sessionStorage.removeItem('branding_{{ $occasion->id }}');
                                            this.isAddingLogo = false;
                                        }

                                        if (data.merged_image_url) {
                                            this.mergedImageUrl = data.merged_image_url;
                                            this.mergeStatus = 'completed';
                                            sessionStorage.removeItem('merge_{{ $occasion->id }}');
                                            this.isMergingLocal = false;
                                        } else if (data.merge_status) {
                                            this.mergeStatus = data.merge_status;
                                        }

                                        return becameReady;
                                    },
                                    async pollRenderStatus(pollToken) {
                                        if (pollToken !== undefined && pollToken !== this.imagePollToken) return;
                                        if (!this.pipelineStillRunning()) {
                                            this.stopImagePolling();
                                            return;
                                        }

                                        try {
                                            const res = await fetch('{{ route('occasions.imageStatus', $occasion) }}', {
                                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                            });
                                            if (pollToken !== undefined && pollToken !== this.imagePollToken) return;

                                            const data = await res.json();
                                            const becameReady = this.applyRenderStatus(data);

                                            if (!this.pipelineStillRunning()) {
                                                this.stopImagePolling();
                                                sessionStorage.removeItem('occasion_pipeline_poll');
                                                window.dispatchEvent(new CustomEvent('occasion-pipeline-halt'));
                                                if (becameReady) {
                                                    $dispatch('notify', { message: 'Image render complete!', type: 'success' });
                                                }
                                                return;
                                            }
                                        } catch (e) {
                                            // keep polling on transient network errors
                                        }

                                        if (this.pipelineStillRunning()) {
                                            this.scheduleImagePoll();
                                        }
                                    },

                                    async pollPromptStatus(pollToken) {
                                        if (pollToken !== undefined && pollToken !== this.promptPollToken) return;
                                        if (this.dnaFailed()) {
                                            this.haltPipelineOnDnaFailure();
                                            return;
                                        }

                                        try {
                                            const res = await fetch('{{ route('occasions.promptStatus', $occasion) }}', {
                                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                            });
                                            if (pollToken !== undefined && pollToken !== this.promptPollToken) return;

                                            const data = await res.json();

                                            if (data.status === 'failed' || data.prompt_error_message) {
                                                const msg = data.prompt_error_message || 'Prompt generation failed. Credits were not deducted.';
                                                this.forceDnaFailed(msg);
                                                $dispatch('notify', { message: msg, type: 'error' });
                                                return;
                                            }

                                            this.status = data.status;
                                            this.promptError = '';

                                            if (data.has_prompts) {
                                                this.liveImagePrompt = data.image_prompt || '';
                                                this.status = data.status || 'ready';
                                                this.stopPromptPolling();
                                                sessionStorage.removeItem('occasion_pipeline_poll');
                                                window.dispatchEvent(new CustomEvent('occasion-pipeline-halt'));
                                                $dispatch('notify', { message: 'Campaign DNA ready! Click Generate to render.', type: 'success' });
                                                return;
                                            }
                                        } catch (e) {
                                            // keep polling on transient network errors
                                        }

                                        if (!this.dnaFailed() && this.status === 'pending_prompt') {
                                            this.schedulePromptPoll();
                                        }
                                    },

                                    async retryPromptGeneration() {
                                        if (!this.hasPromptCredits) {
                                            $dispatch('notify', { message: @js($pipelineBlockMessage ?? 'Insufficient Prompt Credits.'), type: 'error' });
                                            return;
                                        }
                                        if (this.isRetryingPrompt) return;
                                        this.stopPromptPolling();
                                        this.isRetryingPrompt = true;
                                        this.status = 'pending_prompt';
                                        this.promptError = '';
                                        this.liveImagePrompt = '';

                                        try {
                                            const res = await fetch('{{ route('occasions.retryPrompt', $occasion) }}', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                    'Accept': 'application/json',
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                }
                                            });

                                            let data = {};
                                            try {
                                                data = await res.json();
                                            } catch (parseError) {
                                                data = {};
                                            }

                                            if (!res.ok || data.success === false) {
                                                const msg = data.prompt_error_message || data.message || 'Re-render failed. Credits were not deducted.';
                                                this.forceDnaFailed(msg);
                                                $dispatch('notify', { message: msg, type: 'error' });
                                                return;
                                            }

                                            this.isRetryingPrompt = false;
                                            this.status = data.status || 'pending_prompt';
                                            this.promptError = '';

                                            if (data.has_prompts) {
                                                this.liveImagePrompt = data.image_prompt || '';
                                                this.status = data.status || 'ready';
                                                this.stopPromptPolling();
                                                sessionStorage.removeItem('occasion_pipeline_poll');
                                                window.dispatchEvent(new CustomEvent('occasion-pipeline-halt'));
                                                $dispatch('notify', { message: data.message || 'Campaign DNA ready! Click Generate to render.', type: 'success' });
                                                return;
                                            }

                                            $dispatch('notify', { message: data.message || 'Re-rendering DNA…', type: 'info' });
                                            this.pollPromptStatus();
                                        } catch (e) {
                                            this.forceDnaFailed('Could not reach the server. Credits were not deducted.');
                                            $dispatch('notify', { message: 'Could not reach the server.', type: 'error' });
                                        } finally {
                                            this.isRetryingPrompt = false;
                                        }
                                    },

                                    checkAndReload() {
                                        this.haltPipelineOnDnaFailure();
                                        if (this.dnaFailed()) return;
                                        // Prompt done — idle until user clicks Generate
                                        if (this.pipelineReady() && !this.imageUrl && this.imageStatus !== 'making' && !this.isAddingLogo && !this.isMergingLocal) {
                                            return;
                                        }

                                        if (this.brandedUrl !== '') {
                                            sessionStorage.removeItem('branding_{{ $occasion->id }}');
                                            this.isAddingLogo = false;
                                        }
                                        if (this.mergedImageUrl || this.mergeStatus === 'completed') {
                                            sessionStorage.removeItem('merge_{{ $occasion->id }}');
                                            this.isMergingLocal = false;
                                        }
                                        if (this.dnaPending()) {
                                            this.pollPromptStatus();
                                            return;
                                        }
                                        if (this.pipelineStillRunning()) {
                                            this.pollRenderStatus();
                                        }
                                    },

                                    async triggerMakePicture(){
                                        if(!confirm('Initiate Image Render? (Consumes 1 Credit)')) return;
                                        this.isTriggering = true;
                                        try {
                                            const response = await fetch(`/occasions/{{ $occasion->id }}/make-picture`, {
                                                method: 'POST',
                                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                             });
                                            const data = await response.json();
                                            if(data.success) { 
                                                this.imageStatus = 'making';
                                                $dispatch('notify', { message: 'Image Generation Queued', type: 'info' });
                                                this.checkAndReload();
                                            }
                                        } catch(e) { 
                                            $dispatch('notify', { message: 'Render Server Offline', type: 'error' });
                                        } finally { 
                                            this.isTriggering = false;
                                        }
                                    },

                                    async triggerAddLogo() {
                                        const fileInput = this.$refs.logoInput;
                                        if (!fileInput.files.length) {
                                            $dispatch('notify', { message: 'Please select a logo.', type: 'error' });
                                            return;
                                        }
                                        
                                        this.isAddingLogo = true;
                                        sessionStorage.setItem('branding_{{ $occasion->id }}', 'true');

                                        const formData = new FormData();
                                        formData.append('logo', fileInput.files[0]);
                                        formData.append('placement', this.logoPlacement);
                                        formData.append('_token', '{{ csrf_token() }}');

                                        try {
                                            const response = await fetch(`/occasions/{{ $occasion->id }}/add-logo`, {
                                                method: 'POST', body: formData, headers: { 'Accept': 'application/json' }
                                            });
                                            const data = await response.json();
                                            if (data.success) {
                                                $dispatch('notify', { message: 'Branding Pipeline Started!', type: 'success' });
                                                this.closeModal();
                                                this.checkAndReload();
                                            } else {
                                                sessionStorage.removeItem('branding_{{ $occasion->id }}');
                                                this.isAddingLogo = false;
                                                $dispatch('notify', { message: data.message, type: 'error' });
                                            }
                                        } catch(e) {
                                            sessionStorage.removeItem('branding_{{ $occasion->id }}');
                                            this.isAddingLogo = false;
                                            $dispatch('notify', { message: 'Network Error', type: 'error' });
                                        }
                                    },

                                    // ==========================================
                                    // NEW: SOCIAL POSTING METHODS
                                    // ==========================================
                                    openCaptionLanguagePicker() {
                                        if (!this.activePostImage) {
                                            $dispatch('notify', { message: 'Select an image to caption first.', type: 'error' });
                                            return;
                                        }
                                        if (!Array.isArray(this.captionLanguages) || this.captionLanguages.length === 0) {
                                            $dispatch('notify', { message: 'Caption languages unavailable — refresh or contact support.', type: 'error' });
                                            return;
                                        }
                                        this.captionLangPickerOpen = true;
                                    },

                                    async generateAICaption(captionLanguage) {
                                        if (!this.activePostImage || !captionLanguage) return;
                                        this.captionLangPickerOpen = false;
                                        this.isGeneratingCaption = true;
                                        try {
                                            const res = await fetch(`/occasions/{{ $occasion->id }}/generate-caption`, {
                                                method: 'POST', 
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({
                                                    image_url: this.activePostImage,
                                                    caption_language: captionLanguage,
                                                })
                                            });
                                            const data = await res.json();
                                            if (data.success) { 
                                                this.postCaption = data.caption; 
                                                this.captionFromAI = true;
                                                this.resizePostCaption();
                                                $dispatch('notify', { message: 'Caption generated!', type: 'success' }); 
                                            } else { 
                                                $dispatch('notify', { message: data.message, type: 'error' }); 
                                            }
                                        } catch (error) { 
                                            $dispatch('notify', { message: 'AI Engine Error', type: 'error' }); 
                                        } finally { 
                                            this.isGeneratingCaption = false; 
                                        }
                                    },

                                    async publishSocialPost() {
                                        if(!this.postCaption) { $dispatch('notify', { message: 'Please write a caption.', type: 'error' }); return; }
                                        if (!this.hasSocialCredits) {
                                            $dispatch('notify', { message: 'Insufficient Social Post Credits!', type: 'error' });
                                            return;
                                        }
                                        if (this.requiresApproval && (this.postImageType !== 'merged' || this.mergedApprovalStatus !== 'approved')) {
                                            const msg = this.mergedApprovalStatus === 'rejected'
                                                ? ('Rejected by approver: ' + (this.mergedApprovalNote || 'see note'))
                                                : 'Awaiting approver approval before posting.';
                                            $dispatch('notify', { message: msg, type: 'error' });
                                            return;
                                        }
                                        this.isPublishing = true;
                                        try {
                                            const res = await fetch(`/occasions/{{ $occasion->id }}/publish`, {
                                                method: 'POST', 
                                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                body: JSON.stringify({ 
                                                    media_url: this.activePostImage, 
                                                    caption: this.postCaption, 
                                                    media_source: this.postImageType,
                                                    scheduled_at: this.postScheduledTime 
                                                })
                                            });
                                            const data = await res.json();
                                            if(data.success) { 
                                                $dispatch('notify', { message: data.message, type: 'success' }); 
                                                this.closeModal(); 
                                            } else { 
                                                $dispatch('notify', { message: data.message, type: 'error' }); 
                                            }
                                        } catch(e) { 
                                            $dispatch('notify', { message: 'Publishing Error', type: 'error' }); 
                                        } finally { 
                                            this.isPublishing = false; 
                                        }
                                    }
                                }" x-init="haltPipelineOnDnaFailure(); checkAndReload();"
                                    @start-merge-pipeline.window="
                                        if($event.detail == '{{ $occasion->id }}') {
                                            isMergingLocal = true;
                                            checkAndReload();
                                        }
                                    "
                                    class="hover:bg-white/[0.01] transition-colors">

                                    {{-- COL 1: IDENTITY (NOW SHOWS ALL INPUTS) --}}
                                    <td class="px-4 sm:px-6 py-4 align-top w-1/4">
                                        <div @click="switchModal('details')" class="flex flex-col cursor-pointer group p-3 -mx-3 rounded-xl hover:bg-white/[0.03] border border-transparent hover:border-white/5 transition-all">
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <span class="text-xs font-black text-gray-100 uppercase tracking-wider leading-tight">{{ $occasion->occasion_identity }}</span>
                                                <svg class="w-3.5 h-3.5 text-gray-500 opacity-0 group-hover:opacity-100 group-hover:text-pink-400 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            </div>
                                            
                                            <div class="space-y-1.5">
                                                <p class="text-[9px] text-gray-400">
                                                    <span class="font-bold text-gray-500 uppercase tracking-widest">Target:</span> 
                                                    {{ $occasion->target_month ? date('F', mktime(0, 0, 0, $occasion->target_month, 1)) : 'N/A' }} {{ $occasion->target_year }}
                                                </p>
                                                <p class="text-[9px] text-gray-400 line-clamp-2">
                                                    <span class="font-bold text-gray-500 uppercase tracking-widest">Visual:</span> 
                                                    {{ $occasion->visual_direction ?: 'N/A' }}
                                                </p>
                                                <p class="text-[9px] text-gray-400 line-clamp-1">
                                                    <span class="font-bold text-gray-500 uppercase tracking-widest">Text:</span> 
                                                    {{ $occasion->custom_text ?: 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- COL 2: PROMPTS --}}
                                    <td class="px-4 sm:px-6 py-4 text-center align-top">
                                        <div x-show="dnaFailed() && !isRetryingPrompt" x-cloak class="flex flex-col items-center gap-2 max-w-[200px] mx-auto">
                                            <div class="w-full px-3 py-2 bg-red-500/10 border border-red-500/20 rounded-lg text-left">
                                                <p class="text-[8px] font-black text-red-400 uppercase tracking-widest mb-1">DNA Failed</p>
                                                <p class="text-[9px] text-red-300/90 leading-relaxed break-words" x-text="promptError || 'Prompt engine returned an error. No credit was deducted.'"></p>
                                            </div>
                                            <button type="button"
                                                @click="retryPromptGeneration()"
                                                :disabled="isRetryingPrompt || !hasPromptCredits"
                                                class="w-full px-3 py-2 bg-pink-600 hover:bg-pink-500 disabled:opacity-50 disabled:cursor-not-allowed text-[8px] font-black text-white uppercase tracking-widest rounded-lg transition-all shadow-lg shadow-pink-600/20">
                                                <span x-text="hasPromptCredits ? 'Re-render DNA' : '0 Prompt Credits'"></span>
                                            </button>
                                        </div>

                                        <div x-show="isRetryingPrompt && !dnaFailed()" x-cloak class="inline-flex items-center gap-2 px-3 py-1.5 bg-pink-500/5 border border-pink-500/10 rounded-lg">
                                            <span class="w-1.5 h-1.5 bg-pink-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(236,72,153,0.6)]"></span>
                                            <span class="text-[8px] font-black text-pink-400 uppercase tracking-widest">Retrying DNA</span>
                                        </div>

                                        <div x-show="dnaPending()" x-cloak class="inline-flex items-center gap-2 px-3 py-1.5 bg-yellow-500/5 border border-yellow-500/10 rounded-lg">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(234,179,8,0.6)]"></span>
                                            <span class="text-[8px] font-black text-yellow-500 uppercase tracking-widest">Writing DNA</span>
                                        </div>

                                        <div x-show="pipelineReady()" x-cloak>
                                            <button @click="switchModal('image'); isEditing=false;" class="px-2.5 py-1.5 bg-white/5 hover:bg-white/10 text-[8px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Image DNA</button>
                                        </div>
                                    </td>

                                    {{-- COL 3: RENDER CONTROLS --}}
                                    <td class="px-4 sm:px-6 py-4 align-top">
                                        <div x-show="pipelineReady()" x-cloak>
                                            <div class="flex flex-wrap items-center gap-2">
                                                
                                                {{-- DYNAMIC MAIN BUTTON --}}
                                                <button
                                                    @click="
                                                        if(!imageUrl && !hasImageCredits) { $dispatch('notify', {message: 'Insufficient Credits', type: 'error'}); return; }
                                                        if (imageStatus === 'making' || isTriggering) return;
                                                        if (imageUrl) {
                                                            brandedUrl !== '' ? switchModal('previewBranded') : switchModal('previewOriginal');
                                                        } else {
                                                            triggerMakePicture();
                                                        }
                                                    "
                                                    :disabled="imageStatus === 'making' || isTriggering || (!imageUrl && !hasImageCredits)"
                                                    class="h-8 px-2 w-32 text-[9px] font-bold rounded-lg transition-all uppercase tracking-widest flex items-center justify-center gap-1.5 border shadow-lg"
                                                    :class="{
                                                            'bg-emerald-500 border-emerald-500 text-black animate-pulse shadow-[0_0_15px_rgba(16,185,129,0.4)]': imageStatus === 'making',
                                                            'bg-blue-600 border-blue-500 text-white hover:bg-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.3)]': imageStatus !== 'making' && brandedUrl !== '',
                                                            'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500 hover:text-white': imageStatus !== 'making' && brandedUrl === '' && imageUrl !== '',
                                                            'bg-white text-black border-transparent hover:bg-gray-200': !imageUrl && imageStatus !== 'making' && hasImageCredits,
                                                            'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed': !imageUrl && imageStatus !== 'making' && !hasImageCredits
                                                        }">
                                                    
                                                    <span x-show="imageStatus === 'making'">Rendering...</span>
                                                    <span x-show="imageStatus !== 'making' && brandedUrl !== ''" x-cloak>View Branded</span>
                                                    <span x-show="imageStatus !== 'making' && brandedUrl === '' && imageUrl !== ''" x-cloak>View Image</span>
                                                    <span x-show="imageStatus !== 'making' && imageUrl === ''" x-text="hasImageCredits ? 'Generate' : '0 Credits'" x-cloak></span>
                                                </button>

                                                {{-- SECONDARY ACTION BUTTONS --}}
                                                <div class="flex items-center gap-1.5" x-cloak x-show="imageUrl !== '' && imageStatus !== 'making'">

                                                    {{-- Add Logo / Applying Button --}}
                                                    <button x-show="brandedUrl === ''" 
                                                            @click="if(!hasBrandingImageCredits) { $dispatch('notify', {message: 'Insufficient Image Branding Credits', type: 'error'}); return; } if(!isAddingLogo) switchModal('addLogo')" 
                                                            :disabled="isAddingLogo || !hasBrandingImageCredits"
                                                            class="h-8 px-3 rounded-lg transition-all shadow-lg flex items-center justify-center gap-1.5"
                                                            :class="isAddingLogo ? 'bg-blue-500 border-blue-500 text-black animate-pulse shadow-[0_0_15px_rgba(59,130,246,0.5)] cursor-wait' : (hasBrandingImageCredits ? 'bg-blue-500/10 border-blue-500/20 hover:bg-blue-500 text-blue-400 hover:text-white' : 'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed')"
                                                            title="Apply Branding Logo (1 Image Branding Credit)">
                                                        <template x-if="!isAddingLogo">
                                                            <div class="flex items-center gap-1.5">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path></svg>
                                                                <span class="text-[9px] font-black uppercase tracking-widest" x-text="hasBrandingImageCredits ? 'Add Logo' : '0 Brand Credits'"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="isAddingLogo">
                                                            <div class="flex items-center gap-1.5">
                                                                <svg class="animate-spin h-3 w-3 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                                <span class="text-[9px] font-black uppercase tracking-widest">Applying...</span>
                                                            </div>
                                                        </template>
                                                    </button>
                                                    
                                                    {{-- Original Button (Shows if branded image exists) --}}
                                                    <button x-show="brandedUrl !== ''" @click="switchModal('previewOriginal')" class="h-8 px-3 bg-gray-500/10 border border-gray-500/20 hover:bg-gray-500 hover:text-white text-gray-400 rounded-lg transition-all shadow-lg flex items-center justify-center gap-1.5" title="View Original Image">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        <span class="text-[9px] font-black uppercase tracking-widest">Original</span>
                                                    </button>

                                                    {{-- Merge Template --}}
                                                    <button x-show="!mergedImageUrl && !isMergingLocal"
                                                            @click="if(!hasBrandingImageCredits) { $dispatch('notify', {message: 'Insufficient Image Branding Credits', type: 'error'}); return; } resetTemplateSelection(); mergeModal = true; activeOccasionId = '{{ $occasion->id }}'; activeImageUrl = imageUrl;"
                                                            :disabled="!hasBrandingImageCredits"
                                                            class="h-8 px-3 rounded-lg transition-all shadow-lg flex items-center justify-center gap-1.5 border"
                                                            :class="hasBrandingImageCredits ? 'bg-orange-600/10 border-orange-500/20 hover:border-orange-500/50 text-orange-400 hover:bg-orange-600 hover:text-white' : 'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed'"
                                                            title="Merge with Template (1 Image Branding Credit)">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                                        <span class="text-[9px] font-black uppercase tracking-widest">Merge</span>
                                                    </button>

                                                    <button x-show="!mergedImageUrl && mergeStatus !== 'completed' && isMergingLocal" disabled
                                                            class="h-8 px-3 bg-orange-600 border border-orange-500 text-black rounded-lg transition-all shadow-lg flex items-center justify-center gap-1.5 animate-pulse cursor-wait">
                                                        <span class="text-[9px] font-black uppercase tracking-widest">Merging...</span>
                                                    </button>

                                                    <button x-show="mergedImageUrl !== '' || mergeStatus === 'completed'"
                                                            @click="switchModal('previewMerged')"
                                                            class="h-8 px-3 bg-orange-500/10 border border-orange-500/20 hover:bg-orange-600 text-orange-400 hover:text-white rounded-lg transition-all shadow-lg flex items-center justify-center gap-1.5"
                                                            title="View Merged Image">
                                                        <span class="text-[9px] font-black uppercase tracking-widest">Merged</span>
                                                    </button>

                                                    <template x-if="requiresApproval && (mergedImageUrl !== '' || mergeStatus === 'completed')">
                                                        <span class="h-8 px-2 inline-flex items-center rounded uppercase tracking-widest text-[8px] font-black border"
                                                            :class="mergedApprovalStatus === 'approved' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : (mergedApprovalStatus === 'rejected' ? 'bg-red-500/10 border-red-500/30 text-red-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400')"
                                                            x-text="mergedApprovalStatus === 'approved' ? '✓ Approved' : (mergedApprovalStatus === 'rejected' ? '✕ Rejected' : '⏳ Awaiting Approval')"></span>
                                                    </template>

                                                    {{-- POST ASSET BUTTON --}}
                                                    <button @click="openPostFlow()"
                                                            :disabled="!canPostToSocial"
                                                            class="h-8 px-3 rounded-lg transition-all shadow-lg flex items-center justify-center gap-1.5 border"
                                                            :class="canPostToSocial ? 'bg-indigo-500/10 border-indigo-500/20 hover:bg-indigo-500 text-indigo-400 hover:text-white' : 'bg-white/5 border-white/10 text-gray-600 cursor-not-allowed'"
                                                            title="Post to Social Media">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                                                        <span class="text-[9px] font-black uppercase tracking-widest" x-text="hasSocialCredits ? 'Post' : '0 Post Credits'"></span>
                                                    </button>
                                                    
                                                    @if(!empty($canStartPipeline))
                                                        <a href="{{ route('occasions.create', ['duplicate' => $occasion->id]) }}" class="w-8 h-8 bg-white/5 border border-white/10 hover:bg-white/20 text-gray-400 hover:text-white rounded-lg transition-all shadow-lg flex items-center justify-center" title="Duplicate & Regenerate">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                        </a>
                                                    @else
                                                        <button type="button"
                                                            @click="$dispatch('notify', { message: @js($pipelineBlockMessage ?? 'Insufficient Prompt Credits.'), type: 'error' })"
                                                            class="w-8 h-8 bg-white/5 border border-white/10 text-gray-600 rounded-lg transition-all shadow-lg flex items-center justify-center cursor-not-allowed opacity-50"
                                                            title="Duplicate unavailable — no prompt credits">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                        </button>
                                                    @endif
                                                </div>

                                                @include('partials.approver-note', [
                                                    'requiresApproval' => $reqAppr,
                                                    'status' => $mergedMeta['status'],
                                                    'comment' => $mergedMeta['comment'],
                                                ])
                                            </div>
                                        </div>

                                        <div x-show="isRetryingPrompt && !dnaFailed()" x-cloak class="text-center py-2">
                                            <p class="text-[9px] text-pink-400/80 uppercase tracking-widest font-bold">Render paused — retrying DNA</p>
                                        </div>

                                        <div x-show="dnaPending()" x-cloak class="text-center py-2">
                                            <p class="text-[9px] text-yellow-500/70 uppercase tracking-widest font-bold">Render paused — writing DNA</p>
                                        </div>

                                        <div x-show="dnaFailed()" x-cloak class="text-center py-2">
                                            <p class="text-[9px] text-gray-500 uppercase tracking-widest font-bold">Render paused — fix DNA first</p>
                                        </div>

                                        {{-- MODALS TELEPORT --}}
                                        <template x-teleport="body">
                                            <div x-show="openModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6 bg-black/95 backdrop-blur-md" x-cloak>

                                                {{-- SELECT POST ASSET (logo'd vs merged) --}}
                                                <div x-show="openModal === 'postImageOptions'" class="bg-[#0a0a0a] border border-white/10 w-full max-w-sm rounded-xl shadow-2xl overflow-hidden" @click.away="closeModal()">
                                                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                                                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Select Post Image</h3>
                                                        <button @click="closeModal()" class="text-gray-500 hover:text-white text-lg">✕</button>
                                                    </div>
                                                    <div class="p-6 flex flex-col gap-4 bg-black/40">
                                                        <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest text-center mb-1">Which asset do you want to publish?</p>
                                                        <template x-if="requiresApproval">
                                                            <p class="text-[9px] text-amber-400/90 font-bold uppercase tracking-widest text-center -mb-1">Approver sign-off required for merged pictures only</p>
                                                        </template>
                                                        <button x-show="!requiresApproval" @click="selectPostAsset('branded')"
                                                                :disabled="brandedUrl === ''"
                                                                class="w-full py-4 bg-blue-600/10 border border-blue-500/20 text-blue-400 hover:bg-blue-600 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-blue-600/10 disabled:hover:text-blue-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                                            Logo'd Pic
                                                        </button>
                                                        <button @click="selectPostAsset('merged')"
                                                                :disabled="mergedImageUrl === '' || (requiresApproval && mergedApprovalStatus !== 'approved')"
                                                                class="w-full py-4 bg-violet-600/10 border border-violet-500/20 text-violet-400 hover:bg-violet-600 hover:text-white rounded-lg text-[10px] font-black uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-violet-600/10 disabled:hover:text-violet-400">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                                            <span x-text="(requiresApproval && mergedApprovalStatus !== 'approved') ? (mergedApprovalStatus === 'rejected' ? 'Merged — Rejected' : 'Merged — Needs Approval') : 'Merged Pic'"></span>
                                                        </button>
                                                        <template x-if="requiresApproval && mergedImageUrl && mergedApprovalStatus !== 'approved'">
                                                            <p class="text-[8px] font-bold uppercase tracking-widest text-center"
                                                               :class="mergedApprovalStatus === 'rejected' ? 'text-red-400' : 'text-amber-400'"
                                                               x-text="mergedApprovalStatus === 'rejected' ? ('Rejected: ' + (mergedApprovalNote || 'see approver note')) : 'Locked until your approver signs off'"></p>
                                                        </template>
                                                    </div>
                                                </div>

                                                {{-- CREATE POST / SOCIAL MODAL --}}
                                                <div x-show="openModal === 'createPost'"
                                                     x-effect="openModal === 'createPost' && resizePostCaption()"
                                                     class="relative bg-[#0f0f0f] border border-white/5 w-full max-w-3xl rounded-xl shadow-2xl flex flex-col max-h-[90vh]"
                                                     :class="captionLangPickerOpen ? 'overflow-visible' : 'overflow-hidden'"
                                                     @click.away="captionLangPickerOpen ? (captionLangPickerOpen = false) : closeModal()">
                                                    
                                                    <div class="flex flex-col flex-1 min-h-0 transition-all duration-300"
                                                         :class="captionLangPickerOpen ? 'blur-md brightness-[0.65] pointer-events-none select-none' : ''">

                                                    {{-- Header --}}
                                                    <div class="px-6 py-4 flex justify-between items-center border-b border-white/5 shrink-0">
                                                        <h3 class="text-[11px] font-black text-gray-300 uppercase tracking-[0.25em]">CREATE POST</h3>
                                                        <button @click="closeModal()" class="text-gray-500 hover:text-white transition-colors">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        </button>
                                                    </div>

                                                    {{-- Body --}}
                                                    <div class="p-8 overflow-y-auto custom-scrollbar flex-1 relative">

                                                        {{-- AI Loading Overlay --}}
                                                        <div x-show="isGeneratingCaption" 
                                                             x-transition:enter="transition ease-out duration-300"
                                                             x-transition:enter-start="opacity-0"
                                                             x-transition:enter-end="opacity-100"
                                                             x-transition:leave="transition ease-in duration-200"
                                                             x-transition:leave-start="opacity-100"
                                                             x-transition:leave-end="opacity-0"
                                                             class="absolute inset-0 z-50 flex flex-col items-center justify-center pointer-events-none" x-cloak>
                                                            <div class="bg-[#0a0a0a]/90 border border-indigo-500/30 px-6 py-5 rounded-2xl shadow-2xl flex flex-col items-center gap-3 backdrop-blur-xl transform transition-all">
                                                                <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400">Synthesizing Caption...</span>
                                                            </div>
                                                        </div>

                                                        {{-- Blur Wrapper --}}
                                                        <div :class="{'blur-md opacity-30 pointer-events-none scale-[0.98]': isGeneratingCaption}" class="transition-all duration-500 ease-in-out h-full">
                                                            {{-- Profile Info --}}
                                                            <div class="flex items-center gap-3 mb-6">
                                                                <div class="w-10 h-10 rounded-full border border-indigo-500/50 flex items-center justify-center text-white bg-black">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-sm font-bold text-white tracking-wide">CGI Director</p>
                                                                    <p class="text-[10px] text-gray-400 flex items-center gap-1 mt-0.5">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                                        Public Broadcast
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            {{-- Caption Input --}}
                                                            <div class="mb-4">
                                                                <div class="flex justify-between items-center mb-2">
                                                                    <label class="text-gray-400 text-sm font-bold">What's on your mind?</label>
                                                                    <button type="button"
                                                                        @click="openCaptionLanguagePicker()"
                                                                        :disabled="isGeneratingCaption || !activePostImage"
                                                                        class="text-[10px] font-black uppercase tracking-widest bg-transparent border border-white/10 text-indigo-400 hover:bg-white/5 px-3 py-1.5 rounded transition-all flex items-center gap-1.5 disabled:opacity-50 pointer-events-auto relative z-[60]">
                                                                        <template x-if="isGeneratingCaption"><svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></template>
                                                                        <template x-if="!isGeneratingCaption"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg></template>
                                                                        <span x-text="isGeneratingCaption ? 'Writing...' : 'AI Auto-Caption'"></span>
                                                                    </button>
                                                                </div>

                                                                <textarea
                                                                    x-ref="postCaptionInput"
                                                                    x-model="postCaption"
                                                                    @input="resizePostCaption(); captionFromAI = false"
                                                                    x-init="resizePostCaption()"
                                                                    rows="3"
                                                                    :disabled="isGeneratingCaption"
                                                                    placeholder="Click AI Auto-Caption or write your own…"
                                                                    class="w-full bg-white/[0.03] border border-white/10 rounded-lg text-white text-[14px] p-3 focus:ring-1 focus:ring-indigo-500/40 focus:border-indigo-500/40 resize-y placeholder-gray-600 leading-relaxed min-h-[96px] max-h-[280px] overflow-y-auto disabled:opacity-60"></textarea>
                                                                <p class="text-[8px] text-indigo-400/80 font-bold uppercase tracking-widest mt-1.5"
                                                                   x-show="captionFromAI && postCaption">
                                                                    AI caption — edit before publishing
                                                                </p>
                                                            </div>

                                                            {{-- Image Preview --}}
                                                            <div class="relative w-full rounded-xl overflow-hidden mb-6 group bg-black/30">
                                                                <div class="absolute top-3 left-3 z-10">
                                                                    <span class="px-3 py-1.5 rounded text-[9px] uppercase tracking-widest font-black backdrop-blur-md border"
                                                                          :class="postImageType === 'merged' ? 'bg-violet-500/80 text-white border-violet-400/50' : 'bg-blue-500/80 text-white border-blue-400/50'"
                                                                          x-text="postImageType === 'merged' ? 'Merged Pic' : 'Logo\'d Pic'"></span>
                                                                </div>
                                                                <img :src="activePostImage" class="w-full h-auto object-contain">
                                                                <div class="absolute top-3 right-3 bg-black/80 px-3 py-1.5 rounded text-[9px] font-black text-white uppercase tracking-widest">Preview</div>
                                                            </div>

                                                            <template x-if="requiresApproval && mergedApprovalStatus === 'approved' && mergedApprovalNote">
                                                                <div class="px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                                                                    <p class="text-[8px] font-black text-emerald-400 uppercase tracking-widest mb-1">Approver note</p>
                                                                    <p class="text-[11px] text-emerald-100/90 italic leading-relaxed" x-text="mergedApprovalNote"></p>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    {{-- Footer --}}
                                                    <div class="px-8 py-4 border-t border-white/5 bg-[#0f0f0f] flex justify-between items-center shrink-0">
                                                        <button @click="switchModal('postImageOptions')" class="text-gray-500 hover:text-white text-xs font-bold transition-colors flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                                            CHANGE IMAGE
                                                        </button>
                                                        <button @click="publishSocialPost()" :disabled="isPublishing || !postCaption || activePostImage === '' || (requiresApproval && mergedApprovalStatus !== 'approved')" class="bg-[#2563eb] hover:bg-blue-500 text-white px-8 py-2.5 rounded-lg text-sm font-bold tracking-wide transition-all disabled:opacity-50 flex items-center gap-2">
                                                            <template x-if="isPublishing"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></template>
                                                            <template x-if="!isPublishing"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg></template>
                                                            <span x-text="isPublishing ? 'PROCESSING...' : (postScheduledTime ? 'SCHEDULE' : 'PUBLISH')"></span>
                                                        </button>
                                                    </div>

                                                    </div>{{-- end blurred create-post content --}}

                                                    {{-- Language picker overlay (inside modal — avoids nested x-teleport issues on some deploy builds) --}}
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
                                                        <div class="w-full max-w-md max-h-[min(640px,calc(100vh-3rem))] flex flex-col rounded-2xl border border-pink-500/30 bg-gradient-to-b from-[#16161f] via-[#101016] to-[#0a0a0a] shadow-[0_28px_90px_-16px_rgba(0,0,0,0.85)] overflow-hidden"
                                                             @click.stop>
                                                            <div class="px-6 pt-6 pb-5 border-b border-white/[0.06] shrink-0">
                                                                <div class="flex items-start justify-between gap-4">
                                                                    <div class="flex items-center gap-3 min-w-0">
                                                                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-pink-500/20 to-indigo-500/20 border border-pink-500/30 flex items-center justify-center shrink-0">
                                                                            <svg class="w-5 h-5 text-pink-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
                                                                        </div>
                                                                        <div class="min-w-0">
                                                                            <p class="text-[10px] font-black text-pink-400/90 uppercase tracking-[0.2em]">AI Caption</p>
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
                                                                            @click="generateAICaption(opt.value)"
                                                                            class="group flex items-center justify-between gap-3 w-full px-4 py-3.5 rounded-xl border border-white/[0.08] bg-white/[0.02] hover:bg-pink-500/10 hover:border-pink-500/35 focus:outline-none focus:ring-2 focus:ring-pink-500/30 transition-all text-left">
                                                                            <span class="text-[13px] font-semibold text-gray-100 group-hover:text-white" x-text="opt.label"></span>
                                                                            <span class="text-[12px] text-gray-500 group-hover:text-pink-200/90 font-medium" x-text="opt.native || ''"></span>
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

                                                {{-- DETAILS VIEW (UPDATED TO SHOW ALL INPUTS) --}}
                                                <div x-show="openModal === 'details'" class="bg-[#0a0a0a] border border-white/10 w-full max-w-3xl rounded-xl shadow-2xl overflow-hidden" @click.away="closeModal()">
                                                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center">
                                                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Campaign Config</h3>
                                                        <button @click="closeModal()" class="text-gray-500 hover:text-white text-lg">✕</button>
                                                    </div>
                                                    <div class="p-8 grid grid-cols-1 sm:grid-cols-2 gap-8 bg-black/40">
                                                        <div>
                                                            <p class="text-[9px] font-black text-pink-500/80 uppercase tracking-[0.2em] mb-1">01. Target Date</p>
                                                            <p class="text-sm font-black text-white">{{ $occasion->target_month ? date('F', mktime(0, 0, 0, $occasion->target_month, 1)) : 'N/A' }} {{ $occasion->target_year }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-[9px] font-black text-pink-500/80 uppercase tracking-[0.2em] mb-1">02. Occasion Identity</p>
                                                            <p class="text-sm font-black text-white">{{ $occasion->occasion_identity }}</p>
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <p class="text-[9px] font-black text-pink-500/80 uppercase tracking-[0.2em] mb-1">03. Visual Direction</p>
                                                            <p class="text-xs font-bold text-gray-300 italic">{{ $occasion->visual_direction ?: 'N/A' }}</p>
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <p class="text-[9px] font-black text-pink-500/80 uppercase tracking-[0.2em] mb-1">04. Marketing Text</p>
                                                            <p class="text-xs font-bold text-gray-300 italic">{{ $occasion->custom_text ?: 'N/A' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="px-8 py-5 border-t border-white/5 flex justify-end">
                                                        <button @click="closeModal()" class="px-5 py-2.5 bg-gray-800 text-white rounded text-[10px] font-black uppercase tracking-widest hover:bg-gray-700">Close</button>
                                                    </div>
                                                </div>

                                                {{-- PROMPT MODAL --}}
                                                <div x-show="openModal === 'image'" class="bg-[#0a0a0a] border border-white/10 w-full max-w-xl rounded-xl shadow-2xl overflow-hidden" @click.away="closeModal()">
                                                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center"><h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">DNA SEQUENCE</h3><button @click="closeModal()" class="text-gray-500 hover:text-white text-lg">✕</button></div>
                                                    <div class="p-8"><div class="bg-black p-5 rounded border border-white/5 font-mono text-xs text-gray-400 max-h-[40vh] overflow-y-auto whitespace-pre-wrap leading-relaxed shadow-inner" x-text="liveImagePrompt"></div></div>
                                                </div>

                                                {{-- ADD LOGO MODAL --}}
                                                <div x-show="openModal === 'addLogo'" class="bg-[#0a0a0a] border border-white/10 w-full max-w-md rounded-xl shadow-2xl overflow-hidden" @click.away="closeModal()">
                                                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center"><h3 class="text-[10px] font-black text-blue-400 uppercase tracking-[0.3em] flex items-center gap-2">Apply Branding Logo</h3><button @click="closeModal()" class="text-gray-500 hover:text-white text-lg">✕</button></div>
                                                    <div class="p-6 space-y-5 bg-black/40">
                                                        <div>
                                                            <div class="flex items-center justify-center w-full">
                                                                <label class="flex flex-col items-center justify-center w-full h-40 border-2 border-white/10 border-dashed rounded-lg cursor-pointer bg-black hover:bg-white/[0.02] transition-all overflow-hidden" :class="logoPreviewUrl ? 'border-blue-500/50' : 'hover:border-blue-500/50'">
                                                                    <div x-show="!logoPreviewUrl" class="flex flex-col items-center justify-center pt-5 pb-6">
                                                                        <svg class="w-8 h-8 mb-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                                        <p class="mb-2 text-[10px] text-gray-400"><span class="font-bold text-blue-400">Click to upload</span></p>
                                                                    </div>
                                                                    <div x-show="logoPreviewUrl" class="w-full h-full flex items-center justify-center p-3 relative group">
                                                                        <img :src="logoPreviewUrl" class="max-w-full max-h-full object-contain drop-shadow-2xl z-10">
                                                                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-20"><span class="text-[10px] font-black text-white uppercase tracking-widest bg-white/10 px-3 py-1.5 rounded backdrop-blur-sm border border-white/20">Change Logo</span></div>
                                                                    </div>
                                                                    <input type="file" x-ref="logoInput" @change="handleLogoSelect" class="hidden" accept="image/png, image/jpeg" />
                                                                </label>
                                                            </div>
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
                                                        <button @click="closeModal()" class="px-4 py-2 bg-transparent text-gray-400 hover:text-white rounded text-[10px] font-black uppercase tracking-widest transition-colors">Cancel</button>
                                                        <button @click="triggerAddLogo()" :disabled="!logoPreviewUrl || isAddingLogo" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded text-[10px] font-black uppercase tracking-widest shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                                            <span x-show="!isAddingLogo">Generate Branding</span>
                                                            <span x-show="isAddingLogo">Processing...</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- PREVIEW ORIGINAL MODAL --}}
                                                <div x-show="openModal === 'previewOriginal'" class="relative w-full max-w-4xl">
                                                    <button @click="closeModal()" class="absolute -top-12 right-0 text-white text-[10px] font-black uppercase tracking-[0.2em] bg-white/5 px-4 py-2 rounded-full hover:bg-red-500 transition-all">Close Viewer ✕</button>
                                                    <div class="bg-black border border-white/10 rounded-xl overflow-hidden shadow-2xl" @click.away="closeModal()">
                                                        <template x-if="openModal==='previewOriginal'"><img :src="imageUrl" class="w-full max-h-[80vh] object-contain"></template>
                                                    </div>
                                                </div>

                                                {{-- PREVIEW BRANDED MODAL --}}
                                                <div x-show="openModal === 'previewBranded'" class="relative w-full max-w-4xl">
                                                    <button @click="closeModal()" class="absolute -top-12 right-0 text-white text-[10px] font-black uppercase tracking-[0.2em] bg-white/5 px-4 py-2 rounded-full hover:bg-red-500 transition-all">Close Viewer ✕</button>
                                                    <div class="bg-black border border-white/10 rounded-xl overflow-hidden shadow-2xl" @click.away="closeModal()">
                                                        <template x-if="openModal==='previewBranded'"><img :src="brandedUrl" class="w-full max-h-[80vh] object-contain"></template>
                                                    </div>
                                                </div>

                                                {{-- PREVIEW MERGED MODAL --}}
                                                <div x-show="openModal === 'previewMerged'" class="relative w-full max-w-4xl">
                                                    <button @click="closeModal()" class="absolute -top-12 right-0 text-white text-[10px] font-black uppercase tracking-[0.2em] bg-white/5 px-4 py-2 rounded-full hover:bg-red-500 transition-all">Close Viewer ✕</button>
                                                    <div class="bg-black border border-white/10 rounded-xl overflow-hidden shadow-2xl" @click.away="closeModal()">
                                                        <template x-if="openModal==='previewMerged'"><img :src="mergedImageUrl" class="w-full max-h-[80vh] object-contain"></template>
                                                    </div>
                                                </div>

                                            </div>
                                        </template>
                                    </td>
                                    
                                    {{-- COL 4: DELETE --}}
                                    <td class="px-4 sm:px-6 py-4 text-right align-top w-16">
                                        <form action="{{ route('occasions.destroy', $occasion->id) }}" method="POST" onsubmit="return confirm('Purge Campaign?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="group flex items-center justify-center h-8 w-8 bg-white/5 border border-white/10 hover:bg-red-500/10 hover:border-red-500/30 rounded transition-all ml-auto">
                                                <svg class="w-3.5 h-3.5 text-gray-500 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-24 text-center">
                                        <h3 class="text-sm font-black text-white uppercase tracking-[0.2em] mb-1">No Campaigns Generated</h3>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($showPostHistoryTab)
            <div x-show="studioTab === 'posts'" x-cloak class="border-t border-white/5">
                @include('partials.occasion-post-history-studio', [
                    'socialPosts' => $socialPosts,
                    'postHistoryStats' => $postHistoryStats,
                    'accent' => 'pink',
                ])
            </div>
        @endif

        @if($showApprovalTab)
            <div x-show="studioTab === 'approval'" x-cloak class="border-t border-white/5">
                @include('partials.approval-history-studio', ['approvalHistory' => $approvalHistory, 'accent' => 'pink'])
            </div>
        @endif

        @include('partials.merge-template-modal')
    </div>
    <style>
        [x-cloak] { display: none !important; }
        body { background-color: #050505; -webkit-font-smoothing: antialiased; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #0a0a0a; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #374151; }
    </style>
</x-app-layout>