<x-app-layout>
    <div class="max-w-7xl mx-auto py-10 px-6 antialiased" x-data="{ deleteModal: false, formToSubmit: null }">
        
        {{-- Header --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-white/10 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-black text-white uppercase tracking-widest">Asset Library</h1>
                <p class="text-xs text-gray-500 font-bold uppercase mt-2">Manage your product images for CGI generation</p>
            </div>
            <a href="{{ route('cgi.create') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-lg shadow-blue-600/20 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Launch Studio
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 px-4 py-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-widest rounded-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Left: Upload Form (Bulk Supported) --}}
            <div class="lg:col-span-1">
                <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl p-6 shadow-xl sticky top-6">
                    <h2 class="text-[11px] font-black text-white uppercase tracking-widest mb-6 flex items-center gap-2 border-b border-white/5 pb-3">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Upload Assets (Bulk)
                    </h2>

                    <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5" x-data="{ fileCount: 0, fileNames: '', isUploading: false }" @submit="isUploading = true">
                        @csrf
                        
                        <div>
                            <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-3 leading-relaxed">
                                Select one or multiple images. Files will be automatically named based on their original filename.
                            </p>

                            <div class="relative">
                                {{-- Bulk Upload Input --}}
                                <input type="file" name="file_paths[]" id="file_paths" accept="image/*" multiple required class="hidden" 
                                    @change="fileCount = $event.target.files.length; fileNames = Array.from($event.target.files).map(f => f.name).join(', ')">
                                
                                <label for="file_paths" :class="fileCount > 0 ? 'border-blue-500 bg-blue-500/10' : 'border-gray-700 hover:border-blue-500/50 bg-[#111]'" class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed rounded-xl cursor-pointer transition-colors px-4 text-center relative overflow-hidden">
                                    
                                    <svg class="w-8 h-8 mb-2 transition-colors" :class="fileCount > 0 ? 'text-blue-400' : 'text-gray-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    
                                    {{-- Default State --}}
                                    <span x-show="fileCount === 0" class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Click to Browse Files</span>
                                    
                                    {{-- Selected State --}}
                                    <div x-show="fileCount > 0" class="flex flex-col items-center w-full" x-cloak>
                                        <span class="text-[11px] font-black text-blue-400 uppercase tracking-widest" x-text="fileCount + ' File(s) Selected'"></span>
                                        <span class="text-[8px] text-gray-400 mt-1.5 truncate w-full max-w-[200px]" x-text="fileNames"></span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- ADDED OR condition to :disabled logic --}}
                        <button type="submit" :disabled="isUploading || fileCount === 0" class="w-full py-3.5 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black rounded-lg uppercase tracking-widest shadow-[0_0_20px_rgba(37,99,235,0.2)] transition-all flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="!isUploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <svg x-show="isUploading" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="isUploading ? 'UPLOADING...' : 'Upload to Library'"></span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Right: Asset Gallery --}}
            <div class="lg:col-span-2">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @forelse($assets as $asset)
                        <div class="bg-[#0a0a0a] border border-white/5 rounded-xl overflow-hidden group hover:border-white/20 transition-all">
                            <div class="aspect-square relative overflow-hidden bg-[#111]">
                                <img src="{{ asset('storage/' . $asset->file_path) }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500">
                                
                                {{-- Overlay Actions --}}
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-sm">
                                    <a href="{{ route('assets.edit', $asset->id) }}" class="p-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors shadow-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>
                                    <form action="{{ route('assets.destroy', $asset->id) }}" method="POST" @submit.prevent="formToSubmit = $event.target; deleteModal = true;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 bg-red-600 hover:bg-red-500 text-white rounded-lg transition-colors shadow-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="p-3 border-t border-white/5">
                                <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest truncate">{{ $asset->name }}</p>
                                <p class="text-[8px] text-gray-600 font-mono mt-1">{{ $asset->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full flex flex-col items-center justify-center py-20 px-6 bg-[#0a0a0a] border border-white/5 rounded-2xl border-dashed">
                            <svg class="w-12 h-12 text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <p class="text-xs font-black text-gray-500 uppercase tracking-widest">Library is Empty</p>
                            <p class="text-[10px] text-gray-600 font-bold uppercase mt-2">Upload your first product asset to the left.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- DELETE CONFIRMATION MODAL --}}
        <template x-teleport="body">
            <div x-show="deleteModal" 
                class="fixed inset-0 z-[2200] flex items-center justify-center p-6 bg-black/90 backdrop-blur-xl"
                x-cloak>
                <div class="bg-[#0a0a0a] border border-red-500/20 w-full max-w-md rounded-2xl p-8 shadow-[0_0_40px_rgba(239,68,68,0.1)] animate-in zoom-in duration-300"
                    @click.away="deleteModal = false">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-white font-black uppercase tracking-[0.2em] text-sm text-red-500">Delete Asset</h2>
                            <p class="text-gray-400 text-[9px] uppercase font-bold mt-1">Are you sure you want to delete this?</p>
                        </div>
                        <button @click="deleteModal = false" type="button"
                            class="text-gray-600 hover:text-white transition-colors">✕</button>
                    </div>
                    
                    <div class="text-gray-400 text-xs mb-8">
                        This action will permanently delete the selected asset from your library. This cannot be undone.
                    </div>

                    <div class="flex gap-3">
                        <button @click="deleteModal = false" type="button" class="flex-1 py-3 bg-white/5 hover:bg-white/10 text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-colors">
                            Cancel
                        </button>
                        <button 
                            @click="
                                let button = $event.target;
                                button.disabled = true;
                                button.innerHTML = 'Deleting...';
                                formToSubmit.submit();
                            " 
                            type="button" 
                            class="flex-1 py-3 bg-red-600/20 hover:bg-red-600 border border-red-500/50 text-red-500 hover:text-white text-[10px] font-black rounded-lg uppercase tracking-widest transition-all"
                        >
                            Yes, Delete
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>