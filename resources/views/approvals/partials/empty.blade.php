{{-- Empty queue placeholder. Expects: $filter --}}
<div class="py-20 flex flex-col items-center justify-center border-2 border-dashed border-white/5 rounded-[2rem] bg-white/[0.01]">
    <svg class="w-10 h-10 text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <h3 class="text-[11px] font-black text-gray-600 uppercase tracking-[0.3em]">Nothing {{ ucfirst($filter) }}</h3>
    <p class="text-[9px] text-gray-700 font-bold uppercase tracking-widest mt-2">No pics or videos in this queue.</p>
</div>
