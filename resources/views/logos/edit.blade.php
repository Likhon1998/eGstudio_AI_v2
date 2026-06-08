<x-app-layout>
    <div class="max-w-2xl mx-auto py-10 px-6 antialiased"
        x-data="{
            previewModal: false,
            previewUrl: @js($logo->url),
            previewName: @js($logo->name),
            openPreview() { this.previewModal = true; },
            closePreview() { this.previewModal = false; }
        }"
        @keydown.escape.window="closePreview()">
        <div class="mb-8 border-b border-white/10 pb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-black text-white uppercase tracking-widest">Edit Logo</h1>
                <p class="text-xs text-gray-500 font-bold uppercase mt-1">{{ $logo->name }}</p>
            </div>
            <a href="{{ route('assets.index', ['tab' => 'logo']) }}" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-[10px] font-black uppercase tracking-widest rounded-lg border border-white/10 transition-all">Back to Library</a>
        </div>

        <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl p-8 shadow-xl">
            <form action="{{ route('logos.update', $logo->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="{ newImage: false, fileName: '' }">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Logo Name</label>
                    <input type="text" name="name" value="{{ old('name', $logo->name) }}" required class="w-full bg-[#111] border border-white/10 rounded-lg p-3 text-white text-xs focus:border-emerald-500 outline-none transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Current Logo</label>
                        <button type="button" @click="openPreview()"
                            class="aspect-square w-full bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMWExYTFhIj48L3JlY3Q+CjxyZWN0IHg9IjQiIHk9IjQiIHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiMxYTFhMWEiPjwvcmVjdD4KPHJlY3QgeD0iNCIgd2lkdGg9IjQiIGhlaWdodD0iNCIgZmlsbD0iIzExMSI+PC9yZWN0Pgo8cmVjdCB5PSI0IiB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMTExIj48L3JlY3Q+Cjwvc3ZnPg==')] rounded-xl overflow-hidden border border-white/5 p-4 flex items-center justify-center cursor-zoom-in hover:border-emerald-500/40 transition-colors group">
                            <img src="{{ $logo->url }}" alt="{{ $logo->name }}" class="max-w-full max-h-full object-contain opacity-80 group-hover:opacity-100 transition-opacity">
                        </button>
                        <p class="text-[8px] text-gray-600 font-bold uppercase tracking-widest mt-2 text-center">Click to preview</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Replace Logo (Optional)</label>
                        <input type="file" name="logo" id="logo_file" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" class="hidden" @change="newImage = true; fileName = $event.target.files[0]?.name || ''">
                        <label for="logo_file" :class="newImage ? 'border-emerald-500 text-emerald-400' : 'border-gray-700 text-gray-500 hover:border-emerald-500/50'" class="flex flex-col items-center justify-center w-full aspect-square border-2 border-dashed rounded-xl bg-[#111] cursor-pointer transition-colors px-4 text-center">
                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            <span class="text-[10px] font-bold uppercase tracking-widest truncate w-full" x-text="newImage ? fileName : 'Browse Files'"></span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-white/5 flex justify-end">
                    <button type="submit" class="px-8 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black rounded-lg uppercase tracking-widest shadow-[0_0_20px_rgba(16,185,129,0.2)] transition-all">
                        Update Logo
                    </button>
                </div>
            </form>
        </div>

        <template x-teleport="body">
            <div x-show="previewModal" x-cloak class="fixed inset-0 z-[2100] flex items-center justify-center p-4 bg-black/95 backdrop-blur-xl" @click.self="closePreview()">
                <div class="relative w-full max-w-4xl" @click.stop>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-white font-black uppercase tracking-widest text-sm" x-text="previewName"></h2>
                        <button type="button" @click="closePreview()" class="text-gray-500 hover:text-white">✕</button>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-[#0a0a0a] p-6 flex items-center justify-center bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMWExYTFhIj48L3JlY3Q+CjxyZWN0IHg9IjQiIHk9IjQiIHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiMxYTFhMWEiPjwvcmVjdD4KPHJlY3QgeD0iNCIgd2lkdGg9IjQiIGhlaWdodD0iNCIgZmlsbD0iIzExMSI+PC9yZWN0Pgo8cmVjdCB5PSI0IiB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMTExIj48L3JlY3Q+Cjwvc3ZnPg==')]">
                        <img :src="previewUrl" :alt="previewName" class="max-w-full max-h-[75vh] object-contain">
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
