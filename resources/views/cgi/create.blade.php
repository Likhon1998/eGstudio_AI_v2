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
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">CGI Studio Director</h2>
                <p class="text-gray-400 text-[11px] mt-1 font-medium">Build your commercial. Use the <span
                        class="text-blue-400 font-bold">ⓘ</span> icons to understand each step.</p>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">

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
        <div class="bg-gray-900/60 backdrop-blur-2xl p-5 sm:p-6 rounded-2xl text-white shadow-2xl border border-gray-800/60 relative">

            {{-- Background glow isolated with its own overflow-hidden --}}
            <div class="absolute inset-0 overflow-hidden rounded-2xl pointer-events-none z-0">
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl"></div>
            </div>

            <form action="{{ route('cgi.store') }}" method="POST" enctype="multipart/form-data" class="relative z-10"
                x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

                    {{-- 01. The Product (LEFT SIDE) --}}
                    <div x-data="{ val: '' }">
                        <div class="flex items-center gap-2 mb-2 relative group w-fit">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">01. What
                                are you selling?</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 top-full mt-2 hidden group-hover:block w-64 p-3 bg-gray-800 border border-gray-700 text-[10px] text-gray-300 rounded-xl shadow-2xl z-[60] leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> Mention the specific product name.
                                Adding the material helps the AI create realistic textures.
                            </div>
                        </div>
                        <input type="text" name="product_name" x-model="val" placeholder="E.g. Luxury Leather Watch..."
                            required
                            class="w-full bg-black/40 border border-gray-700/80 rounded-xl text-white focus:ring-1 focus:ring-blue-500/50 focus:border-blue-500 p-2.5 outline-none transition-all text-sm shadow-inner placeholder-gray-600">

                        <div class="flex gap-1.5 mt-2.5 overflow-x-auto pb-2 custom-scrollbar snap-x">
                            <button type="button" @click="val = 'Luxury Gold & Leather Watch'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">⌚
                                Gold Watch</button>
                            <button type="button" @click="val = 'Nitro Mesh Running Shoe'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">👟
                                Running Shoe</button>
                            <button type="button" @click="val = 'Matte Carbon Fiber Drone'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">🚁
                                FPV Drone</button>
                            <button type="button" @click="val = 'Frosted Glass Perfume Bottle'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">✨
                                Perfume</button>
                            <button type="button" @click="val = 'Matte Black Sports Car'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">🏎️
                                Sports Car</button>
                            <button type="button" @click="val = 'High-End Gaming PC Setup'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">💻
                                Gaming PC</button>
                            <button type="button" @click="val = 'Obsidian Mirror Smartphone'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">📱
                                Smartphone</button>
                            <button type="button" @click="val = 'Gourmet Truffle Burger'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">🍔
                                Gourmet Burger</button>
                            <button type="button" @click="val = 'Vintage 35mm Film Camera'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">📸
                                Film Camera</button>
                            <button type="button" @click="val = 'Sapphire Crystal Diamond Ring'"
                                class="shrink-0 px-2.5 py-1 bg-gray-800/40 border border-gray-700 rounded-md text-[9px] text-gray-400 hover:text-white hover:bg-blue-600/30 transition-all font-bold tracking-wider">💍
                                Diamond Ring</button>
                        </div>
                    </div>

                    {{-- 02. Search & Select Product Upload (RIGHT SIDE) --}}
                    <div x-data="{ 
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
                    }">
                        <input type="hidden" name="selected_asset_path" :value="selectedAssetPath">

                        <div class="flex items-center gap-1.5 mb-1.5 relative group w-fit">
                            <label class="block text-blue-400 text-[9px] font-bold tracking-[0.2em] uppercase">02. Product Reference</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="absolute left-0 md:left-auto md:right-0 top-full mt-1 hidden group-hover:block w-64 p-2.5 bg-gray-800 border border-gray-700 text-[9px] text-gray-300 rounded-xl shadow-2xl z-[60] leading-relaxed">
                                <strong class="text-white block mb-0.5">Guide:</strong> Select an existing product from your library, or click the + button to upload a new one. New uploads auto-save to your library.
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
                        val: '',
                        toggle(word) {
                            let items = this.val ? this.val.split(', ').filter(i => i) : [];
                            if (items.includes(word)) { items = items.filter(i => i !== word); } else { items.push(word); }
                            this.val = items.join(', ');
                        }
                    }">
                        <div class="flex items-center gap-2 mb-2 relative group w-fit">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">03. Text
                                You Want To See !</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 md:left-auto md:right-0 top-full mt-2 hidden group-hover:block w-64 p-3 bg-gray-800 border border-gray-700 text-[10px] text-gray-300 rounded-xl shadow-2xl z-[60] leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> Select or type words you want
                                highlighted as bold text/graphics inside your final render.
                            </div>
                        </div>
                        <input type="text" name="marketing_angle" x-model="val"
                            placeholder="Type custom benefits or select below..." required
                            class="w-full bg-black/40 border border-gray-700/80 rounded-xl text-white focus:ring-1 focus:ring-blue-500/50 focus:border-blue-500 p-2.5 outline-none transition-all text-sm shadow-inner placeholder-gray-600">

                        <div class="flex gap-1.5 mt-2.5 overflow-x-auto pb-2 custom-scrollbar snap-x">
                            <button type="button" @click="toggle('ENERGY SAVING')"
                                :class="val.includes('ENERGY') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">⚡
                                Energy</button>
                            <button type="button" @click="toggle('PURE LUXURY')"
                                :class="val.includes('LUXURY') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">💎
                                Luxury</button>
                            <button type="button" @click="toggle('ULTRA DURABLE')"
                                :class="val.includes('DURABLE') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">🔨
                                Durable</button>
                            <button type="button" @click="toggle('HYPER SPEED')"
                                :class="val.includes('SPEED') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">🚀
                                Speed</button>
                            <button type="button" @click="toggle('ICE COLD')"
                                :class="val.includes('COLD') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">❄️
                                Fresh</button>
                            <button type="button" @click="toggle('ECO FRIENDLY')"
                                :class="val.includes('ECO') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">🌱
                                Eco-Friendly</button>
                            <button type="button" @click="toggle('WIRELESS TECH')"
                                :class="val.includes('WIRELESS') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">📶
                                Wireless</button>
                            <button type="button" @click="toggle('WATER PROOF')"
                                :class="val.includes('WATER') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">💦
                                Waterproof</button>
                            <button type="button" @click="toggle('ADVANCED AI')"
                                :class="val.includes('AI') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">🧠
                                AI Tech</button>
                            <button type="button" @click="toggle('ULTRA LIGHTWEIGHT')"
                                :class="val.includes('LIGHTWEIGHT') ? 'bg-blue-600 text-white border-blue-500' : 'bg-gray-800/40 text-gray-400 border-gray-700'"
                                class="shrink-0 px-2.5 py-1 border rounded-md text-[9px] transition-all font-bold tracking-wider">🪶
                                Lightweight</button>
                        </div>
                    </div>

                    {{-- 04. Decoration --}}
                    <div x-data="{ 
                        selectedProps: [], 
                        customInput: '',
                        addProp(item) { if (item.trim() !== '' && !this.selectedProps.includes(item)) { this.selectedProps.push(item.trim()); } this.customInput = ''; },
                        removeProp(item) { this.selectedProps = this.selectedProps.filter(i => i !== item); },
                        toggleProp(item) { if (this.selectedProps.includes(item)) { this.removeProp(item); } else { this.addProp(item); } }
                    }">
                        <input type="hidden" name="visual_prop" :value="selectedProps.join(', ')">

                        <div class="flex items-center gap-2 mb-2 relative group w-fit">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">04.
                                Objects next to product.</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 top-full mt-2 hidden group-hover:block w-64 p-3 bg-gray-800 border border-gray-700 text-[10px] text-gray-300 rounded-xl shadow-2xl z-[60] leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> Add props or natural elements
                                around your product to give the scene context, scale, and visual depth.
                            </div>
                        </div>

                        <div
                            class="w-full bg-black/40 border border-gray-700/80 rounded-xl p-1.5 flex flex-wrap gap-1.5 transition-all focus-within:ring-1 focus-within:ring-blue-500/50 shadow-inner">
                            <template x-for="prop in selectedProps" :key="prop">
                                <div
                                    class="flex items-center gap-1.5 bg-blue-600/20 border border-blue-500/40 text-blue-300 px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-wider">
                                    <span x-text="prop"></span>
                                    <button type="button" @click="removeProp(prop)"
                                        class="hover:text-white text-blue-500/50 transition-colors leading-none">✕</button>
                                </div>
                            </template>
                            <input type="text" x-model="customInput" @keydown.enter.prevent="addProp(customInput)"
                                @keydown.comma.prevent="addProp(customInput)" @blur="addProp(customInput)"
                                placeholder="Type & Enter..."
                                class="flex-1 min-w-[120px] bg-transparent border-none text-white text-[11px] p-1 outline-none focus:ring-0 placeholder-gray-600">
                        </div>

                        <div class="flex gap-1.5 mt-2.5 overflow-x-auto pb-2 custom-scrollbar snap-x">
                            <template x-for="item in [
                                {id: 'Marble', val: 'Polished Marble Slab', icon: '🪨'}, {id: 'Water', val: 'Splashing Water Wave', icon: '💧'},
                                {id: 'Chrome', val: 'Floating Chrome Spheres', icon: '🟠'}, {id: 'Leaves', val: 'Tropical Palm Leaves', icon: '🍃'},
                                {id: 'Rock', val: 'Jagged Volcanic Rocks', icon: '🌋'}, {id: 'Energy', val: 'Abstract Glowing Fibers', icon: '⚡'},
                                {id: 'Ice', val: 'Shattered Crystal Ice', icon: '🧊'}, {id: 'Dust', val: 'Floating Gold Dust', icon: '✨'},
                                {id: 'Clouds', val: 'Soft Cloud Puffs', icon: '☁️'}, {id: 'Laser', val: 'Red Laser Grid', icon: '🔴'},
                                {id: 'Blocks', val: 'Geometric Floating Blocks', icon: '🛑'}, {id: 'Petals', val: 'Falling Rose Petals', icon: '🌹'}
                            ]" :key="item.id">
                                <button type="button" @click="toggleProp(item.val)"
                                    :class="selectedProps.includes(item.val) ? 'bg-blue-600 border-blue-400 text-white' : 'bg-gray-800/40 text-gray-400 border-gray-700 text-gray-400 hover:text-white hover:border-blue-500/50'"
                                    class="shrink-0 px-2 py-1 border rounded-md text-[9px] font-bold uppercase tracking-widest transition-all flex items-center gap-1.5">
                                    <span x-text="item.icon"></span><span x-text="item.id"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- 05. Background & 06. Movement --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-6 md:col-span-1">
                        {{-- 05. Background --}}
                        <div x-data="{ val: '' }">
                            <div class="flex items-center gap-2 mb-2 relative group w-fit">
                                <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">05.
                                    Scene Background.</label>
                                <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div
                                    class="absolute left-0 top-full mt-2 hidden group-hover:block w-64 p-3 bg-gray-800 border border-gray-700 text-[10px] text-gray-300 rounded-xl shadow-2xl z-[60] leading-relaxed">
                                    <strong class="text-white block mb-1">Guide:</strong> Describe the environment or
                                    overall setting where your product is placed. Keep it atmospheric.
                                </div>
                            </div>
                            <input type="text" name="atmosphere" x-model="val"
                                placeholder="Type custom scene or select..." required
                                class="w-full bg-black/40 border border-gray-700/80 rounded-xl text-white focus:ring-1 focus:ring-blue-500/50 focus:border-blue-500 p-2.5 outline-none transition-all text-sm shadow-inner placeholder-gray-600">

                            <select x-model="val"
                                class="w-full mt-2 bg-gray-800/40 border border-gray-700 rounded-lg text-[10px] text-gray-300 p-1.5 outline-none font-bold uppercase tracking-widest cursor-pointer">
                                <option value="">-- Quick Select --</option>
                                <option value="Misty Morning Forest">🌲 Misty Forest</option>
                                <option value="Luxury Penthouse Skyline">🏙️ City Skyline</option>
                                <option value="Underwater Sunbeams">🌊 Ocean Depth</option>
                                <option value="Neon Cyberpunk Tokyo">🏮 Cyberpunk Tokyo</option>
                                <option value="Minimal High-Tech Lab">🔬 Clean Tech Lab</option>
                                <option value="Cozy Minimalist Studio">🏠 Photo Studio</option>
                                <option value="Mars Martian Surface">🪐 Martian Surface</option>
                                <option value="Lush Tropical Rainforest">🌴 Lush Rainforest</option>
                                <option value="High-Altitude Cloudscape">☁️ High Cloudscape</option>
                                <option value="Ancient Temple Ruins">🏛️ Ancient Ruins</option>
                                <option value="Neon Retrowave Grid">🪩 Retrowave Grid</option>
                                <option value="Stark White Infinity Cove">⚪ Infinity Cove</option>
                            </select>
                        </div>

                        {{-- 06. Movement --}}
                        <div x-data="{ val: '' }">
                            <div class="flex items-center gap-2 mb-2 relative group w-fit">
                                <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">06.
                                    Camera Style.</label>
                                <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div
                                    class="absolute left-0 sm:left-auto sm:right-0 top-full mt-2 hidden group-hover:block w-64 p-3 bg-gray-800 border border-gray-700 text-[10px] text-gray-300 rounded-xl shadow-2xl z-[60] leading-relaxed">
                                    <strong class="text-white block mb-1">Guide:</strong> Define how the camera moves
                                    around the product to create dynamic, professional video shots.
                                </div>
                            </div>
                            <input type="text" name="camera_motion" x-model="val"
                                placeholder="Type custom camera or select..." required
                                class="w-full bg-black/40 border border-gray-700/80 rounded-xl text-white focus:ring-1 focus:ring-blue-500/50 focus:border-blue-500 p-2.5 outline-none transition-all text-sm shadow-inner placeholder-gray-600">

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
                    <div class="md:col-span-1" x-data="{ comp: '' }">
                        <div class="flex items-center gap-2 mb-2 relative group w-fit">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">07.
                                Product Possitioning?</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 top-full mt-2 hidden group-hover:block w-64 p-3 bg-gray-800 border border-gray-700 text-[10px] text-gray-300 rounded-xl shadow-2xl z-[60] leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> Select where your product sits in
                                the frame. Framing to the side helps leave room for text or graphics.
                            </div>
                        </div>

                        <input type="text" name="composition" x-model="comp"
                            placeholder="Type custom layout or select below..." required
                            class="w-full bg-black/40 border border-gray-700/80 rounded-xl text-white focus:ring-1 focus:ring-blue-500/50 focus:border-blue-500 p-2.5 outline-none transition-all text-sm shadow-inner placeholder-gray-600 mb-2.5">

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
                    <div class="md:col-span-1" x-data="{ light: '' }">
                        <div class="flex items-center gap-2 mb-2 relative group w-fit">
                            <label class="block text-blue-400 text-[10px] font-bold tracking-[0.2em] uppercase">08.
                                Lighting & Color preference.</label>
                            <div class="cursor-help text-gray-500 hover:text-blue-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div
                                class="absolute left-0 md:left-auto md:right-0 top-full mt-2 hidden group-hover:block w-64 p-3 bg-gray-800 border border-gray-700 text-[10px] text-gray-300 rounded-xl shadow-2xl z-[60] leading-relaxed">
                                <strong class="text-white block mb-1">Guide:</strong> Pick a lighting style to set the
                                overall mood, contrast, and visual color palette of your scene.
                            </div>
                        </div>

                        <input type="text" name="lighting_style" x-model="light"
                            placeholder="Type custom lighting or select below..." required
                            class="w-full bg-black/40 border border-gray-700/80 rounded-xl text-white focus:ring-1 focus:ring-blue-500/50 focus:border-blue-500 p-2.5 outline-none transition-all text-sm shadow-inner placeholder-gray-600 mb-2.5">

                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            <div @click="light = 'Movie Style: High contrast, cinematic glow'"
                                :class="light.includes('Movie') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🎬</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Movie</h4>
                            </div>
                            <div @click="light = 'Warm Sunset: Golden hour glow'"
                                :class="light.includes('Sunset') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🌅</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Sunset</h4>
                            </div>
                            <div @click="light = 'Clean Studio: White softbox lighting'"
                                :class="light.includes('Clean') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">💡</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Clean</h4>
                            </div>
                            <div @click="light = 'Cyber Neon: Pink & Blue glow'"
                                :class="light.includes('Neon') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🟣</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Neon</h4>
                            </div>
                            <div @click="light = 'Midnight Blue: Cold moonlight shadows'"
                                :class="light.includes('Midnight') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🌙</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Moon</h4>
                            </div>
                            <div @click="light = 'Earth Tones: Natural browns and greens'"
                                :class="light.includes('Earth') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🍂</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Earth</h4>
                            </div>
                            <div @click="light = 'Noir: High contrast black and white'"
                                :class="light.includes('Noir') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🌑</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Noir</h4>
                            </div>
                            <div @click="light = 'Dreamy Glow: Soft hazy highlights'"
                                :class="light.includes('Dreamy') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">☁️</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Soft</h4>
                            </div>
                            <div @click="light = 'Prism Holographic: Iridescent rainbow refractions'"
                                :class="light.includes('Prism') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🌈</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Prism</h4>
                            </div>
                            <div @click="light = 'Harsh Flash: Direct paparazzi style flash photography'"
                                :class="light.includes('Harsh Flash') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">📸</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Flash</h4>
                            </div>
                            <div @click="light = 'Dramatic Spotlight: Dark room, single hard spotlight'"
                                :class="light.includes('Spotlight') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🔦</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Spotlight</h4>
                            </div>
                            <div @click="light = 'Warm Firelight: Flickering orange and yellow shadows'"
                                :class="light.includes('Firelight') ? 'border-blue-500 bg-blue-600/20 text-white' : 'border-gray-700/80 bg-black/30 text-gray-400'"
                                class="p-1 border rounded-lg cursor-pointer hover:border-blue-500/50 transition-all text-center">
                                <span class="text-sm block mb-0.5">🔥</span>
                                <h4 class="text-[8px] font-bold uppercase tracking-wider">Fire</h4>
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
    </style>
</x-app-layout>