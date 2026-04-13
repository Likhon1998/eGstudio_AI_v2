<x-app-layout>
    {{-- Main Wrapper with Alpine Data for the FB Modal --}}
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen"
         x-data="{ 
            showFBPreview: false, 
            activePost: {
                mediaUrl: '',
                mediaType: '',
                caption: '',
                date: ''
            }
         }">
        
        {{-- Slim Top Toolbar --}}
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 border-b border-white/5 bg-[#0a0a0a]">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-indigo-600 rounded-full shadow-[0_0_10px_rgba(79,70,229,0.5)]"></span>
                    Social Post History
                 </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">Broadcast Archive</p>
            </div>
            
            <a href="{{ route('cgi.index') }}"
                class="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white text-[9px] font-black rounded-md transition-all uppercase tracking-widest border border-white/10 shadow-lg">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Studio
            </a>
        </div>

        <div class="p-4 sm:p-6">
            <div class="bg-[#0a0a0a] border border-white/5 rounded-xl overflow-hidden shadow-2xl">
               <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white/[0.02] border-b border-white/5">
                             <tr class="text-[9px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                                <th class="px-4 sm:px-6 py-4">Media</th>
                                <th class="px-4 sm:px-6 py-4">Product Context</th>
                                <th class="px-4 sm:px-6 py-4">Caption Details</th>
                                <th class="px-4 sm:px-6 py-4">Platform</th>
                                <th class="px-4 sm:px-6 py-4 text-right">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/[0.03]">
                            @forelse($posts as $post)
                                <tr class="hover:bg-white/[0.01] transition-colors">
                                    
                                    {{-- MEDIA THUMBNAIL (TRIGGER 1: CLICKABLE IMAGE) --}}
                                    <td class="px-4 sm:px-6 py-4 align-top w-24">
                                        <div @click="
                                                activePost = {
                                                    mediaUrl: '{{ $post->media_url }}',
                                                    mediaType: '{{ $post->media_type }}',
                                                    caption: {{ json_encode($post->caption ?? '') }},
                                                    {{-- FIX: Using published_at time for the popup --}}
                                                    date: '{{ $post->published_at ? $post->published_at->format('F j \a\t g:i A') : $post->created_at->format('F j \a\t g:i A') }}'
                                                }; 
                                                showFBPreview = true;
                                             " 
                                             class="w-20 h-20 rounded-lg overflow-hidden bg-black border border-white/10 relative group shadow-lg cursor-pointer">
                                            
                                            @if($post->media_type === 'video')
                                                <video src="{{ $post->media_url }}" class="w-full h-full object-cover opacity-80" muted loop playsinline onmouseover="this.play()" onmouseout="this.pause()"></video>
                                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none group-hover:opacity-0 transition-opacity">
                                                    <div class="bg-black/50 backdrop-blur-sm p-1.5 rounded-full text-white">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                                    </div>
                                                </div>
                                            @else
                                                <img src="{{ $post->media_url }}" class="w-full h-full object-cover">
                                            @endif

                                            {{-- Hover Preview Overlay --}}
                                            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center transition-all duration-200">
                                                <svg class="w-5 h-5 text-blue-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                <span class="text-[7px] font-black text-white uppercase tracking-widest">FB Preview</span>
                                            </div>

                                            @if($post->is_branded)
                                                <div class="absolute top-1 right-1 bg-blue-600 px-1.5 py-0.5 rounded shadow text-[7px] font-black text-white uppercase tracking-widest pointer-events-none">
                                                    Branded
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- CONTEXT --}}
                                    <td class="px-4 sm:px-6 py-4 align-top">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-xs font-black text-gray-100 uppercase tracking-wider leading-tight">{{ $post->generation->product_name ?? 'Unknown Product' }}</span>
                                            {{-- FIX: Show published_at time in the table if it exists --}}
                                            <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest">
                                                {{ $post->published_at ? $post->published_at->format('M d, Y • h:i A') : $post->created_at->format('M d, Y • h:i A') }}
                                            </span>
                                        </div>
                                    </td>

                                    {{-- CAPTION --}}
                                    <td class="px-4 sm:px-6 py-4 align-top max-w-xs">
                                        <div class="text-xs text-gray-400 leading-relaxed bg-white/[0.02] p-3 rounded-lg border border-white/5 shadow-inner italic line-clamp-3">
                                            "{{ $post->caption ?? 'No caption provided' }}"
                                        </div>
                                    </td>

                                    {{-- PLATFORM --}}
                                    <td class="px-4 sm:px-6 py-4 align-top">
                                        @if(strtolower($post->platform) === 'facebook')
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-[#1877F2]/10 border border-[#1877F2]/20 rounded-md text-[#1877F2] shadow-sm">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path></svg>
                                                <span class="text-[9px] font-black uppercase tracking-widest">Facebook</span>
                                            </div>
                                        @else
                                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">{{ $post->platform }}</span>
                                        @endif
                                    </td>

                                    {{-- STATUS & TRIGGER 2 --}}
                                    <td class="px-4 sm:px-6 py-4 text-right align-top">
                                        <div class="flex flex-col items-end gap-2">
                                            @if($post->status === 'published')
                                                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-full text-[9px] font-black uppercase tracking-widest shadow-lg">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.8)]"></span>
                                                    Published
                                                </div>
                                                
                                                {{-- TRIGGER 2: TEXT LINK --}}
                                                <button @click="
                                                    activePost = {
                                                        mediaUrl: '{{ $post->media_url }}',
                                                        mediaType: '{{ $post->media_type }}',
                                                        caption: {{ json_encode($post->caption ?? '') }},
                                                        {{-- FIX: Using published_at time for the popup --}}
                                                        date: '{{ $post->published_at ? $post->published_at->format('F j \a\t g:i A') : $post->created_at->format('F j \a\t g:i A') }}'
                                                    }; 
                                                    showFBPreview = true;
                                                " class="text-[9px] font-bold text-blue-400 hover:text-blue-300 uppercase tracking-widest flex items-center gap-1 transition-colors">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                    View Post
                                                </button>

                                            @elseif($post->status === 'pending' || $post->status === 'sent_to_n8n')
                                                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-500/10 border border-blue-500/20 text-blue-400 rounded-full text-[9px] font-black uppercase tracking-widest shadow-lg">
                                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    Processing
                                                </div>
                                            @elseif($post->status === 'n8n_rejected' || $post->status === 'connection_failed')
                                                <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-500/10 border border-red-500/20 text-red-400 rounded-full text-[9px] font-black uppercase tracking-widest shadow-lg">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-[0_0_5px_rgba(239,68,68,0.8)]"></span>
                                                    Failed
                                                </div>
                                            @else
                                                <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">{{ $post->status }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center border-t border-white/5">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 rounded-full bg-white/5 border border-white/10 flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            </div>
                                            <p class="text-sm font-black text-gray-400 uppercase tracking-widest">No Broadcasts Found</p>
                                            <p class="text-[10px] font-medium text-gray-600 uppercase tracking-widest mt-2">You haven't posted any media to social channels yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- FACEBOOK STYLE POST MODAL (Teleported so it floats above everything) --}}
        <template x-teleport="body">
            <div x-show="showFBPreview" x-cloak 
                 class="fixed inset-0 z-[3000] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md" 
                 @click.away="showFBPreview = false"
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0">
                
                {{-- Facebook Dark Mode Post Container --}}
                <div x-show="showFBPreview"
                     class="bg-[#242526] w-full max-w-[500px] rounded-xl shadow-[0_0_50px_rgba(0,0,0,0.8)] border border-[#3E4042] overflow-hidden flex flex-col max-h-[90vh]"
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-8 scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-8 scale-95">
                    
                    {{-- FB Header (Avatar, Name, Date) --}}
                    <div class="px-4 py-3 flex items-center gap-3 shrink-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#1877F2] to-blue-400 flex items-center justify-center text-white font-bold shadow-inner">
                            {{ substr(auth()->user()->name ?? 'C', 0, 1) }}
                        </div>
                        <div class="flex-1">
                            <div class="text-[#E4E6EB] font-bold text-[15px] leading-tight hover:underline cursor-pointer">{{ auth()->user()->name ?? 'CGI Studio' }}</div>
                            <div class="text-[#B0B3B8] text-[13px] flex items-center gap-1 mt-0.5">
                                <span x-text="activePost.date"></span>
                                <span>·</span>
                                <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm6.94 6h-3.8c-.3-1.63-.8-3.14-1.45-4.42A8.04 8.04 0 0118.94 8zM12 4.04c.83 1.25 1.48 2.65 1.9 4.16h-3.8c.42-1.51 1.07-2.91 1.9-4.16zM4.26 14A8.06 8.06 0 014 12c0-.69.1-1.36.26-2h3.45c-.06.66-.11 1.32-.11 2 0 .68.05 1.34.11 2H4.26zm1.44 2h3.8c.3 1.63.8 3.14 1.45 4.42A8.04 8.04 0 015.7 16zm3.8-8H5.7a8.04 8.04 0 013.25-4.42C8.3 4.86 7.8 6.37 7.5 8zm2.6 11.96c-.83-1.25-1.48-2.65-1.9-4.16h3.8c-.42 1.51-1.07 2.91-1.9 4.16zM14.19 14H9.81c-.07-.65-.11-1.31-.11-2 0-.69.04-1.35.11-2h4.38c.07.65.11 1.31.11 2 0 .69-.04 1.35-.11 2zm1.86 6.42c.65-1.28 1.15-2.79 1.45-4.42h3.8a8.04 8.04 0 01-5.25 4.42zM16.4 14c.06-.66.11-1.32.11-2 0-.68-.05-1.34-.11-2h3.45a8.06 8.06 0 01.26 2c0 .69-.1 1.36-.26 2h-3.45z"></path></svg>
                            </div>
                        </div>
                        <button @click="showFBPreview = false" class="text-[#B0B3B8] hover:bg-[#3A3B3C] p-2 rounded-full transition-colors self-start">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    {{-- FB Caption (Scrollable if long) --}}
                    <div class="px-4 pb-3 text-[#E4E6EB] text-[15px] whitespace-pre-wrap leading-snug shrink-0 overflow-y-auto max-h-[150px] custom-scrollbar" x-text="activePost.caption || 'No caption provided.'"></div>

                    {{-- FB Media Box --}}
                    <div class="w-full bg-black flex-1 overflow-hidden flex items-center justify-center border-y border-[#3E4042]/50">
                        <template x-if="activePost.mediaType === 'image'">
                            <img :src="activePost.mediaUrl" class="w-full h-auto max-h-[400px] object-contain">
                        </template>
                        <template x-if="activePost.mediaType === 'video'">
                            <video :src="activePost.mediaUrl" class="w-full h-auto max-h-[400px] object-contain" controls autoplay muted loop playsinline></video>
                        </template>
                    </div>

                    {{-- FB Stats & Actions Footer --}}
                    <div class="px-4 py-2 shrink-0">
                        {{-- Fake Stats --}}
                        <div class="flex items-center justify-between text-[#B0B3B8] text-[13px] border-b border-[#3E4042] pb-2 mb-1">
                            <div class="flex items-center gap-1.5 hover:underline cursor-pointer">
                                <div class="bg-[#1877F2] rounded-full p-[3px] shadow-sm"><svg class="w-3 h-3 text-white fill-current" viewBox="0 0 24 24"><path d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path></svg></div>
                                <span>You</span>
                            </div>
                            <div class="hover:underline cursor-pointer">0 comments</div>
                        </div>

                        {{-- Fake Action Buttons --}}
                        <div class="flex items-center justify-between text-[#B0B3B8] font-semibold text-[14px]">
                            <button class="flex-1 flex items-center justify-center gap-2 py-2 hover:bg-[#3A3B3C] rounded-md transition-colors text-[#1877F2]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path></svg> 
                                Like
                            </button>
                            <button class="flex-1 flex items-center justify-center gap-2 py-2 hover:bg-[#3A3B3C] rounded-md transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg> 
                                Comment
                            </button>
                            <button class="flex-1 flex items-center justify-center gap-2 py-2 hover:bg-[#3A3B3C] rounded-md transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg> 
                                Share
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #3E4042;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</x-app-layout>