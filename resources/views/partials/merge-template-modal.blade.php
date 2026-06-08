{{-- Requires parent Alpine: mergeModal, resetTemplateSelection, libraryTemplates, templateDropdownId, etc. --}}
<template x-teleport="body">
    <div x-show="mergeModal" x-cloak
        class="fixed inset-0 z-[2200] flex items-center justify-center p-3 sm:p-4 bg-black/90 backdrop-blur-sm overflow-y-auto">
        <div class="bg-[#0a0a0a] border border-white/10 w-full max-w-lg rounded-2xl shadow-2xl my-auto flex flex-col max-h-[min(92vh,720px)]"
            @click.stop
            @click.away="mergeModal = false">

            <div class="flex justify-between items-start p-5 sm:p-6 pb-4 border-b border-white/5 shrink-0">
                <div class="min-w-0 pr-4">
                    <h2 class="text-white font-black uppercase tracking-[0.15em] text-sm">Merge with Template</h2>
                    <p class="text-gray-500 text-[9px] uppercase font-bold mt-1">Select from library or upload — uploads save automatically</p>
                </div>
                <button type="button" @click="mergeModal = false; resetTemplateSelection()"
                    class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg bg-white/5 hover:bg-white/10 text-gray-500 hover:text-white transition-colors">✕</button>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar px-5 sm:px-6 py-4 space-y-4 min-h-0">

                {{-- Dropdown --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Choose Template</label>
                    <select
                        x-model="templateDropdownId"
                        @change="onTemplateDropdownChange($event)"
                        class="w-full bg-[#111] border border-white/10 rounded-lg px-3 py-3 text-white text-xs font-bold focus:border-orange-500 outline-none transition-all appearance-none cursor-pointer"
                        :disabled="isSavingTemplate">
                        <option value="">— Select a saved template —</option>
                        <template x-for="tpl in libraryTemplates" :key="tpl.id">
                            <option :value="String(tpl.id)" x-text="tpl.name"></option>
                        </template>
                    </select>
                    <p x-show="libraryTemplates.length === 0" class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-2">
                        No templates yet — upload below to add one.
                    </p>
                    <p x-show="libraryTemplates.length > 0" class="text-[8px] text-gray-600 font-bold uppercase tracking-widest mt-1.5"
                        x-text="libraryTemplates.length + ' template(s) in your library'"></p>
                </div>

                {{-- Preview --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Preview</label>
                    <div class="w-full h-36 sm:h-40 rounded-xl border border-white/10 bg-[#111] overflow-hidden flex items-center justify-center p-3 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMWExYTFhIj48L3JlY3Q+CjxyZWN0IHg9IjQiIHk9IjQiIHdpZHRoPSI0IiBoZWlnaHQ9IjQiIGZpbGw9IiMxYTFhMWEiPjwvcmVjdD4KPHJlY3QgeD0iNCIgd2lkdGg9IjQiIGhlaWdodD0iNCIgZmlsbD0iIzExMSI+PC9yZWN0Pgo8cmVjdCB5PSI0IiB3aWR0aD0iNCIgaGVpZ2h0PSI0IiBmaWxsPSIjMTExIj48L3JlY3Q+Cjwvc3ZnPg==')]">
                        <template x-if="templatePreview">
                            <img :src="templatePreview" alt="Template preview" class="max-w-full max-h-full w-auto h-auto object-contain">
                        </template>
                        <template x-if="!templatePreview && !isSavingTemplate">
                            <p class="text-[9px] text-gray-600 font-black uppercase tracking-widest text-center px-4">Select from dropdown or upload a template</p>
                        </template>
                        <template x-if="isSavingTemplate">
                            <div class="flex flex-col items-center gap-2 text-orange-400">
                                <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span class="text-[9px] font-black uppercase tracking-widest">Saving to library…</span>
                            </div>
                        </template>
                    </div>
                </div>

                <p class="text-center text-[9px] text-gray-600 font-bold uppercase tracking-widest">— or upload new —</p>

                {{-- Upload (auto-saves to library) --}}
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 text-center">Upload Template Image</label>
                    <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed rounded-xl cursor-pointer bg-[#111] hover:bg-white/[0.02] transition-all overflow-hidden group relative"
                        :class="isSavingTemplate ? 'border-orange-500/30 opacity-60 pointer-events-none' : (templatePreview ? 'border-orange-500/50' : 'border-white/10 hover:border-orange-500/50')">
                        <div x-show="!templatePreview && !isSavingTemplate" class="flex flex-col items-center justify-center py-4">
                            <div class="p-2.5 bg-gray-800/50 rounded-full mb-2 group-hover:bg-orange-900/20 transition-colors">
                                <svg class="w-5 h-5 text-gray-500 group-hover:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </div>
                            <p class="text-[9px] text-gray-500 font-black uppercase">Click to Upload</p>
                            <p class="text-[8px] text-gray-600 font-bold uppercase mt-1">Saved to library automatically</p>
                        </div>
                        <input type="file" class="hidden" accept="image/jpeg,image/png,image/jpg,image/webp" @change="handleTemplateUpload($event)" :disabled="isSavingTemplate">
                    </label>
                </div>

                <div class="bg-orange-500/5 border border-orange-500/10 p-3 rounded-xl">
                    <p class="text-[9px] text-orange-400 font-bold leading-relaxed uppercase tracking-widest">
                        Starts the n8n merge pipeline. The merged image appears when processing completes.
                    </p>
                </div>
            </div>

            <div class="p-5 sm:p-6 pt-4 border-t border-white/5 shrink-0">
                <button type="button" @click="triggerMergeTemplate()"
                    :disabled="isMerging || isSavingTemplate || !selectedTemplateId"
                    class="w-full py-3.5 bg-orange-600 text-white text-[10px] font-black rounded-lg uppercase tracking-widest shadow-lg shadow-orange-600/20 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-3 hover:bg-orange-500 transition-all">
                    <svg x-show="isMerging" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="isMerging ? 'INITIATING MERGE…' : 'START MERGE PIPELINE'"></span>
                </button>
            </div>
        </div>
    </div>
</template>
