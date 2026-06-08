{{-- Inline approver feedback for the maker (approved or rejected, when a note was left). --}}
@if(($requiresApproval ?? false) && in_array($status ?? '', ['approved', 'rejected'], true) && filled($comment ?? null))
    <p class="w-full basis-full mt-1 text-[9px] italic leading-relaxed {{ ($status ?? '') === 'rejected' ? 'text-red-300/80' : 'text-emerald-300/80' }}">
        <span class="not-italic font-black uppercase tracking-widest text-[8px] text-gray-500">Approver note:</span>
        “{{ $comment }}”
    </p>
@endif
