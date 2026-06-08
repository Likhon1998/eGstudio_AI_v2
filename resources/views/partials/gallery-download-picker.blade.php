{{-- Must be included INSIDE the parent Alpine x-data component (x-teleport keeps scope). --}}
<template x-teleport="body">
<div x-show="downloadPickerOpen" x-cloak
     class="fixed inset-0 z-[5000] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md"
     @keydown.escape.window="downloadPickerOpen = false">
    <div class="bg-[#0a0a0a] border border-white/10 w-full max-w-lg rounded-xl shadow-2xl overflow-hidden"
         @click.stop>
        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
            <div>
                <h3 class="text-[11px] font-black text-white uppercase tracking-[0.25em]">Choose Download Format</h3>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-1 truncate max-w-[280px]" x-text="downloadBaseName"></p>
            </div>
            <button type="button" @click="downloadPickerOpen = false" class="text-gray-500 hover:text-white text-lg">✕</button>
        </div>
        <div class="p-6 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <template x-for="fmt in downloadFormats" :key="fmt.id">
                <button type="button"
                        @click="downloadInFormat(fmt.id)"
                        :disabled="isDownloading"
                        class="rounded-xl border border-white/10 bg-white/[0.02] hover:border-emerald-500/40 hover:bg-emerald-500/10 p-4 text-center transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="block text-sm font-black text-white uppercase tracking-wider" x-text="fmt.label"></span>
                    <span class="block text-[8px] text-gray-500 font-bold uppercase tracking-widest mt-1" x-text="fmt.hint"></span>
                </button>
            </template>
        </div>
        <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex items-center justify-between gap-3">
            <p class="text-[9px] text-gray-600 font-semibold uppercase tracking-wider" x-show="isDownloading">Converting &amp; downloading…</p>
            <p class="text-[9px] text-gray-600 font-semibold" x-show="!isDownloading">Select a format to download</p>
            <button type="button" @click="downloadPickerOpen = false"
                    class="px-4 py-2 text-gray-400 hover:text-white text-[10px] font-black uppercase tracking-widest">Cancel</button>
        </div>
    </div>
</div>
</template>
