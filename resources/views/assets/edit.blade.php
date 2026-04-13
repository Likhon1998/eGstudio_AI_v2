<x-app-layout>
    <div class="max-w-2xl mx-auto py-10 px-6 antialiased">
        <div class="mb-8 border-b border-white/10 pb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-black text-white uppercase tracking-widest">Edit Asset</h1>
                <p class="text-xs text-gray-500 font-bold uppercase mt-1">{{ $asset->name }}</p>
            </div>
            <a href="{{ route('assets.index') }}" class="px-5 py-2.5 bg-white/5 hover:bg-white/10 text-gray-300 text-[10px] font-black uppercase tracking-widest rounded-lg border border-white/10 transition-all">Back to Library</a>
        </div>

        <div class="bg-[#0a0a0a] border border-white/5 rounded-2xl p-8 shadow-xl">
            <form action="{{ route('assets.update', $asset->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="{ newImage: false, fileName: '' }">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Asset Name</label>
                    <input type="text" name="name" value="{{ old('name', $asset->name) }}" required class="w-full bg-[#111] border border-white/10 rounded-lg p-3 text-white text-xs focus:border-blue-500 outline-none transition-all">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Current Image</label>
                        <div class="aspect-square bg-[#111] rounded-xl overflow-hidden border border-white/5">
                            <img src="{{ asset('storage/' . $asset->file_path) }}" class="w-full h-full object-cover opacity-60">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Replace Image (Optional)</label>
                        <input type="file" name="file_path" id="file_path" accept="image/*" class="hidden" @change="newImage = true; fileName = $event.target.files[0].name">
                        <label for="file_path" :class="newImage ? 'border-emerald-500 text-emerald-400' : 'border-gray-700 text-gray-500 hover:border-blue-500'" class="flex flex-col items-center justify-center w-full aspect-square border-2 border-dashed rounded-xl bg-[#111] cursor-pointer transition-colors px-4 text-center">
                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            <span class="text-[10px] font-bold uppercase tracking-widest truncate w-full" x-text="newImage ? fileName : 'Browse Files'"></span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-white/5 flex justify-end">
                    <button type="submit" class="px-8 py-3.5 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black rounded-lg uppercase tracking-widest shadow-[0_0_20px_rgba(37,99,235,0.2)] transition-all">
                        Update Asset
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>