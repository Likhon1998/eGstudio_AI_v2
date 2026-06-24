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
        x-init="
            @if(session('success')) add('{{ session('success') }}', 'success'); @endif
            @if(session('error')) add('{{ session('error') }}', 'error'); @endif
            @if($errors->any()) 
                @foreach($errors->all() as $error) add('{{ $error }}', 'error'); @endforeach 
            @endif
        "
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

    <div class="w-full max-w-[98%] mx-auto py-4 px-2 sm:px-4">

        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4" x-data="{ 
            showAutofillModal: false,
            isAutofilling: false,
            autofillImage: null,
            autofillPreview: null,
            selectedAssetPath: null,
            searchQuery: '',
            dropdownOpen: false,
            business: localStorage.getItem('cgi_business') || 'lighting',
            businessOpen: false,

            businessMeta() {
                return window.cgiBusinessPresets?.[this.business] || { label: 'Lumina Elite', tagline: 'Premium Lighting Studio', icon: '💡' };
            },

            setBusiness(key) {
                this.business = key;
                localStorage.setItem('cgi_business', key);
                this.businessOpen = false;
                const input = document.getElementById('cgi_business_type');
                if (input) input.value = key;
                $dispatch('cgi-business-changed', { business: key });
            },

            handleAutofillFile(e) {
                const file = e.target.files[0];
                if (file) {
                    this.autofillImage = file;
                    this.autofillPreview = URL.createObjectURL(file);
                    this.selectedAssetPath = null;
                    this.dropdownOpen = false;
                }
            },

            selectFromLibrary(path, fullUrl) {
                this.autofillPreview = fullUrl;
                this.selectedAssetPath = path;
                this.autofillImage = null;
                this.dropdownOpen = false;
            },

            async triggerAutofill() {
                if (!this.autofillImage && !this.selectedAssetPath) {
                    $dispatch('notify', { message: 'Please select or upload a picture.', type: 'error' });
                    return;
                }

                this.isAutofilling = true;
                const formData = new FormData();
                if (this.autofillImage) formData.append('product_image', this.autofillImage);
                if (this.selectedAssetPath) formData.append('selected_asset_path', this.selectedAssetPath);
                formData.append('business_type', document.getElementById('cgi_business_type')?.value || this.business || 'lighting');
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    const response = await fetch('{{ route('cgi.autofill') }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        // Transfer file to main input if it's a new upload
                        if (this.autofillImage) {
                            const mainInput = document.getElementById('product_image');
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(this.autofillImage);
                            mainInput.files = dataTransfer.files;
                        }

                        // Prepare payload for main form components
                        const fillData = {
                            ...result.data,
                            _imagePreview: this.autofillPreview,
                            _assetPath: this.selectedAssetPath,
                            _isNew: !!this.autofillImage
                        };

                        $dispatch('cgi-autofill-reset');
                        $dispatch('cgi-autofill-data', fillData);
                        $dispatch('notify', { message: 'AI Analysis Complete! Form Filled.', type: 'success' });
                        this.showAutofillModal = false;
                    } else {
                        $dispatch('notify', { message: result.message || 'Autofill failed.', type: 'error' });
                    }
                } catch (e) {
                    $dispatch('notify', { message: 'Network error during AI analysis.', type: 'error' });
                } finally {
                    this.isAutofilling = false;
                }
            }
        }" x-init="$nextTick(() => { const input = document.getElementById('cgi_business_type'); if (input) input.value = business; })">
            <div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">CGI Studio Director</h2>
                <div class="flex items-center gap-3 mt-1">
                    <p class="text-gray-400 text-[11px] font-medium italic">Build your commercial or</p>
                    <button type="button" @click="showAutofillModal = true" class="flex items-center gap-1.5 px-2.5 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-[9px] font-black uppercase tracking-widest shadow-lg shadow-blue-600/20 transition-all group">
                        <svg class="w-3 h-3 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        AI Auto-Fill
                    </button>
                </div>
            </div>

            {{-- AI AUTO-FILL MODAL --}}
            <template x-teleport="body">
                <div x-show="showAutofillModal" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/90 backdrop-blur-md" @click="showAutofillModal = false" x-transition.opacity></div>
                    
                    <div class="relative bg-[#0a0a0a] border border-white/10 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all"
                         x-show="showAutofillModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                        
                        <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                            <h3 class="text-[10px] font-black text-blue-400 uppercase tracking-[0.3em] flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Intelligent Asset Analysis
                            </h3>
                            <button @click="showAutofillModal = false" class="text-gray-500 hover:text-white transition-colors">✕</button>
                        </div>

                        <div class="p-6 space-y-6 bg-black/40">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 text-center">Step 1: Provide Product Image</label>
                                
                                {{-- Selection Method: Library or Upload --}}
                                <div class="space-y-4">
                                    {{-- Custom Library Selector --}}
                                    <div class="relative">
                                        <button type="button" @click="dropdownOpen = !dropdownOpen" class="w-full bg-[#111] border border-gray-700/80 rounded-lg p-2.5 flex items-center justify-between hover:border-blue-500/50 transition-all">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Select from library...</span>
                                            </div>
                                            <svg class="w-3 h-3 text-gray-500 transition-transform" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </button>

                                        <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-cloak class="absolute left-0 right-0 top-full mt-1 bg-[#1a1a1a] border border-gray-700 rounded-lg shadow-2xl z-[70] flex flex-col overflow-hidden">
                                            <div class="p-2 border-b border-gray-800 bg-[#111]">
                                                <input type="text" x-model="searchQuery" placeholder="Search products..." class="w-full bg-[#0a0a0a] border border-gray-700 rounded-md px-3 py-1.5 text-white text-[10px] outline-none">
                                            </div>
                                            <div class="max-h-40 overflow-y-auto custom-scrollbar p-1">
                                                @foreach($productAssets as $asset)
                                                    <button type="button" 
                                                            x-show="String('{{ addslashes($asset->name) }}').toLowerCase().includes(searchQuery.toLowerCase())"
                                                            @click="selectFromLibrary('{{ $asset->file_path }}', '{{ asset('storage/' . $asset->file_path) }}')"
                                                            class="w-full flex items-center gap-2 p-1.5 rounded hover:bg-blue-600/20 transition-colors text-left">
                                                        <img src="{{ asset('storage/' . $asset->file_path) }}" class="w-6 h-6 rounded object-cover border border-gray-700 bg-black">
                                                        <span class="text-[10px] font-bold text-gray-300 truncate">{{ $asset->name }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    {{-- OR Divider --}}
                                    <div class="flex items-center gap-3">
                                        <div class="h-px flex-1 bg-white/5"></div>
                                        <span class="text-[8px] font-black text-gray-600 uppercase tracking-widest">OR UPLOAD</span>
                                        <div class="h-px flex-1 bg-white/5"></div>
                                    </div>

                                    {{-- Upload Area / Preview --}}
                                    <label class="flex flex-col items-center justify-center w-full h-44 border-2 border-white/10 border-dashed rounded-xl cursor-pointer bg-black hover:bg-white/[0.02] transition-all overflow-hidden group relative" :class="autofillPreview ? 'border-blue-500/50' : 'hover:border-blue-500/50'">
                                        <div x-show="!autofillPreview" class="flex flex-col items-center justify-center">
                                            <div class="p-3 bg-gray-800/50 rounded-full mb-2 group-hover:bg-blue-600/20 transition-colors">
                                                <svg class="w-6 h-6 text-gray-500 group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                            </div>
                                            <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">New Reference Image</p>
                                        </div>
                                        <img x-show="autofillPreview" :src="autofillPreview" class="w-full h-full object-contain p-2 scale-95 group-hover:scale-100 transition-transform">
                                        <input type="file" class="hidden" @change="handleAutofillFile" accept="image/*">
                                        
                                        <div x-show="autofillPreview" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="text-[8px] font-black text-white uppercase tracking-widest bg-blue-600 px-3 py-1.5 rounded-lg shadow-lg">Change Image</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="bg-blue-500/5 border border-blue-500/10 p-3 rounded-lg">
                                <p class="text-[9px] text-blue-400/80 leading-relaxed text-center font-medium italic">
                                    "AI will analyze this image to determine the product name, materials, atmosphere, and cinematic style for you."
                                </p>
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-end gap-3">
                            <button @click="showAutofillModal = false" class="px-4 py-2 text-gray-400 hover:text-white rounded text-[10px] font-black uppercase tracking-widest transition-colors">Cancel</button>
                            <button @click="triggerAutofill()" :disabled="isAutofilling" class="px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-[10px] font-black uppercase tracking-widest shadow-lg transition-all flex items-center gap-2 disabled:opacity-50">
                                <span x-show="!isAutofilling">Analyze & Fill Form</span>
                                <span x-show="isAutofilling" class="flex items-center gap-2">
                                    <svg class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    AI is Thinking...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <div class="flex items-center gap-2 w-full sm:w-auto">

                {{-- Business Type Selector (beside Directory, no step number) --}}
                <div class="relative flex-1 sm:flex-none">
                    <button type="button" @click="businessOpen = !businessOpen"
                        class="group w-full sm:w-auto min-w-[168px] px-4 py-2 bg-gray-800/80 border border-gray-700 text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-all duration-200 font-semibold text-xs shadow-lg backdrop-blur-sm flex items-center justify-between gap-2">
                        <span class="flex items-center gap-1.5 min-w-0 text-left leading-tight">
                            <span x-text="businessMeta().icon" class="shrink-0"></span>
                            <span class="min-w-0">
                                <span x-text="businessMeta().label" class="block truncate"></span>
                                <span x-show="businessMeta().tagline" x-text="businessMeta().tagline"
                                    class="block truncate text-[8px] font-bold text-gray-500 uppercase tracking-widest group-hover:text-gray-400"></span>
                            </span>
                        </span>
                        <svg class="w-3 h-3 text-gray-500 shrink-0 transition-transform group-hover:text-gray-300" :class="businessOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div x-show="businessOpen" @click.away="businessOpen = false" x-cloak
                        class="absolute right-0 top-full mt-1 min-w-[210px] bg-[#1a1a1a] border border-gray-700 rounded-xl shadow-2xl z-[100] overflow-hidden py-1">
                        @foreach($businessPresets as $businessKey => $businessPreset)
                            <button type="button" @click="setBusiness('{{ $businessKey }}')"
                                :class="business === '{{ $businessKey }}' ? 'bg-blue-600/20 text-white' : 'text-gray-300 hover:bg-gray-800'"
                                class="w-full flex items-center gap-2 px-4 py-2.5 text-left text-xs font-semibold transition-colors">
                                <span>{{ $businessPreset['icon'] }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate">{{ $businessPreset['label'] }}</span>
                                    @if(!empty($businessPreset['tagline']))
                                        <span class="block truncate text-[8px] font-bold text-gray-500 uppercase tracking-widest">{{ $businessPreset['tagline'] }}</span>
                                    @endif
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- SECURITY CHECK: Only show Directory if they are allowed to see the Index --}}
                @can('view_cgi_index')
                    <a href="{{ route('cgi.index') }}"
                        class="flex-1 sm:flex-none px-5 py-2 bg-gray-800/80 border border-gray-700 text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-all duration-200 font-semibold text-xs shadow-lg text-center backdrop-blur-sm">
                        Directory
                    </a>
                @endcan

                {{-- Universal button (Everyone can exit to Dashboard) --}}
                <a href="{{ route('dashboard') }}"
                    class="flex-1 sm:flex-none px-5 py-2 bg-gray-800/80 border border-gray-700 text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-all duration-200 font-semibold text-xs shadow-lg text-center backdrop-blur-sm">
                    Exit
                </a>

            </div>
        </div>

        {{-- Main Form Container --}}
        <script>window.cgiBusinessPresets = @json($businessPresets);</script>
        <div class="bg-gray-900/60 backdrop-blur-2xl p-5 sm:p-6 rounded-2xl text-white shadow-2xl border border-gray-800/60 relative">

            {{-- Background glow isolated with its own overflow-hidden --}}
            <div class="absolute inset-0 overflow-hidden rounded-2xl pointer-events-none z-0">
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
            </div>

            <form action="{{ route('cgi.store') }}" method="POST" enctype="multipart/form-data" class="relative z-10"
                x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf
                <input type="hidden" name="business_type" id="cgi_business_type" value="{{ old('business_type', 'lighting') }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

                    {{-- Business-specific guidance banner (lighting etc.) --}}
                    <div class="md:col-span-2" x-data="{
                        business: localStorage.getItem('cgi_business') || 'lighting',
                        get preset() { return window.cgiBusinessPresets?.[this.business] || {}; }
                    }"
                    x-init="$nextTick(() => { const b = document.getElementById('cgi_business_type')?.value; if (b) business = b; })"
                    @cgi-business-changed.window="business = $event.detail.business">
                        <div x-show="preset.form_banner" x-cloak
                             class="px-4 py-3 rounded-xl border border-blue-500/20 bg-blue-600/10 text-[10px] text-blue-100/90 leading-relaxed">
                            <span class="font-black uppercase tracking-widest text-blue-400 block mb-1">Director tip</span>
                            <span x-text="preset.form_banner"></span>
                        </div>
                    </div>

                    {{-- 01. The Product (LEFT SIDE) --}}
                    <div x-data="{
                        business: localStorage.getItem('cgi_business') || 'lighting',
                        val: '',
                        get preset() {
                            return window.cgiBusinessPresets?.[this.business] || {};
                        },
                        get suggestions() {
                            return this.preset.product_suggestions || [];
                        },
                        get suggestionGroups() {
                            const items = this.suggestions;
                            if (!items.length || !items[0].category) {
                                return [{ key: 'all', label: null, items }];
                            }
                            const cats = this.preset.usage_categories || {};
                            const groups = {};
                            for (const item of items) {
                                const key = item.category || 'other';
                                if (!groups[key]) groups[key] = [];
                                groups[key].push(item);
                            }
                            const order = Object.keys(cats).length ? Object.keys(cats) : Object.keys(groups);
                            return order.filter(k => groups[k]?.length).map(key => ({
                                key,
                                label: cats[key]?.icon ? cats[key].icon + ' ' + cats[key].label : key,
                                items: groups[key],
                            }));
                        }
                    }"
                    x-init="$nextTick(() => { const b = document.getElementById('cgi_business_type')?.value; if (b) business = b; })"
                    @cgi-business-changed.window="business = $event.detail.business; val = '';"
                    @cgi-autofill-data.window="val = $event.detail.product_name || val">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">01.
                                <span x-text="preset.step01_label || 'Your product name'"></span></label>
                            <div class="cgi-tip-anchor shrink-0">
                                <span class="cursor-help text-gray-500 hover:text-blue-400 transition-colors inline-flex" tabindex="0" role="button" aria-label="Help">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                <div class="cgi-step-tip">
                                    <strong class="text-white block mb-1">What this means:</strong>
                                    <span x-text="preset.step01_guide || 'Type the exact name of what you sell — the same item shown in your photo (Step 02). Scenario: ceiling panel or wall switch name your customer would recognize.'"></span>
                                </div>
                            </div>
                        </div>
                        <x-cgi-text-field
                            name="product_name"
                            model="val"
                            required
                            x-bind:placeholder="preset.step01_placeholder || 'E.g. Product name...'"
                        />

                        <p x-show="preset.step01_example" x-cloak class="mt-1.5 text-[9px] text-gray-500 italic leading-relaxed" x-text="preset.step01_example"></p>

                        <div class="mt-2.5 space-y-2">
                            <template x-for="group in suggestionGroups" :key="group.key">
                                <div>
                                    <p x-show="group.label" class="text-[8px] font-black uppercase tracking-[0.2em] text-gray-500 mb-1.5" x-text="group.label"></p>
                                    <div class="flex gap-1.5 overflow-x-auto pb-1 custom-scrollbar snap-x">
                                        <template x-for="item in group.items" :key="item.value">
                                            <button type="button" @click="val = item.value"
                                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">
                                                <span x-text="item.icon"></span>
                                                <span x-text="' ' + item.label"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- 02. Search & Select Product Upload (RIGHT SIDE) --}}
                    <div x-data="{ 
                        business: localStorage.getItem('cgi_business') || 'lighting',
                        get preset() { return window.cgiBusinessPresets?.[this.business] || {}; },
                        searchQuery: '',
                        imageUrl: null, 
                        selectedAssetPath: null,
                        selectedAssetName: 'Select from Asset Library...',
                        dropdownOpen: false,
                        showUploadModal: false,

                        handleFileUpload(e) {
                            const file = e.target.files[0];
                            if (file) { 
                                this.imageUrl = URL.createObjectURL(file);
                                this.selectedAssetPath = null;
                                this.selectedAssetName = file.name;
                                this.showUploadModal = false; // Close modal automatically
                                this.dropdownOpen = false;
                            }
                        },
                        selectFromLibrary(path, fullUrl, name) {
                            this.imageUrl = fullUrl;
                            this.selectedAssetPath = path;
                            this.selectedAssetName = name;
                            document.getElementById('product_image').value = ''; // Clear file input
                            this.dropdownOpen = false; // Close dropdown
                        }
                    }"
                    @cgi-business-changed.window="business = $event.detail.business;"
                    @cgi-autofill-data.window="
                        if ($event.detail._imagePreview) {
                            imageUrl = $event.detail._imagePreview;
                            selectedAssetPath = $event.detail._assetPath;
                            selectedAssetName = $event.detail._isNew ? 'AI Analyzed Reference' : 'Selected from Library';
                            
                            // If it's a library asset, ensure the main file input is cleared
                            if (!$event.detail._isNew) {
                                document.getElementById('product_image').value = '';
                            }
                        }
                    ">
                        <input type="hidden" name="selected_asset_path" :value="selectedAssetPath">

                        <div class="flex items-center gap-1.5 mb-1.5">
                            <label class="block text-blue-400 text-[9px] font-bold tracking-[0.2em] uppercase">02.
                                <span x-text="preset.step02_label || 'Your product photo'"></span></label>
                            <div class="cgi-tip-anchor cgi-tip-anchor--left shrink-0">
                                <span class="cursor-help text-gray-500 hover:text-blue-400 transition-colors inline-flex" tabindex="0" role="button" aria-label="Help">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </span>
                                <div class="cgi-step-tip">
                                    <strong class="text-white block mb-1">What this means:</strong>
                                    <span x-text="preset.step02_guide || 'Choose or upload the real photo of your product. The AI copies this exact item — shape, color, and branding — in the finished poster.'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Dropdown & Plus Button Row --}}
                        <div class="flex items-center gap-1.5">
                            
                            {{-- Custom Select Dropdown --}}
                            <div class="relative flex-1">
                                <button type="button" @click="dropdownOpen = !dropdownOpen" class="w-full bg-[#111] border border-gray-700/80 rounded-lg p-2 flex items-center justify-between hover:border-blue-500/50 transition-all focus:ring-1 focus:ring-blue-500/50 h-[38px]">
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        {{-- Mini Preview Thumbnail --}}
                                        <div class="w-6 h-6 rounded bg-black/60 border border-gray-800 flex items-center justify-center shrink-0 overflow-hidden">
                                            <template x-if="imageUrl">
                                                <img :src="imageUrl" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!imageUrl">
                                                <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </template>
                                        </div>
                                        <span class="text-[10px] font-bold text-gray-300 truncate" x-text="selectedAssetName"></span>
                                    </div>
                                    <svg class="w-3 h-3 text-gray-500 shrink-0 ml-1 transition-transform" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>

                                {{-- Dropdown Menu Items with Search --}}
                                <div x-show="dropdownOpen" @click.away="dropdownOpen = false" x-cloak class="absolute left-0 right-0 top-full mt-1 bg-[#1a1a1a] border border-gray-700 rounded-lg shadow-2xl z-[70] flex flex-col overflow-hidden">
                                    
                                    {{-- Search Bar inside Dropdown --}}
                                    <div class="p-2 border-b border-gray-800 bg-[#111]">
                                        <div class="relative">
                                            <svg class="w-3 h-3 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                            <input type="text" x-model="searchQuery" placeholder="Search library..." class="w-full bg-[#0a0a0a] border border-gray-700 rounded-md pl-7 pr-2 py-1.5 text-white text-[10px] focus:border-blue-500 outline-none transition-all placeholder-gray-600">
                                        </div>
                                    </div>

                                    {{-- Asset List --}}
                                    <div class="max-h-48 overflow-y-auto custom-scrollbar p-1">
                                        @if(isset($productAssets) && $productAssets->count() > 0)
                                            @foreach($productAssets as $asset)
                                                <button type="button" 
                                                        x-show="String('{{ addslashes($asset->name) }}').toLowerCase().includes(searchQuery.toLowerCase())"
                                                        @click="selectFromLibrary('{{ $asset->file_path }}', '{{ asset('storage/' . $asset->file_path) }}', '{{ addslashes($asset->name) }}')"
                                                        class="w-full flex items-center gap-2 p-1.5 rounded hover:bg-blue-600/20 transition-colors text-left"
                                                        :class="selectedAssetPath === '{{ $asset->file_path }}' ? 'bg-blue-600/20 border border-blue-500/30' : 'border border-transparent'">
                                                    <img src="{{ asset('storage/' . $asset->file_path) }}" class="w-7 h-7 rounded object-cover border border-gray-700 shrink-0 bg-black">
                                                    <span class="text-[10px] font-bold text-gray-200 truncate flex-1">{{ $asset->name }}</span>
                                                    
                                                    {{-- Active Checkmark --}}
                                                    <div x-show="selectedAssetPath === '{{ $asset->file_path }}'" class="shrink-0 text-blue-500 pr-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                    </div>
                                                </button>
                                            @endforeach
                                        @else
                                            <div class="p-3 text-center text-[9px] text-gray-500 font-bold uppercase tracking-widest">Library is Empty</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Plus Button --}}
                            <button type="button" @click="showUploadModal = true" class="shrink-0 h-[38px] w-10 flex items-center justify-center bg-blue-600/20 hover:bg-blue-600/40 border border-blue-500/50 text-blue-400 rounded-lg transition-all shadow-sm hover:shadow-blue-500/20 group">
                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                            </button>
                        </div>

                        {{-- Product reference preview --}}
                        <div x-show="imageUrl" x-cloak x-transition.opacity
                             class="mt-2.5 rounded-xl border border-white/10 bg-black/50 overflow-hidden shadow-inner">
                            <div class="px-2.5 py-1.5 border-b border-white/5 flex items-center justify-between gap-2 bg-white/[0.03]">
                                <span class="text-[8px] font-black uppercase tracking-widest text-blue-400/80 shrink-0">Preview</span>
                                <span class="text-[9px] font-bold text-gray-400 truncate" x-text="selectedAssetName"></span>
                            </div>
                            <button type="button" @click="showUploadModal = true"
                                class="w-full p-3 flex items-center justify-center min-h-[100px] max-h-[168px] bg-[#0a0a0a] hover:bg-[#111] transition-colors group/preview">
                                <img :src="imageUrl" alt="Product reference preview"
                                     class="max-h-[152px] max-w-full object-contain rounded-lg shadow-lg border border-white/5 group-hover/preview:scale-[1.02] transition-transform">
                            </button>
                            <p class="px-2.5 py-1.5 text-[8px] text-gray-600 text-center uppercase tracking-widest font-bold border-t border-white/5">
                                Click preview to change image
                            </p>
                        </div>

                        {{-- CRITICAL FIX: The File Input must stay outside the x-teleport so it submits with the form --}}
                        <input type="file" name="product_image" id="product_image" accept="image/*" class="hidden" @change="handleFileUpload">

                        {{-- MODAL: Upload New Product (Uses x-teleport to escape CSS constraints) --}}
                        <template x-teleport="body">
                            <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-[99999] flex items-center justify-center p-4">
                                
                                {{-- Deep Backdrop --}}
                                <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showUploadModal = false" x-transition.opacity></div>
                                
                                {{-- Modal Box --}}
                                <div class="relative bg-[#0a0a0a] border border-gray-700/60 rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.5)] w-full max-w-sm transform transition-all"
                                     x-show="showUploadModal" 
                                     x-transition:enter="ease-out duration-300" 
                                     x-transition:enter-start="opacity-0 translate-y-8 scale-95" 
                                     x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                                     x-transition:leave="ease-in duration-200" 
                                     x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                                     x-transition:leave-end="opacity-0 translate-y-8 scale-95">
                                    
                                    <div class="flex justify-between items-center p-5 border-b border-gray-800/80">
                                        <h3 class="text-sm font-black text-white uppercase tracking-widest flex items-center gap-2">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                            Upload New Asset
                                        </h3>
                                        <button type="button" @click="showUploadModal = false" class="text-gray-500 hover:text-white transition-colors bg-gray-800/50 hover:bg-gray-700 rounded-lg p-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>

                                    <div class="p-6">
                                        <div class="relative">
                                            <label for="product_image" class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-gray-700 hover:border-blue-500/50 rounded-xl bg-[#111] cursor-pointer transition-all group overflow-hidden relative">
                                                
                                                <template x-if="!imageUrl || selectedAssetPath">
                                                    <div class="flex flex-col items-center">
                                                        <div class="p-3 bg-gray-800/50 rounded-full mb-3 group-hover:bg-blue-900/30 group-hover:text-blue-400 transition-colors">
                                                            <svg class="w-8 h-8 text-gray-500 group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                        </div>
                                                        <span class="text-xs font-black text-gray-400 uppercase tracking-widest group-hover:text-blue-400">Click to Browse</span>
                                                        <span class="text-[9px] text-gray-600 mt-2 font-medium uppercase tracking-wider">JPEG, PNG, WEBP up to 5MB</span>
                                                    </div>
                                                </template>
                                                
                                                <template x-if="imageUrl && !selectedAssetPath">
                                                    <div class="absolute inset-0 p-2">
                                                        <img :src="imageUrl" class="w-full h-full object-contain rounded-lg">
                                                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-lg m-2">
                                                            <span class="text-[10px] font-black text-white uppercase tracking-widest bg-blue-600 px-4 py-2 rounded-lg shadow-lg">Change File</span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </label>
                                        </div>
                                        <p class="text-[9px] text-gray-500 mt-4 text-center uppercase tracking-widest font-bold">Image will auto-save to your asset library</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- 03. The Feeling --}}
                    <div x-data="{
                        business: localStorage.getItem('cgi_business') || 'lighting',
                        val: '',
                        get preset() {
                            return window.cgiBusinessPresets?.[this.business] || {};
                        },
                        get chips() {
                            return this.preset.marketing_chips || [];
                        },
                        get chipGroups() {
                            const items = this.chips;
                            if (!items.length || !items[0].category) {
                                return [{ key: 'all', label: null, items }];
                            }
                            const cats = this.preset.usage_categories || {};
                            const groups = {};
                            for (const item of items) {
                                const key = item.category || 'other';
                                if (!groups[key]) groups[key] = [];
                                groups[key].push(item);
                            }
                            const order = Object.keys(cats).length ? Object.keys(cats) : Object.keys(groups);
                            return order.filter(k => groups[k]?.length).map(key => ({
                                key,
                                label: cats[key]?.icon ? cats[key].icon + ' ' + cats[key].label : key,
                                items: groups[key],
                            }));
                        },
                        toggle(word) {
                            let items = this.val ? this.val.split(', ').filter(i => i) : [];
                            if (items.includes(word)) { items = items.filter(i => i !== word); } else { items.push(word); }
                            this.val = items.join(', ');
                        },
                        isActive(chip) {
                            const needle = chip.match || chip.value;
                            return (this.val || '').includes(needle);
                        }
                    }"
                    x-init="$nextTick(() => { const b = document.getElementById('cgi_business_type')?.value; if (b) business = b; })"
                    @cgi-business-changed.window="business = $event.detail.business; val = '';"
                    @cgi-autofill-data.window="val = $event.detail.marketing_angle || val">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">03.
                                <span x-text="preset.step03_label || 'Marketing headline'"></span></label>
                            <div class="cgi-tip-anchor shrink-0">
                                <span class="cursor-help text-gray-500 hover:text-blue-400 transition-colors inline-flex" tabindex="0" role="button" aria-label="Help">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                <div class="cgi-step-tip">
                                    <strong class="text-white block mb-1">Guidance</strong>
                                    <span x-text="preset.step03_guide || 'The bold selling message on your advertisement — 2 to 3 short benefit phrases. This headline sits in the poster layout, not on the product photo.'"></span>
                                </div>
                            </div>
                        </div>
                        <x-cgi-text-field
                            name="marketing_angle"
                            model="val"
                            required
                            x-bind:placeholder="preset.step03_placeholder || 'E.g. BRIGHT UNIFORM LIGHT · ENERGY SAVING · MODERN DESIGN'"
                        />

                        <p x-show="preset.step03_example" x-cloak class="mt-1.5 text-[9px] text-gray-500 italic leading-relaxed" x-text="preset.step03_example"></p>

                        <div class="mt-2.5 space-y-2">
                            <template x-for="group in chipGroups" :key="group.key">
                                <div>
                                    <p x-show="group.label" class="text-[8px] font-black uppercase tracking-[0.2em] text-gray-500 mb-1.5" x-text="group.label"></p>
                                    <div class="flex gap-1.5 overflow-x-auto pb-1 custom-scrollbar snap-x">
                                        <template x-for="chip in group.items" :key="chip.value">
                                            <button type="button" @click="toggle(chip.value)"
                                                :class="isActive(chip) ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">
                                                <span x-text="chip.icon"></span>
                                                <span x-text="' ' + chip.label"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- 04. Product Usage (manual input + searchable presets, drives 05) --}}
                    <div x-data="{
                        business: localStorage.getItem('cgi_business') || 'lighting',
                        usage: '',
                        usageLabel: '',
                        selectedUsageId: '',
                        search: '',
                        open: false,
                        get options() {
                            return window.cgiBusinessPresets?.[this.business]?.usage_options || [];
                        },
                        get preset() {
                            return window.cgiBusinessPresets?.[this.business] || {};
                        },
                        capBackgrounds(backgrounds) {
                            return backgrounds || [];
                        },
                        emitUsageChange(backgrounds, autoVal = null) {
                            const bgs = this.capBackgrounds(backgrounds);
                            const detail = { backgrounds: bgs };
                            if (autoVal !== null) detail.autoVal = autoVal;
                            $dispatch('usage-changed', detail);
                        },
                        get categoryKeys() {
                            const cats = this.preset.usage_categories || {};
                            const keys = Object.keys(cats);
                            if (!keys.length) return ['all'];
                            const present = new Set(this.filteredOptions.map(o => o.category || 'other'));
                            return keys.filter(k => present.has(k));
                        },
                        categoryLabel(key) {
                            const cat = (this.preset.usage_categories || {})[key];
                            if (!cat) return key;
                            return (cat.icon ? cat.icon + ' ' : '') + (cat.label || key);
                        },
                        optionMatchesSearch(opt) {
                            const q = (this.search || '').toLowerCase().trim();
                            if (!q) return true;
                            const hay = [opt.label, opt.id, opt.val, ...(opt.keywords || [])].join(' ').toLowerCase();
                            return hay.includes(q);
                        },
                        optionsInCategory(key) {
                            if (key === 'all') return this.filteredOptions;
                            return this.filteredOptions.filter(o => (o.category || 'other') === key);
                        },
                        get filteredOptions() {
                            return this.options.filter(o => this.optionMatchesSearch(o));
                        },
                        resetUsage() {
                            this.usage = '';
                            this.usageLabel = '';
                            this.selectedUsageId = '';
                            this.search = '';
                            this.open = false;
                            this.emitUsageChange([], '');
                        },
                        pick(opt) {
                            this.usage = opt.val;
                            this.usageLabel = opt.icon + '  ' + opt.label;
                            this.selectedUsageId = opt.id || '';
                            this.open = false;
                            this.search = '';
                            const bgs = this.capBackgrounds(opt.backgrounds || []);
                            const autoVal = bgs[0]?.val || '';
                            this.emitUsageChange(bgs, autoVal);
                        },
                        syncFromInput() {
                            const trimmed = (this.usage || '').trim();
                            if (!trimmed) {
                                this.usageLabel = '';
                                this.selectedUsageId = '';
                                this.emitUsageChange([], '');
                                return;
                            }
                            const exact = this.options.find(o => o.val === trimmed);
                            if (exact) {
                                this.usageLabel = exact.icon + '  ' + exact.label;
                                this.selectedUsageId = exact.id || '';
                                const bgs = this.capBackgrounds(exact.backgrounds || []);
                                this.emitUsageChange(bgs, bgs[0]?.val || '');
                                return;
                            }
                            this.usageLabel = '';
                            this.selectedUsageId = '';
                            let best = null, bestScore = 0;
                            for (const opt of this.options) {
                                const s = this.scoreUsage(opt, trimmed, document.querySelector('[name=product_name]')?.value || '');
                                if (s > bestScore) { bestScore = s; best = opt; }
                            }
                            if (bestScore >= 55 && best) {
                                this.selectedUsageId = best.id || '';
                                const bgs = this.capBackgrounds(best.backgrounds || []);
                                this.emitUsageChange(bgs, bgs[0]?.val || '');
                            } else {
                                this.emitUsageChange([]);
                            }
                        },
                        scoreUsage(opt, hint, productName) {
                            const t = (hint || '').toLowerCase();
                            const p = (productName || '').toLowerCase();
                            const id = (opt.id || '').toLowerCase();
                            const val = (opt.val || '').toLowerCase();
                            let score = 0;
                            if (t && (t === val || t === id)) score = 100;
                            else if (t && (val.includes(t) || t.includes(val.slice(0, 20)))) score = 70;
                            else if (t && id && t.includes(id)) score = 60;
                            const kws = opt.keywords || [];
                            for (const kw of kws) {
                                if ((t && t.includes(kw)) || (p && p.includes(kw))) score = Math.max(score, 55);
                            }
                            return score;
                        },
                        matchUsageFromDetail(detail) {
                            const hint = detail.visual_prop || '';
                            const productName = detail.product_name || '';
                            let best = null, bestScore = 0;
                            for (const opt of this.options) {
                                const s = this.scoreUsage(opt, hint, productName);
                                if (s > bestScore) { bestScore = s; best = opt; }
                            }
                            return bestScore >= 30 ? best : null;
                        },
                        matchBackground(hint, backgrounds) {
                            if (!backgrounds || !backgrounds.length) return null;
                            const t = (hint || '').toLowerCase().trim();
                            if (!t) return null;
                            let best = null, bestScore = 0;
                            for (const bg of backgrounds) {
                                const val = (bg.val || '').toLowerCase();
                                const label = (bg.label || '').toLowerCase();
                                let score = 0;
                                if (t === val) score = 100;
                                else if (val.includes(t) || t.includes(val)) score = 80;
                                else if (label && (t.includes(label.replace(/[^\w\s]/g, '').trim()) || label.includes(t))) score = 55;
                                else {
                                    const words = t.split(/\s+/).filter(w => w.length > 3);
                                    for (const w of words) {
                                        if (val.includes(w) || label.includes(w)) score += 8;
                                    }
                                }
                                if (score > bestScore) { bestScore = score; best = bg; }
                            }
                            return bestScore >= 20 ? best : null;
                        },
                        resolveBackgroundVal(hint, backgrounds) {
                            const trimmed = (hint || '').trim();
                            if (!backgrounds || !backgrounds.length) return trimmed;
                            const matched = this.matchBackground(trimmed, backgrounds);
                            if (matched) return matched.val;
                            if (trimmed) return trimmed;
                            return backgrounds[0]?.val || '';
                        }
                    }"
                    x-init="$nextTick(() => { const b = document.getElementById('cgi_business_type')?.value; if (b) business = b; usage = @js(old('visual_prop', '')); if (usage) syncFromInput(); })"
                    @cgi-business-changed.window="business = $event.detail.business; resetUsage(); $dispatch('cgi-autofill-background', { backgrounds: [], val: '' });"
                    @cgi-autofill-reset.window="resetUsage();"
                    @cgi-autofill-data.window="
                        const opt = matchUsageFromDetail($event.detail);
                        if (opt) {
                            pick(opt);
                            const bgs = capBackgrounds(opt.backgrounds || []);
                            const bgVal = resolveBackgroundVal($event.detail.atmosphere || '', bgs);
                            $dispatch('cgi-autofill-background', { backgrounds: bgs, val: bgVal });
                        } else {
                            usage = ($event.detail.visual_prop || '').trim();
                            usageLabel = '';
                            selectedUsageId = '';
                            syncFromInput();
                            const atmOnly = ($event.detail.atmosphere || '').trim();
                            $dispatch('cgi-autofill-background', { backgrounds: [], val: atmOnly });
                        }
                    ">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">04.
                                <span x-text="preset.step04_label || 'How it is used in real life'"></span></label>
                            <div class="cgi-tip-anchor shrink-0">
                                <span class="cursor-help text-gray-500 hover:text-blue-400 transition-colors inline-flex" tabindex="0" role="button" aria-label="Help">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                <div class="cgi-step-tip">
                                    <strong class="text-white block mb-1">What this means:</strong>
                                    <span x-text="preset.step04_guide || 'Where your product goes and that it is working (switched ON). Pick from the list or type your own — Step 05 will suggest matching rooms.'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Manual input (always available) — submitted value --}}
                        <x-cgi-text-field
                            name="visual_prop"
                            model="usage"
                            required
                            @input="syncFromInput()"
                            x-bind:placeholder="preset.step04_manual_placeholder || 'Type custom usage, or pick a preset below...'"
                        />

                        <p x-show="preset.step04_example" x-cloak class="mt-1.5 text-[9px] text-gray-500 italic leading-relaxed" x-text="preset.step04_example"></p>

                        <p x-show="usageLabel" x-cloak class="mt-2 text-[9px] text-blue-400/80 font-bold uppercase tracking-widest truncate" x-text="usageLabel"></p>

                        {{-- Searchable preset picker --}}
                        <div class="relative mt-2">
                            <button type="button" @click="open = !open"
                                class="w-full bg-gray-800/40 border border-gray-700/80 rounded-lg p-2 flex items-center justify-between hover:border-blue-500/50 transition-all focus:ring-1 focus:ring-blue-500/50">
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-400"
                                    x-text="preset.step04_dropdown_label || 'Browse common examples (optional)'"></span>
                                <svg class="w-3 h-3 text-gray-500 shrink-0 ml-1 transition-transform"
                                    :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-cloak
                                class="absolute left-0 right-0 top-full mt-1 bg-[#1a1a1a] border border-gray-700 rounded-xl shadow-2xl z-[70] flex flex-col overflow-hidden">
                                <div class="p-2 border-b border-gray-800 bg-[#111]">
                                    <div class="relative">
                                        <svg class="w-3 h-3 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <input type="text" x-model="search" :placeholder="preset.step04_search_placeholder || 'Search usage...'"
                                            class="w-full bg-[#0a0a0a] border border-gray-700 rounded-md pl-7 pr-2 py-1.5 text-white text-[10px] focus:border-blue-500 outline-none transition-all placeholder-gray-600">
                                    </div>
                                </div>
                                <div class="max-h-56 overflow-y-auto custom-scrollbar p-1">
                                    <template x-if="filteredOptions.length === 0">
                                        <p class="p-3 text-center text-[9px] text-gray-500 font-bold uppercase tracking-widest">No presets match — use your custom text above</p>
                                    </template>
                                    <template x-for="catKey in categoryKeys" :key="catKey">
                                        <div x-show="optionsInCategory(catKey).length > 0">
                                            <p x-show="categoryKeys.length > 1 && catKey !== 'all'" class="px-2 pt-2 pb-1 text-[8px] font-black uppercase tracking-[0.2em] text-gray-500 sticky top-0 bg-[#1a1a1a]" x-text="categoryLabel(catKey)"></p>
                                            <template x-for="opt in optionsInCategory(catKey)" :key="opt.id">
                                                <button type="button"
                                                    @click="pick(opt)"
                                                    :class="selectedUsageId === opt.id ? 'bg-blue-600/20 border border-blue-500/30' : 'border border-transparent'"
                                                    class="w-full flex items-center gap-2 p-2 rounded hover:bg-blue-600/20 transition-colors text-left">
                                                    <span class="text-base leading-none shrink-0" x-text="opt.icon"></span>
                                                    <span class="text-[10px] font-bold text-gray-200 truncate flex-1" x-text="opt.label"></span>
                                                    <div x-show="selectedUsageId === opt.id" class="shrink-0 text-blue-500 pr-1">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <p class="mt-2 text-[9px] text-gray-500 font-bold uppercase tracking-widest"
                            x-text="preset.step04_helper || 'Pick an example below, or type your own above — Step 05 will suggest matching rooms.'">
                        </p>
                    </div>

                    {{-- 05. Background & 06. Movement --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-6 md:col-span-1">
                        {{-- 05. Background (suggestions depend on step 04) --}}
                        <div x-data="{
                            business: localStorage.getItem('cgi_business') || 'lighting',
                            val: '',
                            options: [],
                            sceneOpen: false,
                            sceneIndex: -1,
                            get preset() { return window.cgiBusinessPresets?.[this.business] || {}; },
                            syncSceneIndex() {
                                const idx = this.options.findIndex(o => o.val === this.val);
                                this.sceneIndex = idx >= 0 ? idx : (this.options.length ? 0 : -1);
                            },
                            selectedSceneLabel() {
                                const match = this.options.find(o => o.val === this.val);
                                return match?.label || '';
                            },
                            selectScene(bg) {
                                this.val = bg.val;
                                this.sceneOpen = false;
                                this.sceneIndex = this.options.findIndex(o => o.val === bg.val);
                            },
                            toggleSceneDropdown() {
                                this.sceneOpen = !this.sceneOpen;
                                if (this.sceneOpen) this.syncSceneIndex();
                            },
                            onSceneKeydown(e) {
                                if (!this.options.length) return;
                                if (!this.sceneOpen && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
                                    e.preventDefault();
                                    this.sceneOpen = true;
                                    this.syncSceneIndex();
                                    return;
                                }
                                if (!this.sceneOpen) return;
                                if (e.key === 'ArrowDown') {
                                    e.preventDefault();
                                    this.sceneIndex = Math.min(this.sceneIndex + 1, this.options.length - 1);
                                    this.$refs.sceneList?.children[this.sceneIndex]?.scrollIntoView({ block: 'nearest' });
                                } else if (e.key === 'ArrowUp') {
                                    e.preventDefault();
                                    this.sceneIndex = Math.max(this.sceneIndex - 1, 0);
                                    this.$refs.sceneList?.children[this.sceneIndex]?.scrollIntoView({ block: 'nearest' });
                                } else if (e.key === 'Enter' && this.sceneIndex >= 0) {
                                    e.preventDefault();
                                    this.selectScene(this.options[this.sceneIndex]);
                                } else if (e.key === 'Escape') {
                                    this.sceneOpen = false;
                                }
                            }
                        }"
                        x-init="$nextTick(() => { const b = document.getElementById('cgi_business_type')?.value; if (b) business = b; })"
                        @cgi-business-changed.window="business = $event.detail.business; sceneOpen = false; sceneIndex = -1;"
                        @usage-changed.window="options = $event.detail.backgrounds || []; sceneOpen = false; if ($event.detail.autoVal !== undefined) val = $event.detail.autoVal; syncSceneIndex();"
                           @cgi-autofill-reset.window="val = ''; options = []; sceneOpen = false; sceneIndex = -1;"
                           @cgi-autofill-background.window="options = $event.detail.backgrounds || []; val = $event.detail.val || ''; syncSceneIndex();">
                            <div class="flex items-center gap-2 mb-2">
                                <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">05.
                                    <span x-text="preset.step05_label || 'Which room or place'"></span></label>
                                <div class="cgi-tip-anchor shrink-0">
                                    <span class="cursor-help text-gray-500 hover:text-blue-400 transition-colors inline-flex" tabindex="0" role="button" aria-label="Help">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </span>
                                    <div class="cgi-step-tip">
                                        <strong class="text-white block mb-1">What this means:</strong>
                                        <span x-text="preset.step05_guide || 'The background scene — where your product is shown in real life. Pick from the dropdown after Step 04, or type your own room or place.'"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Manual input (always available) — this is the submitted value --}}
                            <x-cgi-text-field
                                name="atmosphere"
                                model="val"
                                required
                                x-bind:placeholder="preset.step05_placeholder || 'Type a custom scene, or pick a suggestion below...'"
                            />

                            <p x-show="preset.step05_example" x-cloak class="mt-1.5 text-[9px] text-gray-500 italic leading-relaxed" x-text="preset.step05_example"></p>

                            {{-- Keyboard-navigable scene dropdown (↑ ↓ Enter) --}}
                            <div x-show="options.length > 0" x-cloak class="relative mt-2 outline-none" tabindex="0" @keydown="onSceneKeydown($event)">
                                <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1.5"
                                    x-text="preset.step05_suggestions_label || 'Suggested scenes'"></p>
                                <button type="button"
                                    @click="toggleSceneDropdown()"
                                    class="w-full bg-gray-800/40 border border-gray-700/80 rounded-lg px-3 py-2 flex items-center justify-between hover:border-blue-500/50 transition-all focus:ring-1 focus:ring-blue-500/50 text-left"
                                    :class="sceneOpen ? 'border-blue-500/50 ring-1 ring-blue-500/30' : ''">
                                    <span class="text-[10px] font-bold truncate"
                                        :class="selectedSceneLabel() ? 'text-gray-200' : 'text-gray-500'"
                                        x-text="selectedSceneLabel() || (preset.step05_dropdown_placeholder || 'Select suggested scene...')"></span>
                                    <svg class="w-3 h-3 text-gray-500 shrink-0 ml-2 transition-transform"
                                        :class="sceneOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <p class="mt-1 text-[8px] text-gray-600 font-bold uppercase tracking-widest">↑ ↓ to browse · Enter to select</p>

                                <div x-show="sceneOpen" @click.away="sceneOpen = false" x-cloak
                                    class="absolute left-0 right-0 top-full mt-1 bg-[#1a1a1a] border border-gray-700 rounded-xl shadow-2xl z-[70] overflow-hidden">
                                    <div x-ref="sceneList" class="max-h-48 overflow-y-auto custom-scrollbar p-1">
                                        <template x-for="(bg, idx) in options" :key="bg.val">
                                            <button type="button"
                                                @click="selectScene(bg)"
                                                :class="(sceneOpen && sceneIndex === idx) || val === bg.val ? 'bg-blue-600/20 border border-blue-500/30 text-white' : 'border border-transparent text-gray-300'"
                                                class="w-full flex items-center gap-2 px-3 py-2 rounded text-left text-[10px] font-bold transition-colors hover:bg-blue-600/10">
                                                <span class="truncate flex-1" x-text="bg.label"></span>
                                                <svg x-show="val === bg.val" class="w-3.5 h-3.5 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Hint shown until a usage is chosen in step 04 --}}
                            <p x-show="options.length === 0"
                                class="mt-2 text-[9px] text-gray-500 font-bold uppercase tracking-widest"
                                x-text="preset.step05_helper || 'Fill in Step 04 first — matching room examples appear in the dropdown below.'">
                            </p>
                        </div>

                        {{-- 06. Movement --}}
                        <div x-data="{
                            business: localStorage.getItem('cgi_business') || 'lighting',
                            val: '',
                            get preset() { return window.cgiBusinessPresets?.[this.business] || {}; }
                        }"
                        x-init="$nextTick(() => { const b = document.getElementById('cgi_business_type')?.value; if (b) business = b; })"
                        @cgi-business-changed.window="business = $event.detail.business;"
                        @cgi-autofill-data.window="val = $event.detail.camera_motion || $event.detail.movement || $event.detail.camera || val">
                            <div class="flex items-center gap-2 mb-2">
                                <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">06.
                                    <span x-text="preset.step06_label || 'Video camera movement'"></span></label>
                                <div class="cgi-tip-anchor cgi-tip-anchor--left shrink-0">
                                    <span class="cursor-help text-gray-500 hover:text-blue-400 transition-colors inline-flex" tabindex="0" role="button" aria-label="Help">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </span>
                                    <div class="cgi-step-tip">
                                        <strong class="text-white block mb-1">What this means:</strong>
                                        <span x-text="preset.step06_guide || 'Only for video ads — how the camera should move (slow zoom, gentle pan, etc.). For a still poster, a soft slow zoom works well.'"></span>
                                    </div>
                                </div>
                            </div>
                            <x-cgi-text-field
                                name="camera_motion"
                                model="val"
                                required
                                x-bind:placeholder="preset.step06_placeholder || 'Type custom camera or select...'"
                            />

                            <select x-model="val"
                                class="w-full mt-2 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-300 p-1.5 outline-none font-bold uppercase tracking-widest cursor-pointer">
                                <option value="">-- Quick Select --</option>
                                <option value="Elegant 360 degree slow orbit">🔄 Slow Orbit</option>
                                <option value="Extreme Fast Zoom">🔍 Fast Zoom</option>
                                <option value="Cinematic Macro Slide">🎞️ Macro Slide</option>
                                <option value="Spinning Glitch Zoom">🌀 Glitch Spin</option>
                                <option value="Dolly Push Reveal">🚶 Dolly Push</option>
                                <option value="Drone Landscape Sweep">🚁 Drone Sweep</option>
                                <option value="High Speed FPV Action Dive">🛸 FPV Action Dive</option>
                                <option value="Slow Motion Bullet Time">⏳ Bullet Time</option>
                                <option value="Fast Whip Pan Transition">⚡ Whip Pan</option>
                                <option value="Cinematic Tracking Crane Shot">🎥 Crane Shot</option>
                                <option value="Dramatic Dutch Angle Tilt">📐 Dutch Angle</option>
                                <option value="Top-Down Spiral Rotation">🦅 Top-Down Spiral</option>
                            </select>
                        </div>
                    </div>

                    {{-- 07. Layout --}}
                    <div class="md:col-span-1" x-data="{
                        business: localStorage.getItem('cgi_business') || 'lighting',
                        comp: '',
                        get preset() { return window.cgiBusinessPresets?.[this.business] || {}; }
                    }"
                    x-init="$nextTick(() => { const b = document.getElementById('cgi_business_type')?.value; if (b) business = b; })"
                    @cgi-business-changed.window="business = $event.detail.business; comp = '';"
                    @cgi-autofill-data.window="comp = $event.detail.composition || $event.detail.layout || $event.detail.position || comp">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">07.
                                <span x-text="preset.step07_label || 'Where product sits on poster'"></span></label>
                            <div class="cgi-tip-anchor cgi-tip-anchor--left shrink-0">
                                <span class="cursor-help text-gray-500 hover:text-blue-400 transition-colors inline-flex" tabindex="0" role="button" aria-label="Help">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                <div class="cgi-step-tip">
                                    <strong class="text-white block mb-1">What this means:</strong>
                                    <span x-text="preset.step07_guide || 'Where your product photo appears on the poster — usually bottom-left or bottom-right so the top has space for your marketing headline.'"></span>
                                </div>
                            </div>
                        </div>

                        <x-cgi-text-field
                            name="composition"
                            model="comp"
                            required
                            class="mb-2.5"
                            x-bind:placeholder="preset.step07_placeholder || 'Type custom layout or select below...'"
                        />

                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            <div @click="comp = 'Product on far left side. Negative space on right.'"
                                :class="comp.includes('far left') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Far Left</h4>
                            </div>
                            <div @click="comp = 'Product on the right side. Space on left for text.'"
                                :class="comp.includes('right side') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Far Right</h4>
                            </div>
                            <div @click="comp = 'Symmetrical centered product. Perfectly balanced.'"
                                :class="comp.includes('centered') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Centered</h4>
                            </div>
                            <div @click="comp = 'Product at bottom center looking up.'"
                                :class="comp.includes('bottom center') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Hero Bottom</h4>
                            </div>
                            <div @click="comp = 'Extreme close up of product corner.'"
                                :class="comp.includes('close up') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Macro</h4>
                            </div>
                            <div @click="comp = 'Top-down flat lay view of product.'"
                                :class="comp.includes('Top-down') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Top Down</h4>
                            </div>
                            <div @click="comp = 'Product floating at a 45 degree angle.'"
                                :class="comp.includes('45 degree') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Dyn Tilt</h4>
                            </div>
                            <div @click="comp = 'Product in foreground, decoration in background.'"
                                :class="comp.includes('foreground') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Depth Mix</h4>
                            </div>
                            <div @click="comp = 'Split screen composition. Product left, graphics right.'"
                                :class="comp.includes('Split') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Split 50/50</h4>
                            </div>
                            <div @click="comp = 'Framed centrally with a natural border of props.'"
                                :class="comp.includes('Framed') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Framed</h4>
                            </div>
                            <div @click="comp = 'Placed specifically on the lower right rule of thirds intersection.'"
                                :class="comp.includes('thirds') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Rule of 3rd</h4>
                            </div>
                            <div @click="comp = 'Extreme low angle, making product look massive.'"
                                :class="comp.includes('Extreme low') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-2 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center flex flex-col justify-center min-h-[40px]">
                                <h4 class="font-bold text-[8px] uppercase tracking-wider">Low Angle</h4>
                            </div>
                        </div>
                    </div>

                    {{-- 08. Lighting --}}
                    <div class="md:col-span-1" x-data="{
                        business: localStorage.getItem('cgi_business') || 'lighting',
                        light: '',
                        get preset() { return window.cgiBusinessPresets?.[this.business] || {}; },
                        get lightingChips() { return this.preset.lighting_style_chips || []; }
                    }"
                    x-init="$nextTick(() => { const b = document.getElementById('cgi_business_type')?.value; if (b) business = b; })"
                    @cgi-business-changed.window="business = $event.detail.business; light = '';"
                    @cgi-autofill-data.window="light = $event.detail.lighting_style || $event.detail.lighting || $event.detail.light || light">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">08.
                                <span x-text="preset.step08_label || 'Light mood & colors'"></span></label>
                            <div class="cgi-tip-anchor shrink-0">
                                <span class="cursor-help text-gray-500 hover:text-blue-400 transition-colors inline-flex" tabindex="0" role="button" aria-label="Help">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                                <div class="cgi-step-tip">
                                    <strong class="text-white block mb-1">What this means:</strong>
                                    <span x-text="preset.step08_guide || 'The feeling of light in your poster — warm and cozy, bright daylight, dark dramatic night, etc. Describe mood and glow from your product only.'"></span>
                                </div>
                            </div>
                        </div>

                        <x-cgi-text-field
                            name="lighting_style"
                            model="light"
                            required
                            class="mb-2.5"
                            x-bind:placeholder="preset.step08_placeholder || 'E.g. Deep cinematic darkness, soft ambient rim lighting, radiant hero product.'"
                        />

                        <p x-show="preset.step08_example" x-cloak class="mt-1.5 mb-2 text-[9px] text-gray-500 italic leading-relaxed" x-text="preset.step08_example"></p>

                        <div x-show="lightingChips.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-2">
                            <template x-for="chip in lightingChips" :key="chip.value">
                                <button type="button" @click="light = chip.value"
                                    :class="light === chip.value ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                    class="p-1 border rounded-lg hover:border-blue-500/50 transition-all text-center">
                                    <span class="text-sm block mb-0.5" x-text="chip.icon"></span>
                                    <h4 class="text-[8px] font-bold uppercase tracking-wider" x-text="chip.label"></h4>
                                </button>
                            </template>
                        </div>

                        <div x-show="lightingChips.length === 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            <div @click="light = 'Warm amber wash, soft natural fill, radiant hero product.'"
                                :class="light.includes('Warm amber') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🔥</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Warm White</h4>
                            </div>
                            <div @click="light = 'Cool daylight clarity, crisp clean contrast, luminous hero product.'"
                                :class="light.includes('Cool daylight') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">☀️</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Daylight</h4>
                            </div>
                            <div @click="light = 'Bright even illumination, shadowless clarity, fully visible hero product.'"
                                :class="light.includes('shadowless') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🔆</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Uniform</h4>
                            </div>
                            <div @click="light = 'Soft ambient interior glow, gentle relaxing mood, radiant hero product.'"
                                :class="light.includes('Soft ambient') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">💡</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Soft Ambient</h4>
                            </div>
                            <div @click="light = 'Golden hour warmth, sunset rim light, beautifully radiant hero product.'"
                                :class="light.includes('Golden hour') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🌅</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Evening</h4>
                            </div>
                            <div @click="light = 'Warm festive sparkle, cozy celebration glow, radiant hero product.'"
                                :class="light.includes('festive') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">✨</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Festive</h4>
                            </div>
                            <div @click="light = 'Clean minimal studio light, bright neutral tone, sharp hero product.'"
                                :class="light.includes('minimal studio') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">⚪</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Clean</h4>
                            </div>
                            <div @click="light = 'Deep cinematic darkness, soft ambient rim lighting, radiant hero product.'"
                                :class="light.includes('cinematic darkness') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🌙</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Night Glow</h4>
                            </div>
                            <div @click="light = 'Golden dining ambience, warm overhead wash, softly radiant hero product.'"
                                :class="light.includes('dining ambience') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🍽️</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Dining</h4>
                            </div>
                            <div @click="light = 'Focused accent beam, dramatic contrast, spotlit hero product.'"
                                :class="light.includes('accent beam') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">💎</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Accent</h4>
                            </div>
                            <div @click="light = 'Clean commercial brightness, professional neutral tone, sharp hero product.'"
                                :class="light.includes('commercial brightness') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🏬</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Commercial</h4>
                            </div>
                            <div @click="light = 'Cozy warm interior glow, soft wall reflections, inviting hero product.'"
                                :class="light.includes('Cozy warm') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🏡</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Cozy Home</h4>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Action Section --}}
                <div class="pt-6 border-t border-gray-800/80 mt-6 flex flex-col items-center gap-4">

                    {{-- NEW PHP LOGIC: Check UserPackage instead of the old model --}}
                    @php
                        $activeWallet = \App\Models\UserPackage::where('user_id', auth()->id())
                            ->where('is_active_selection', 'true')
                            ->where(function($q) {
                                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            })
                            ->first();
                            
                        $hasCredits = $activeWallet && $activeWallet->directive_credits >= 1;
                        $isAdmin = auth()->user()->role === 'admin';
                    @endphp

                    @if($isAdmin || $hasCredits)
                        <button type="submit" :disabled="isSubmitting"
                            class="relative w-full max-w-sm group overflow-hidden py-3 rounded-xl transition-all duration-500 border border-zinc-700/50 bg-zinc-950 hover:border-blue-500/50 hover:shadow-[0_0_20px_rgba(37,99,235,0.2)] disabled:opacity-50 disabled:cursor-not-allowed">

                            <div
                                class="absolute inset-0 bg-gradient-to-r from-transparent via-blue-500/10 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000">
                            </div>

                            <div class="relative flex items-center justify-center gap-3">
                                <div
                                    class="p-1.5 bg-zinc-900 border border-zinc-800 rounded group-hover:border-blue-500/50 transition-colors">
                                    <svg x-show="!isSubmitting" class="w-3.5 h-3.5 text-blue-500" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    {{-- Spinner SVG --}}
                                    <svg x-show="isSubmitting" x-cloak class="w-3.5 h-3.5 animate-spin text-blue-400"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </div>

                                <div class="text-left">
                                    <span
                                        class="block text-[8px] font-bold text-gray-500 tracking-[0.3em] uppercase group-hover:text-blue-400 transition-colors leading-none mb-0.5">
                                        {{ $isAdmin ? 'Admin Unlimited' : 'System Ready • 1 Credit' }}
                                    </span>
                                    <span x-text="isSubmitting ? 'Processing...' : 'Launch Pipeline'"
                                        class="block text-white font-black tracking-widest uppercase text-xs leading-none"></span>
                                </div>
                            </div>
                        </button>
                    @else
                        {{-- LOCKED STATE: Only shows for Users with 0 credits --}}
                        <button type="button" disabled
                            class="relative w-full max-w-sm group overflow-hidden py-3 rounded-xl border border-red-900/20 bg-zinc-950/50 cursor-not-allowed">

                            <div class="relative flex items-center justify-center gap-3">
                                <div class="p-1.5 bg-zinc-900 border border-red-900/30 rounded">
                                    <svg class="w-3.5 h-3.5 text-red-500/50" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                </div>

                                <div class="text-left">
                                    <span
                                        class="block text-[8px] font-bold text-red-500/60 tracking-[0.3em] uppercase leading-none mb-0.5">
                                        Insufficient Credits
                                    </span>
                                    <span
                                        class="block text-zinc-600 font-black tracking-widest uppercase text-xs leading-none">
                                        Upgrade to Launch
                                    </span>
                                </div>
                            </div>
                        </button>
                    @endif

                    <div class="flex items-center gap-3 w-full max-w-[200px]">
                        <div class="h-px flex-1 bg-gradient-to-r from-transparent to-gray-800"></div>
                        <p class="text-[9px] tracking-[0.3em] text-gray-600 font-medium leading-none ">
                            Powered by <span class="text-gray-400 font-black">eGeneration</span>
                        </p>
                        <div class="h-px flex-1 bg-gradient-to-l from-transparent to-gray-800"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.cgiGrowField = function (el) {
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = Math.max(el.scrollHeight, 42) + 'px';
        };
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #3f3f46;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #3b82f6;
        }

        .cgi-tip-anchor {
            position: relative;
            display: inline-flex;
        }

        .cgi-step-tip {
            display: none;
            position: absolute;
            z-index: 80;
            pointer-events: none;
            width: min(18rem, calc(100vw - 2rem));
            padding: 0.75rem;
            background: rgb(31 41 55);
            border: 1px solid rgb(55 65 81);
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            font-size: 10px;
            line-height: 1.625;
            color: rgb(209 213 219);
            top: 50%;
            left: calc(100% + 0.5rem);
            transform: translateY(-50%);
        }

        .cgi-tip-anchor:hover .cgi-step-tip,
        .cgi-tip-anchor:focus-within .cgi-step-tip {
            display: block;
        }

        @media (min-width: 768px) {
            .cgi-tip-anchor--left .cgi-step-tip {
                left: auto;
                right: calc(100% + 0.5rem);
            }
        }

        @media (max-width: 767px) {
            .cgi-step-tip {
                top: auto;
                bottom: calc(100% + 0.375rem);
                left: auto;
                right: 0;
                transform: none;
            }
        }
    </style>
</x-app-layout>