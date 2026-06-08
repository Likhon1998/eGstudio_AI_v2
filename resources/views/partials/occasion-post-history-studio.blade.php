{{--
    Occasion Studio — Post History tab.
    Expects: $socialPosts (collection), $postHistoryStats, $accent ('pink')
--}}
@php
    $accent = $accent ?? 'pink';
    $activeTab = $accent === 'pink'
        ? 'bg-pink-600/15 text-pink-400 border-pink-500/30'
        : 'bg-blue-600/15 text-blue-400 border-blue-500/30';
    $histFilterIdle = 'text-gray-500 hover:text-gray-300 border-transparent';
    $stats = $postHistoryStats ?? ['total' => 0, 'published' => 0, 'scheduled' => 0, 'pending' => 0, 'failed' => 0];
    $defaultFilter = ($stats['published'] ?? 0) > 0 ? 'published' : (($stats['scheduled'] ?? 0) > 0 ? 'scheduled' : (($stats['pending'] ?? 0) > 0 ? 'pending' : 'all'));
@endphp

<div x-data="{
        histFilter: @js($defaultFilter),
        showPreview: false,
        deletingId: null,
        activePost: { mediaUrl: '', caption: '', date: '', campaign: '', asset: '' },
        openPreview(post) {
            this.activePost = post;
            this.showPreview = true;
        },
        async deletePost(postId) {
            if (!confirm('Remove this post from history? This only deletes the record here — it will not unpublish from Facebook.')) return;
            this.deletingId = postId;
            try {
                const res = await fetch('{{ url('occasions/post-history') }}/' + postId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                    return;
                }
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: data.message || 'Could not delete post.', type: 'error' }
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message: 'Network error while deleting.', type: 'error' }
                }));
            } finally {
                this.deletingId = null;
            }
        }
     }" class="p-4 sm:p-6 space-y-5">
    <p class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider">
        Social posts published from Occasion Studio campaigns
    </p>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <button type="button" @click="histFilter = 'all'"
                :class="histFilter === 'all' ? '{{ $activeTab }}' : '{{ $histFilterIdle }}'"
                class="rounded-xl border p-4 text-left transition-all">
            <div class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Total posts</div>
            <div class="text-2xl font-black text-white mt-1 tabular-nums">{{ $stats['total'] }}</div>
        </button>
        <button type="button" @click="histFilter = 'published'"
                :class="histFilter === 'published' ? '{{ $activeTab }}' : '{{ $histFilterIdle }}'"
                class="rounded-xl border p-4 text-left transition-all">
            <div class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Published</div>
            <div class="text-2xl font-black text-emerald-400 mt-1 tabular-nums">{{ $stats['published'] }}</div>
        </button>
        <button type="button" @click="histFilter = 'scheduled'"
                :class="histFilter === 'scheduled' ? '{{ $activeTab }}' : '{{ $histFilterIdle }}'"
                class="rounded-xl border p-4 text-left transition-all">
            <div class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Scheduled</div>
            <div class="text-2xl font-black text-blue-400 mt-1 tabular-nums">{{ $stats['scheduled'] }}</div>
        </button>
        <button type="button" @click="histFilter = 'pending'"
                :class="histFilter === 'pending' ? '{{ $activeTab }}' : '{{ $histFilterIdle }}'"
                class="rounded-xl border p-4 text-left transition-all">
            <div class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Processing</div>
            <div class="text-2xl font-black text-amber-400 mt-1 tabular-nums">{{ $stats['pending'] }}</div>
        </button>
        <button type="button" @click="histFilter = 'failed'"
                :class="histFilter === 'failed' ? '{{ $activeTab }}' : '{{ $histFilterIdle }}'"
                class="rounded-xl border p-4 text-left transition-all">
            <div class="text-[9px] font-bold text-gray-500 uppercase tracking-wider">Failed</div>
            <div class="text-2xl font-black text-red-400 mt-1 tabular-nums">{{ $stats['failed'] }}</div>
        </button>
    </div>

    <div class="bg-[#0a0a0a] border border-white/[0.06] rounded-xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[720px]">
                <thead class="bg-white/[0.02] border-b border-white/5">
                    <tr class="text-[9px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                        <th class="px-4 sm:px-6 py-4">Media</th>
                        <th class="px-4 sm:px-6 py-4">Campaign</th>
                        <th class="px-4 sm:px-6 py-4">Caption</th>
                        <th class="px-4 sm:px-6 py-4">Asset</th>
                        <th class="px-4 sm:px-6 py-4 text-right">Status / Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.03]">
                    @forelse($socialPosts as $post)
                        @php
                            $isMerged = !$post->is_branded;
                            $statusGroup = match ($post->status) {
                                'published' => 'published',
                                'scheduled' => 'scheduled',
                                'pending' => 'pending',
                                'failed', 'n8n_rejected' => 'failed',
                                default => 'all',
                            };
                            $postedAt = $post->created_at->timezone('Asia/Dhaka');
                            $previewPayload = [
                                'mediaUrl' => $post->media_url,
                                'mediaType' => 'image',
                                'caption' => $post->caption ?? '',
                                'date' => $postedAt->format('F j \a\t g:i A'),
                                'campaign' => $post->occasion->occasion_identity ?? 'Occasion Campaign',
                                'asset' => $isMerged ? 'Merged Pic' : 'Logo\'d Pic',
                                'status' => ucfirst(str_replace('_', ' ', $post->status)),
                            ];
                        @endphp
                        <tr x-show="histFilter === 'all' || histFilter === @js($statusGroup)"
                            x-cloak
                            class="hover:bg-white/[0.01] transition-colors">
                            <td class="px-4 sm:px-6 py-4 align-top w-24">
                                <button type="button"
                                        @click="openPreview(@js($previewPayload))"
                                        class="w-20 h-20 rounded-lg overflow-hidden bg-black border border-white/10 relative group shadow-lg block">
                                    <img src="{{ $post->media_url }}" alt="" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                        <span class="text-[7px] font-black text-white uppercase tracking-widest">Preview</span>
                                    </div>
                                </button>
                            </td>
                            <td class="px-4 sm:px-6 py-4 align-top">
                                <span class="text-xs font-black text-gray-100 uppercase tracking-wider leading-tight block">
                                    {{ $post->occasion->occasion_identity ?? 'Occasion Campaign' }}
                                </span>
                                <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-1 block">
                                    {{ $postedAt->format('M d, Y • h:i A') }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 align-top max-w-xs">
                                <div class="text-xs text-gray-400 leading-relaxed bg-white/[0.02] p-3 rounded-lg border border-white/5 line-clamp-3 italic">
                                    "{{ $post->caption ?: 'No caption' }}"
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 align-top">
                                <span class="inline-flex px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border
                                    {{ $isMerged ? 'bg-violet-500/10 text-violet-300 border-violet-500/30' : 'bg-blue-500/10 text-blue-300 border-blue-500/30' }}">
                                    {{ $isMerged ? 'Merged Pic' : "Logo'd Pic" }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-right align-top">
                                <div class="flex flex-col items-end gap-2">
                                    @if($post->status === 'published')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-full text-[9px] font-black uppercase tracking-widest">Published</span>
                                    @elseif($post->status === 'scheduled')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-full text-[9px] font-black uppercase tracking-widest">Scheduled</span>
                                    @elseif($post->status === 'pending')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded-full text-[9px] font-black uppercase tracking-widest">Processing</span>
                                    @elseif(in_array($post->status, ['failed', 'n8n_rejected'], true))
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-500/10 border border-red-500/20 text-red-400 rounded-full text-[9px] font-black uppercase tracking-widest">Failed</span>
                                    @else
                                        <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">{{ $post->status }}</span>
                                    @endif
                                    <button type="button"
                                            @click="openPreview(@js($previewPayload))"
                                            class="text-[9px] font-bold text-pink-400 hover:text-pink-300 uppercase tracking-widest flex items-center gap-1 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        View Post
                                    </button>
                                    <button type="button"
                                            @click="deletePost({{ $post->id }})"
                                            :disabled="deletingId === {{ $post->id }}"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[9px] font-black uppercase tracking-widest border border-red-500/20 bg-red-500/10 text-red-400 hover:bg-red-500/20 hover:text-red-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        <span x-text="deletingId === {{ $post->id }} ? 'Deleting…' : 'Delete'"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <p class="text-sm font-black text-gray-400 uppercase tracking-widest">No posts yet</p>
                                <p class="text-[10px] text-gray-600 uppercase tracking-widest mt-2">Publish from Create Post to see history here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="showPreview" x-cloak
             class="fixed inset-0 z-[3000] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md"
             @click.self="showPreview = false"
             @keydown.escape.window="showPreview = false"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div x-show="showPreview"
                 class="bg-[#242526] w-full max-w-[500px] rounded-xl shadow-[0_0_50px_rgba(0,0,0,0.8)] border border-[#3E4042] overflow-hidden flex flex-col max-h-[min(90vh,820px)]"
                 @click.stop
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-95">

                {{-- Fixed header --}}
                <div class="px-4 py-3 flex items-start gap-3 shrink-0 border-b border-[#3E4042] bg-[#242526]">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-pink-500 to-pink-400 flex items-center justify-center text-white font-bold shadow-inner shrink-0">
                        {{ substr(auth()->user()->name ?? 'O', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0 pr-2">
                        <div class="text-[#E4E6EB] font-bold text-[15px] leading-snug">{{ auth()->user()->name ?? 'Occasion Studio' }}</div>
                        <div class="text-[#B0B3B8] text-[12px] leading-relaxed mt-1 space-y-0.5">
                            <div x-text="activePost.date"></div>
                            <div class="flex flex-wrap items-center gap-x-1 gap-y-0.5">
                                <span class="text-pink-400/90 font-semibold" x-text="activePost.campaign"></span>
                                <span>·</span>
                                <span x-text="activePost.asset"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="showPreview = false" class="text-[#B0B3B8] hover:bg-[#3A3B3C] p-2 rounded-full transition-colors shrink-0 -mt-1 -mr-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                {{-- Scrollable post body: caption then image, no overlap --}}
                <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain custom-scrollbar bg-[#242526]">
                    <div class="px-4 pt-3 pb-4 text-[#E4E6EB] text-[15px] whitespace-pre-wrap leading-relaxed break-words"
                         x-text="activePost.caption || 'No caption provided.'"></div>
                    <div class="w-full bg-black border-t border-[#3E4042]/60">
                        <img :src="activePost.mediaUrl" alt="" class="w-full h-auto block object-contain">
                    </div>
                </div>

                {{-- Fixed footer --}}
                <div class="px-4 py-3 shrink-0 border-t border-[#3E4042] bg-[#242526]">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/5 border border-white/10 text-[10px] font-bold uppercase tracking-wider text-gray-300" x-text="activePost.status"></span>
                        <span class="text-[9px] uppercase tracking-widest text-gray-500 shrink-0">Occasion Studio Preview</span>
                    </div>
                    <div class="flex items-center justify-between text-[#B0B3B8] font-semibold text-[13px] border-t border-[#3E4042] pt-1">
                        <button type="button" class="flex-1 flex items-center justify-center gap-1.5 py-2.5 hover:bg-[#3A3B3C] rounded-md transition-colors text-[#1877F2]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path></svg>
                            Like
                        </button>
                        <button type="button" class="flex-1 flex items-center justify-center gap-1.5 py-2.5 hover:bg-[#3A3B3C] rounded-md transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            Comment
                        </button>
                        <button type="button" class="flex-1 flex items-center justify-center gap-1.5 py-2.5 hover:bg-[#3A3B3C] rounded-md transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                            Share
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
