{{--
    Maker-side approval control for a single media item.
    Expects:
      $requiresApproval (bool) – standard user with an approver (never admin)
      $genId, $mediaUrl, $mediaType ('image'|'video'), $variant, $isBranded (bool)
      $approval (App\Models\MediaApproval|null) – resolved status for this media
--}}
@if($requiresApproval)
    <div class="mt-3 pt-3 border-t border-white/5">
        @if($approval && $approval->status === 'approved')
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded text-[8px] font-black uppercase tracking-widest">✓ Approved</span>
                <span class="text-[8px] text-gray-500 font-bold uppercase tracking-widest">Ready to publish</span>
            </div>
            @include('partials.approver-note', [
                'requiresApproval' => true,
                'status' => 'approved',
                'comment' => $approval->comment,
            ])
        @elseif($approval && $approval->status === 'pending')
            <span class="px-2 py-1 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded text-[8px] font-black uppercase tracking-widest">⏳ Awaiting Approval</span>
        @elseif($approval && $approval->status === 'rejected')
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 bg-red-500/10 text-red-400 border border-red-500/20 rounded text-[8px] font-black uppercase tracking-widest">✕ Rejected</span>
            </div>
            @include('partials.approver-note', [
                'requiresApproval' => true,
                'status' => 'rejected',
                'comment' => $approval->comment,
            ])
            <button @click="submitForApproval('{{ $genId }}', '{{ $mediaUrl }}', '{{ $mediaType }}', '{{ $variant }}', {{ $isBranded ? 'true' : 'false' }})"
                    :disabled="submittingId === '{{ $mediaUrl }}'"
                    class="mt-2 w-full py-2 bg-white/5 hover:bg-amber-600 hover:text-white border border-white/10 text-amber-400 text-[8px] font-black rounded uppercase tracking-widest transition-all disabled:opacity-50">
                <span x-text="submittingId === '{{ $mediaUrl }}' ? 'Resubmitting...' : 'Resubmit for Approval'"></span>
            </button>
        @else
            <button @click="submitForApproval('{{ $genId }}', '{{ $mediaUrl }}', '{{ $mediaType }}', '{{ $variant }}', {{ $isBranded ? 'true' : 'false' }})"
                    :disabled="submittingId === '{{ $mediaUrl }}'"
                    class="w-full py-2 bg-emerald-600/10 hover:bg-emerald-600 hover:text-white border border-emerald-500/20 text-emerald-400 text-[8px] font-black rounded uppercase tracking-widest transition-all disabled:opacity-50">
                <span x-text="submittingId === '{{ $mediaUrl }}' ? 'Submitting...' : 'Submit for Approval'"></span>
            </button>
        @endif
    </div>
@endif
