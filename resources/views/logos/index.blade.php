<x-app-layout>
    <div x-data="logoVault()" class="p-4 sm:p-6 lg:p-8 max-w-[1600px] mx-auto space-y-8 antialiased selection:bg-blue-500/30">
        
        {{-- =========================================================================
             HEADER SECTION
             ========================================================================= --}}
        <div class="flex flex-col sm:flex-row sm:items-end justify-between border-b border-white/10 pb-6 gap-4 relative">
            <div class="absolute -top-10 -left-10 w-40 h-40 bg-blue-600/20 rounded-full blur-[80px] pointer-events-none"></div>

            <div class="relative">
                <h1 class="text-xl sm:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-500 tracking-[0.2em] uppercase">
                    Brand Identity Vault
                </h1>
                <p class="text-[10px] sm:text-xs text-gray-400 font-bold uppercase tracking-widest mt-2 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_#3b82f6] animate-pulse"></span>
                    Manage Watermarks & Brand Overlays
                </p>
            </div>
        </div>

        {{-- SUCCESS & ERROR TOASTS --}}
        @if(session('success'))
            <div class="px-6 py-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-black uppercase tracking-widest rounded-xl flex items-center gap-3 shadow-[0_0_20px_rgba(16,185,129,0.1)] animate-in fade-in slide-in-from-top-4">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="px-6 py-4 bg-red-500/10 border border-red-500/20 text-red-400 text-[11px] font-black uppercase tracking-widest rounded-xl flex flex-col gap-1.5 shadow-[0_0_20px_rgba(239,68,68,0.1)]">
                @foreach ($errors->all() as $error)
                    <span class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-500 rounded-full shadow-[0_0_5px_#ef4444]"></span> {{ $error }}</span>
                @endforeach
            </div>
        @endif

        {{-- =========================================================================
             UNIFIED WORKSPACE GRID (LEFT: UPLOAD, RIGHT: VAULT)
             ========================================================================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- LEFT COLUMN: MULTI-UPLOAD ZONE --}}
            <div class="lg:col-span-4 xl:col-span-3 sticky top-6">
                <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl shadow-2xl relative overflow-hidden flex flex-col">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 to-cyan-400"></div>
                    
                    <div class="p-6 md:p-8 flex-grow flex flex-col">
                        <div class="mb-6">
                            <p class="text-[9px] font-black text-blue-500/80 uppercase tracking-[0.2em] mb-1.5">
                                01. Asset Acquisition
                            </p>
                            <h2 class="text-sm font-black text-white uppercase tracking-[0.15em] flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Upload New Logos
                            </h2>
                        </div>

                        {{-- Hidden File Input --}}
                        <input 
                            type="file" 
                            id="logoUploader" 
                            multiple 
                            accept="image/png, image/jpeg, image/jpg, image/svg+xml" 
                            class="hidden" 
                            @change="handleFiles"
                        >

                        {{-- Upload Trigger Box --}}
                        <div 
                            @click="document.getElementById('logoUploader').click()"
                            class="border-2 border-dashed border-white/10 rounded-xl p-8 text-center hover:border-blue-500/40 hover:bg-blue-500/5 transition-all cursor-pointer mb-6 group"
                        >
                            <div class="w-12 h-12 bg-white/5 group-hover:bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4 transition-colors">
                                <svg class="w-6 h-6 text-gray-500 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest block">Select Image Files</span>
                            <span class="text-[8px] font-bold text-gray-600 block mt-2 uppercase tracking-widest">PNG, JPG, SVG • Select Multiple</span>
                        </div>

                        {{-- Live Preview Grid for Selected Files --}}
                        <div x-show="files.length > 0" class="mb-6 flex-grow" x-transition>
                            <p class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3 border-b border-white/5 pb-2">
                                Staged for Upload (<span x-text="files.length"></span>)
                            </p>
                            <div class="grid grid-cols-2 gap-3 max-h-[40vh] overflow-y-auto pr-1 custom-scrollbar">
                                <template x-for="file in files" :key="file.id">
                                    <div class="relative bg-black border border-white/10 rounded-lg p-2 group">
                                        <div class="aspect-square bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMWExYTFhIj48L3JlY3Q+CjxyZWN0IHg9IjQiIHk9IjQiIHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiMxYTFhMWEiPjwvcmVjdD4KPHJlY3QgeD0iNCIgd2lkdGg9IjQiIGhlaWdodD0iNCIgZmlsbD0iIzExMSI+PC9yZWN0Pgo8cmVjdCB5PSI0IiB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMTExIj48L3JlY3Q+Cjwvc3ZnPg==')] rounded flex items-center justify-center overflow-hidden">
                                            <img :src="file.preview" class="max-w-full max-h-full object-contain">
                                        </div>
                                        <button 
                                            @click.stop="removeFile(file.id)" 
                                            class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-opacity focus:outline-none"
                                            title="Remove"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                        <p class="text-[7px] text-gray-400 font-mono truncate mt-1.5 text-center" x-text="file.name"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Smart Submission Button --}}
                        <div class="mt-auto pt-6 border-t border-white/5">
                            <button 
                                @click="uploadAll()"
                                :disabled="files.length === 0 || isSubmitting"
                                class="w-full py-4 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg transition-all flex items-center justify-center gap-3"
                                :class="files.length === 0 ? 'bg-white/5 text-gray-600 cursor-not-allowed border border-white/5' : 'bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white shadow-blue-600/20 hover:-translate-y-0.5'"
                            >
                                <span x-show="!isSubmitting" x-text="files.length === 0 ? 'Awaiting Assets' : 'Upload ' + files.length + ' Assets'"></span>
                                <span x-show="isSubmitting" x-text="uploadProgress" class="animate-pulse"></span>
                                <svg x-show="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: IDENTITY VAULT GRID --}}
            <div class="lg:col-span-8 xl:col-span-9">
                <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl shadow-2xl p-6 md:p-8 min-h-full">
                    
                    <div class="mb-8 border-b border-white/5 pb-4">
                        <p class="text-[9px] font-black text-emerald-500/80 uppercase tracking-[0.2em] mb-1.5">
                            02. Verified Vault Assets
                        </p>
                        <h2 class="text-sm font-black text-white uppercase tracking-[0.15em] flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Identity Library ({{ count($logos) }})
                        </h2>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-5">
                        @forelse($logos as $logo)
                            <div class="bg-black border border-white/5 rounded-xl overflow-hidden group hover:border-blue-500/30 transition-all shadow-xl relative flex flex-col">
                                
                                {{-- Image Container (Checkerboard Background) --}}
                                <div class="aspect-square w-full relative bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMWExYTFhIj48L3JlY3Q+CjxyZWN0IHg9IjQiIHk9IjQiIHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiMxYTFhMWEiPjwvcmVjdD4KPHJlY3QgeD0iNCIgd2lkdGg9IjQiIGhlaWdodD0iNCIgZmlsbD0iIzExMSI+PC9yZWN0Pgo8cmVjdCB5PSI0IiB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMTExIj48L3JlY3Q+Cjwvc3ZnPg==')] border-b border-white/5 p-4 flex items-center justify-center">
                                    <img src="{{ $logo->url }}" alt="{{ $logo->name }}" class="max-w-full max-h-full object-contain drop-shadow-2xl transition-transform group-hover:scale-110 duration-500">
                                    
                                    {{-- Hover Delete Button --}}
                                    <form action="{{ route('logos.destroy', $logo->id) }}" method="POST" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        @csrf @method('DELETE')
                                        <button 
                                            @click.prevent="formToSubmit = $el.closest('form'); deleteModal = true;" 
                                            type="button" 
                                            class="p-2 bg-red-500/80 hover:bg-red-500 text-white rounded-lg backdrop-blur-md shadow-lg transition-colors focus:outline-none"
                                            title="Purge Logo"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                                
                                {{-- File Meta Info --}}
                                <div class="p-3 bg-[#050505]">
                                    <p class="text-[9px] text-gray-400 font-mono truncate" title="{{ $logo->name }}">{{ $logo->name ?? 'brand_logo.png' }}</p>
                                    <p class="text-[8px] text-gray-600 font-bold uppercase tracking-widest mt-1">{{ $logo->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full flex flex-col items-center justify-center py-32 px-6 bg-black border border-white/5 rounded-2xl border-dashed">
                                <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mb-6 shadow-inner">
                                    <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <h3 class="text-sm font-black text-white uppercase tracking-[0.2em] mb-2">Identity Vault Empty</h3>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest text-center">Use the acquisition panel on the left to secure brand assets.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- =========================================================================
             MODAL: DELETE CONFIRMATION
             ========================================================================= --}}
        <template x-teleport="body">
            <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[5000] flex items-center justify-center bg-black/90 backdrop-blur-xl px-4">
                <div class="bg-gradient-to-t from-[#050505] to-[#0a0a0a] border border-red-500/30 rounded-2xl p-8 w-full max-w-sm shadow-[0_0_50px_rgba(239,68,68,0.15)] relative text-center" @click.away="deleteModal = false" x-show="deleteModal" x-transition>
                    
                    <div class="w-14 h-14 bg-red-500/10 border border-red-500/20 text-red-500 rounded-full mx-auto flex items-center justify-center mb-5 shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    
                    <h3 class="text-sm font-black text-white uppercase tracking-[0.15em] mb-2">Purge Identity Asset?</h3>
                    <p class="text-[10px] text-gray-400 font-bold px-2 mb-8 leading-relaxed">This will permanently delete the selected logo from your secure vault. This action cannot be undone.</p>
                    
                    <div class="flex gap-3">
                        <button @click="deleteModal = false" type="button" class="w-1/2 px-4 py-3.5 bg-white/5 hover:bg-white/10 text-gray-300 text-[10px] font-black uppercase tracking-widest rounded-xl transition-colors border border-white/5">Cancel</button>
                        <button @click="formToSubmit.querySelector('button[type=submit]').disabled = true; $el.innerHTML = 'PURGING...'; formToSubmit.submit();" type="button" class="w-1/2 px-4 py-3.5 bg-red-600 hover:bg-red-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-[0_0_20px_rgba(220,38,38,0.3)] hover:-translate-y-0.5">Confirm Purge</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- ALPINE.JS LOGIC CONTROLLER --}}
    <script>
        function logoVault() {
            return {
                files: [],
                isSubmitting: false,
                deleteModal: false,
                formToSubmit: null,
                uploadProgress: '',

                // Handle incoming files from the file input
                handleFiles(event) {
                    const selected = event.target.files;
                    for(let i=0; i<selected.length; i++) {
                        const file = selected[i];
                        if (file.type.startsWith('image/')) {
                            this.files.push({
                                id: Date.now() + i, // Unique ID for Alpine tracking
                                file: file,
                                preview: URL.createObjectURL(file),
                                name: file.name
                            });
                        }
                    }
                    // Reset input so the same file can be selected again if removed
                    event.target.value = ''; 
                },

                // Remove a file from the staging queue
                removeFile(id) {
                    this.files = this.files.filter(f => f.id !== id);
                },

                // Background AJAX Loop to upload multiple files to the existing single-file endpoint
                async uploadAll() {
                    if(this.files.length === 0 || this.isSubmitting) return;
                    this.isSubmitting = true;
                    
                    let successCount = 0;
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    for(let i=0; i<this.files.length; i++) {
                        this.uploadProgress = `UPLOADING ${i+1} OF ${this.files.length}...`;
                        
                        let formData = new FormData();
                        formData.append('logo', this.files[i].file);
                        formData.append('_token', token);

                        try {
                            let res = await fetch('{{ route("logos.store") }}', {
                                method: 'POST',
                                body: formData,
                                headers: { 'Accept': 'application/json' } // Prevents strict redirects from breaking the loop
                            });
                            
                            // Because LogoController redirects back(), res.ok will be true (200 OK from the redirect target)
                            if(res.ok) {
                                successCount++;
                            }
                        } catch(e) { 
                            console.error('Upload failed for', this.files[i].name); 
                        }
                    }

                    this.uploadProgress = 'SYNCING VAULT...';
                    
                    if(successCount > 0) {
                        // Reload the page to show the newly uploaded images in the grid
                        window.location.reload();
                    } else {
                        this.isSubmitting = false;
                        this.$dispatch('notify', { message: 'Upload Failed. Check file sizes.', type: 'error' });
                    }
                }
            }
        }
    </script>

    {{-- STUDIO STYLESHEET --}}
    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom Scrollbar for the preview box */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #2a2a2a;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #3b82f6;
        }
    </style>
</x-app-layout>