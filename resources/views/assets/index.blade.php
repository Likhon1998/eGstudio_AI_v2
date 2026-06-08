<x-app-layout>
    @php
        $isProductTab = $tab === \App\Models\ProductAsset::TYPE_PRODUCT;
        $isTemplateTab = $tab === \App\Models\ProductAsset::TYPE_TEMPLATE;
        $isLogoTab = $tab === 'logo';
        $checkerboard = "bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMWExYTFhIj48L3JlY3Q+CjxyZWN0IHg9IjQiIHk9IjQiIHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiMxYTFhMWEiPjwvcmVjdD4KPHJlY3QgeD0iNCIgd2lkdGg9IjQiIGhlaWdodD0iNCIgZmlsbD0iIzExMSI+PC9yZWN0Pgo8cmVjdCB5PSI0IiB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMTExIj48L3JlY3Q+Cjwvc3ZnPg==')]";
    @endphp
    <div class="max-w-7xl mx-auto py-10 px-6 antialiased"
        x-data="{
            deleteModal: false,
            formToSubmit: null,
            previewModal: false,
            previewUrl: '',
            previewName: '',
            previewEditUrl: null,
            openPreview(url, name, editUrl = null) {
                this.previewUrl = url;
                this.previewName = name;
                this.previewEditUrl = editUrl || null;
                this.previewModal = true;
            },
            closePreview() {
                this.previewModal = false;
                this.previewUrl = '';
                this.previewName = '';
                this.previewEditUrl = null;
            }
        }"
        @keydown.escape.window="if (previewModal) { closePreview(); } else if (deleteModal) { deleteModal = false; }">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-white/10 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-black text-white uppercase tracking-widest">Asset Library</h1>
                <p class="text-xs text-gray-500 font-bold uppercase mt-2">
                    Product images, merge templates &amp; brand logos — preview, edit &amp; delete on every tab
                </p>
            </div>
            <a href="{{ route('cgi.create') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-lg shadow-blue-600/20 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Launch Studio
            </a>
        </div>

        {{-- Tabs --}}
        <div class="flex flex-wrap gap-2 mb-8">
            <a href="{{ route('assets.index', ['tab' => 'product']) }}"
               class="px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-lg border transition-all {{ $isProductTab ? 'bg-blue-600/20 border-blue-500/40 text-blue-400' : 'bg-white/5 border-white/10 text-gray-500 hover:text-gray-300' }}">
                Product Images
            </a>
            <a href="{{ route('assets.index', ['tab' => 'template']) }}"
               class="px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-lg border transition-all {{ $isTemplateTab ? 'bg-orange-600/20 border-orange-500/40 text-orange-400' : 'bg-white/5 border-white/10 text-gray-500 hover:text-gray-300' }}">
                Merge Templates
            </a>
            <a href="{{ route('assets.index', ['tab' => 'logo']) }}"
               class="px-5 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-lg border transition-all {{ $isLogoTab ? 'bg-emerald-600/20 border-emerald-500/40 text-emerald-400' : 'bg-white/5 border-white/10 text-gray-500 hover:text-gray-300' }}">
                Brand Logos
            </a>
        </div>

        @if(($missingFileCount ?? 0) > 0)
            <div class="mb-6 px-4 py-3 bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-bold uppercase tracking-widest rounded-lg leading-relaxed">
                {{ $missingFileCount }} asset(s) have database records but the image files are not on this server.
                Re-upload them using the form on the left, or delete the broken entries and upload again.
            </div>
        @endif

        @if(session('success'))
            <div class="mb-6 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-widest rounded-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-6 px-4 py-3 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold uppercase tracking-widest rounded-lg">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Left: Upload --}}
            <div class="lg:col-span-1">
                <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl p-6 shadow-xl sticky top-6">
                    @if($isLogoTab)
                        <h2 class="text-[11px] font-black text-white uppercase tracking-widest mb-6 flex items-center gap-2 border-b border-white/5 pb-3">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                            Upload Logos (Bulk)
                        </h2>
                        <form action="{{ route('logos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5" x-data="{ fileCount: 0, fileNames: '', isUploading: false }" @submit="isUploading = true">
                            @csrf
                            <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-3 leading-relaxed">
                                PNG, JPG, SVG or WEBP with transparency. Select multiple logos for branding overlays.
                            </p>
                            <input type="file" name="logos[]" id="logo_paths" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" multiple required class="hidden"
                                @change="fileCount = $event.target.files.length; fileNames = Array.from($event.target.files).map(f => f.name).join(', ')">
                            <label for="logo_paths"
                                :class="fileCount > 0 ? 'border-emerald-500 bg-emerald-500/10' : 'border-gray-700 hover:border-emerald-500/50 bg-[#111]'"
                                class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed rounded-xl cursor-pointer transition-colors px-4 text-center">
                                <svg class="w-8 h-8 mb-2 transition-colors" :class="fileCount > 0 ? 'text-emerald-400' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span x-show="fileCount === 0" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Click to Browse Logos</span>
                                <div x-show="fileCount > 0" class="flex flex-col items-center w-full" x-cloak>
                                    <span class="text-[11px] font-black text-emerald-400 uppercase tracking-widest" x-text="fileCount + ' File(s) Selected'"></span>
                                    <span class="text-[8px] text-gray-400 mt-1.5 truncate w-full max-w-[200px]" x-text="fileNames"></span>
                                </div>
                            </label>
                            <button type="submit" :disabled="isUploading || fileCount === 0" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black rounded-lg uppercase tracking-widest shadow-[0_0_20px_rgba(16,185,129,0.2)] transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-text="isUploading ? 'UPLOADING...' : 'Upload to Library'"></span>
                            </button>
                        </form>
                    @else
                        <h2 class="text-[11px] font-black text-white uppercase tracking-widest mb-6 flex items-center gap-2 border-b border-white/5 pb-3">
                            @if($isTemplateTab)
                                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
                                Upload Templates (Bulk)
                            @else
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Upload Product Images (Bulk)
                            @endif
                        </h2>
                        <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5" x-data="{ fileCount: 0, fileNames: '', isUploading: false }" @submit="isUploading = true">
                            @csrf
                            <input type="hidden" name="asset_type" value="{{ $tab }}">
                            <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-3 leading-relaxed">
                                @if($isTemplateTab)
                                    Template frames/overlays for merge pipelines in CGI &amp; Occasion Studio.
                                @else
                                    Product shots for CGI generation. Named from original filenames.
                                @endif
                            </p>
                            <input type="file" name="file_paths[]" id="file_paths" accept="image/*" multiple required class="hidden"
                                @change="fileCount = $event.target.files.length; fileNames = Array.from($event.target.files).map(f => f.name).join(', ')">
                            <label for="file_paths"
                                @if($isTemplateTab)
                                :class="fileCount > 0 ? 'border-orange-500 bg-orange-500/10' : 'border-gray-700 hover:border-orange-500/50 bg-[#111]'"
                                @else
                                :class="fileCount > 0 ? 'border-blue-500 bg-blue-500/10' : 'border-gray-700 hover:border-blue-500/50 bg-[#111]'"
                                @endif
                                class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed rounded-xl cursor-pointer transition-colors px-4 text-center">
                                @if($isTemplateTab)
                                <svg class="w-8 h-8 mb-2 transition-colors" :class="fileCount > 0 ? 'text-orange-400' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                @else
                                <svg class="w-8 h-8 mb-2 transition-colors" :class="fileCount > 0 ? 'text-blue-400' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                @endif
                                <span x-show="fileCount === 0" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Click to Browse Files</span>
                                <div x-show="fileCount > 0" class="flex flex-col items-center w-full" x-cloak>
                                    <span class="text-[11px] font-black {{ $isTemplateTab ? 'text-orange-400' : 'text-blue-400' }} uppercase tracking-widest" x-text="fileCount + ' File(s) Selected'"></span>
                                    <span class="text-[8px] text-gray-400 mt-1.5 truncate w-full max-w-[200px]" x-text="fileNames"></span>
                                </div>
                            </label>
                            <button type="submit" :disabled="isUploading || fileCount === 0" class="w-full py-3.5 {{ $isTemplateTab ? 'bg-orange-600 hover:bg-orange-500 shadow-[0_0_20px_rgba(234,88,12,0.2)]' : 'bg-blue-600 hover:bg-blue-500 shadow-[0_0_20px_rgba(37,99,235,0.2)]' }} text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-text="isUploading ? 'UPLOADING...' : 'Upload to Library'"></span>
                            </button>
                        </form>
                    @endif
                    <p class="text-[8px] text-gray-600 font-bold uppercase tracking-widest mt-4 text-center leading-relaxed">
                        Click thumbnail to preview · Hover for preview, edit &amp; delete
                    </p>
                </div>
            </div>

            {{-- Right: Gallery --}}
            <div class="lg:col-span-2">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @if($isLogoTab)
                        @forelse($logos as $logo)
                            @include('partials.asset-library-card', [
                                'imageUrl' => $logo->url,
                                'name' => $logo->name,
                                'createdAt' => $logo->created_at,
                                'editUrl' => route('logos.edit', $logo->id),
                                'destroyUrl' => route('logos.destroy', $logo->id),
                                'accent' => 'emerald',
                                'objectFit' => 'contain',
                                'useCheckerboard' => true,
                                'fileMissing' => ! $logo->fileExistsOnDisk(),
                            ])
                        @empty
                            <div class="col-span-full flex flex-col items-center justify-center py-20 px-6 bg-[#0a0a0a] border border-white/5 rounded-2xl border-dashed">
                                <p class="text-xs font-black text-gray-500 uppercase tracking-widest">No Logos Yet</p>
                                <p class="text-[10px] text-gray-600 font-bold uppercase mt-2">Upload brand logos using the form on the left.</p>
                            </div>
                        @endforelse
                    @else
                        @forelse($assets as $asset)
                            @include('partials.asset-library-card', [
                                'imageUrl' => $asset->url,
                                'name' => $asset->name,
                                'createdAt' => $asset->created_at,
                                'editUrl' => route('assets.edit', $asset->id),
                                'destroyUrl' => route('assets.destroy', $asset->id),
                                'accent' => $isTemplateTab ? 'orange' : 'blue',
                                'objectFit' => $isTemplateTab ? 'contain' : 'cover',
                                'useCheckerboard' => $isTemplateTab,
                                'fileMissing' => ! $asset->fileExistsOnDisk(),
                            ])
                        @empty
                            <div class="col-span-full flex flex-col items-center justify-center py-20 px-6 bg-[#0a0a0a] border border-white/5 rounded-2xl border-dashed">
                                <p class="text-xs font-black text-gray-500 uppercase tracking-widest">Library is Empty</p>
                                <p class="text-[10px] text-gray-600 font-bold uppercase mt-2">
                                    @if($isTemplateTab)
                                        Upload your first merge template using the form on the left.
                                    @else
                                        Upload your first product image using the form on the left.
                                    @endif
                                </p>
                            </div>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>

        {{-- IMAGE PREVIEW MODAL --}}
        <template x-teleport="body">
            <div x-show="previewModal" x-cloak
                class="fixed inset-0 z-[2100] flex items-center justify-center p-4 sm:p-8 bg-black/95 backdrop-blur-xl"
                @click.self="closePreview()">
                <div class="relative w-full max-w-5xl max-h-[90vh] flex flex-col" @click.stop>
                    <div class="flex justify-between items-center mb-4 gap-4">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-white font-black uppercase tracking-[0.2em] text-sm truncate" x-text="previewName"></h2>
                            <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-1">Full size preview</p>
                        </div>
                        <button type="button" @click="closePreview()" class="shrink-0 w-10 h-10 flex items-center justify-center rounded-lg bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition-colors text-lg" title="Close">✕</button>
                    </div>
                    <div class="flex-1 min-h-0 rounded-2xl border border-white/10 overflow-hidden {{ $checkerboard }} flex items-center justify-center p-4 sm:p-8 bg-[#0a0a0a]">
                        <img :src="previewUrl" :alt="previewName" class="max-w-full max-h-[70vh] w-auto h-auto object-contain shadow-2xl">
                    </div>
                    <div class="flex flex-wrap gap-2 mt-4 justify-center sm:justify-end">
                        <a :href="previewUrl" target="_blank" rel="noopener"
                            class="px-4 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 text-[10px] font-black uppercase tracking-widest rounded-lg transition-colors inline-flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            Open Full Size
                        </a>
                        <a x-show="previewEditUrl" :href="previewEditUrl"
                            class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black uppercase tracking-widest rounded-lg transition-colors inline-flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            Edit Asset
                        </a>
                    </div>
                </div>
            </div>
        </template>

        {{-- DELETE CONFIRMATION MODAL --}}
        <template x-teleport="body">
            <div x-show="deleteModal" x-cloak
                class="fixed inset-0 z-[2200] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl"
                @click.self="deleteModal = false">
                <div class="bg-[#0a0a0a] border border-red-500/20 w-full max-w-md rounded-2xl p-8 shadow-[0_0_40px_rgba(239,68,68,0.1)]">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-white font-black uppercase tracking-[0.2em] text-sm text-red-500">Delete Asset</h2>
                            <p class="text-gray-400 text-[9px] uppercase font-bold mt-1">This cannot be undone.</p>
                        </div>
                        <button @click="deleteModal = false" type="button" class="text-gray-600 hover:text-white">✕</button>
                    </div>
                    <div class="flex gap-3">
                        <button @click="deleteModal = false" type="button" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white text-[10px] font-black rounded-lg uppercase tracking-widest">Cancel</button>
                        <button type="button"
                            @click="let b = $event.target; b.disabled = true; b.textContent = 'Deleting...'; formToSubmit.submit();"
                            class="flex-1 py-3 bg-red-600/20 hover:bg-red-600 border border-red-500/50 text-red-500 hover:text-white text-[10px] font-black rounded-lg uppercase tracking-widest">Yes, Delete</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
