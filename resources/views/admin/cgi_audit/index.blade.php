<x-app-layout>
    {{-- Global Notification System --}}
    <div x-data="{ 
            notifications: [], 
            add(message, type = 'info') { 
                const id = Date.now();
                this.notifications.push({ id, message, type });
                setTimeout(() => this.remove(id), 5000);
            },
            remove(id) { this.notifications = this.notifications.filter(n => n.id !== id); } 
        }" 
        @notify.window="add($event.detail.message, $event.detail.type)"
        class="fixed top-6 right-6 z-[1500] flex flex-col gap-3 w-80">
        <template x-for="n in notifications" :key="n.id">
            <div x-transition class="px-5 py-4 rounded-xl shadow-2xl border flex items-center gap-3 backdrop-blur-xl bg-[#0a0a0a]/80"
                 :class="n.type === 'success' ? 'border-emerald-500/20 text-emerald-400' : 'border-blue-500/20 text-blue-400'">
                <div class="flex-1 text-[10px] font-black uppercase tracking-widest leading-none" x-text="n.message"></div>
                <button @click="remove(n.id)" class="text-white/20 hover:text-white">✕</button>
            </div>
        </template>
    </div>

    <div class="max-w-full mx-auto bg-[#050505] min-h-screen p-4 sm:p-6" 
         x-data="{ 
             expandedUser: null, 
             openPromptModal: false, 
             activePromptText: '', 
             promptType: '',
             openAssetModal: false,
             activeAssetUrl: '',
             assetType: '',
             isDownloading: false,
             
             async forceDownload(url, filename) {
                 if(this.isDownloading) return;
                 this.isDownloading = true;
                 $dispatch('notify', { message: 'Preparing Download...', type: 'info' });

                 // 1. THE ABSOLUTE FIX FOR CLOUDINARY
                 if (url.includes('cloudinary.com')) {
                     let dlUrl = url;
                     if (dlUrl.includes('/upload/')) {
                         dlUrl = dlUrl.replace('/upload/', '/upload/fl_attachment/');
                     } else if (dlUrl.includes('/fetch/')) {
                         dlUrl = dlUrl.replace('/fetch/', '/fetch/fl_attachment/');
                     }
                     
                     const link = document.createElement('a');
                     link.href = dlUrl;
                     link.setAttribute('download', filename || 'neural-asset.png');
                     document.body.appendChild(link);
                     link.click();
                     document.body.removeChild(link);
                     $dispatch('notify', { message: 'Download Initiated', type: 'success' });
                     this.isDownloading = false;
                     return;
                 }

                 // 2. FOR LOCAL SERVER FILES
                 try {
                     const response = await fetch(url);
                     if (!response.ok) throw new Error('Network error');
                     const blob = await response.blob();
                     const blobUrl = window.URL.createObjectURL(blob);
                     
                     const link = document.createElement('a');
                     link.href = blobUrl;
                     link.download = filename || 'neural-asset.png';
                     document.body.appendChild(link);
                     link.click();
                     document.body.removeChild(link);
                     window.URL.revokeObjectURL(blobUrl);
                     $dispatch('notify', { message: 'Download Initiated', type: 'success' });

                 } catch (e) {
                     // 3. NO PREVIEWS ALLOWED FALLBACK
                     console.error('Fetch failed, forcing hard download link', e);
                     const link = document.createElement('a');
                     link.href = url;
                     link.setAttribute('download', filename || 'neural-asset.png');
                     document.body.appendChild(link);
                     link.click();
                     document.body.removeChild(link);
                     $dispatch('notify', { message: 'Download Forced', type: 'info' });
                 } finally {
                     this.isDownloading = false;
                 }
             }
         }">
        
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6 border-b border-white/5 pb-4">
            <div>
                <h1 class="text-sm font-black text-purple-400 tracking-[0.2em] uppercase flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Neural User Audit & Pipeline
                </h1>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mt-1">Monitor end-to-end asset assembly and social publishing</p>
            </div>
            
            <a href="{{ route('cgi.create') }}" class="text-[10px] font-black text-gray-400 hover:text-white uppercase tracking-widest transition-colors flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Studio
            </a>
        </div>

        {{-- Master User Table --}}
        <div class="bg-[#0a0a0a] border border-white/5 rounded-xl shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead class="bg-purple-500/5 border-b border-white/5">
                        <tr class="text-[9px] uppercase tracking-[0.2em] text-purple-300 font-bold">
                            <th class="px-6 py-4">User Identity</th>
                            <th class="px-6 py-4 text-center">Total Pipelines</th>
                            <th class="px-6 py-4 text-right">Latest Activity</th>
                            <th class="px-6 py-4 text-right">Expand Audit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        @foreach($users as $user)
                            <tr class="hover:bg-white/[0.02] transition-colors cursor-pointer group"
                                @click="expandedUser = (expandedUser === {{ $user->id }} ? null : {{ $user->id }})">
                                
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-purple-500/10 flex items-center justify-center border border-purple-500/30">
                                            <span class="text-purple-400 font-black text-sm">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <h4 class="text-white text-xs font-bold tracking-wide">{{ $user->name }}</h4>
                                            <p class="text-gray-500 text-[10px]">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-white/5 border border-white/10 rounded text-[10px] font-bold text-purple-300 shadow-inner">
                                        {{ $user->cgiGenerations->count() }} Assets
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4 text-right">
                                    <span class="text-[10px] text-gray-400 font-mono">{{ optional($user->cgiGenerations->first())->created_at ? $user->cgiGenerations->first()->created_at->timezone('Asia/Dhaka')->diffForHumans() : 'N/A' }}</span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center">
                                        <div class="w-8 h-8 rounded-full bg-white/5 group-hover:bg-purple-500/20 flex items-center justify-center transition-all">
                                            <svg class="w-4 h-4 text-gray-500 group-hover:text-purple-400 transition-all transform" 
                                                :class="expandedUser === {{ $user->id }} ? 'rotate-180 text-purple-500' : ''" 
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- Expanded Pipeline Panel --}}
                            <tr x-show="expandedUser === {{ $user->id }}" x-cloak x-collapse>
                                <td colspan="4" class="p-0 border-b-2 border-purple-500/30 bg-[#0d0d0d] shadow-inner">
                                    <div class="p-6">
                                        <h4 class="text-[10px] font-black tracking-widest text-purple-400 uppercase mb-4 border-b border-white/5 pb-2 flex items-center gap-2">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                            Pipeline Infrastructure Audit
                                        </h4>
                                        
                                        <div class="overflow-x-auto rounded-lg border border-white/5">
                                            <table class="w-full text-left min-w-[1000px]">
                                                <thead class="bg-black/50 text-[8px] uppercase tracking-[0.2em] text-gray-500">
                                                    <tr>
                                                        <th class="px-4 py-3">Directive</th>
                                                        <th class="px-4 py-3 text-center border-l border-white/5">1. Raw Prompts</th>
                                                        <th class="px-4 py-3 text-center border-l border-white/5">2. Base Render</th>
                                                        <th class="px-4 py-3 text-center border-l border-white/5">3. Branded Output</th>
                                                        <th class="px-4 py-3 text-center border-l border-white/5">4. Social Status</th>
                                                        <th class="px-4 py-3 text-right border-l border-white/5">Timestamp</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-white/5 bg-black/20">
                                                    @foreach($user->cgiGenerations as $gen)
                                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                                            
                                                            <td class="px-4 py-3 align-top w-48">
                                                                <span class="text-[11px] font-bold text-gray-300 block line-clamp-1">{{ $gen->product_name }}</span>
                                                                <span class="text-[9px] text-gray-600 line-clamp-1 italic mt-0.5">{{ $gen->marketing_angle }}</span>
                                                            </td>
                                                            
                                                            <td class="px-4 py-3 align-top text-center border-l border-white/5">
                                                                <div class="flex items-center justify-center gap-1 flex-wrap">
                                                                    <button @click.stop="openPromptModal = true; promptType = 'Image'; activePromptText = @js($gen->image_prompt)" class="px-2 py-1 bg-blue-500/10 hover:bg-blue-500 text-[8px] font-black text-blue-400 hover:text-white border border-blue-500/20 rounded transition-all uppercase tracking-widest">IMG</button>
                                                                    <button @click.stop="openPromptModal = true; promptType = 'Video'; activePromptText = @js($gen->video_prompt)" class="px-2 py-1 bg-pink-500/10 hover:bg-pink-500 text-[8px] font-black text-pink-400 hover:text-white border border-pink-500/20 rounded transition-all uppercase tracking-widest">VID</button>
                                                                    @if(!empty($gen->audio_prompt))
                                                                        <button @click.stop="openPromptModal = true; promptType = 'Audio'; activePromptText = @js($gen->audio_prompt)" class="px-2 py-1 bg-amber-500/10 hover:bg-amber-500 text-[8px] font-black text-amber-400 hover:text-white border border-amber-500/20 rounded transition-all uppercase tracking-widest">AUD</button>
                                                                    @endif
                                                                </div>
                                                            </td>

                                                            <td class="px-4 py-3 align-top text-center border-l border-white/5">
                                                                <div class="flex items-center justify-center gap-2">
                                                                    @if($gen->image_url)
                                                                        <button @click.stop="openAssetModal = true; assetType = 'image'; activeAssetUrl = '{{ str_starts_with($gen->image_url, 'http') ? $gen->image_url : asset('storage/' . $gen->image_url) }}'" 
                                                                            class="w-6 h-6 rounded bg-emerald-500/10 flex items-center justify-center text-emerald-400 hover:bg-emerald-500 hover:text-white border border-emerald-500/20 transition-all" title="View Base Image">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                                        </button>
                                                                    @else
                                                                        <span class="w-6 h-6 rounded bg-white/5 flex items-center justify-center text-gray-700 text-xs">-</span>
                                                                    @endif

                                                                    @if($gen->video_url)
                                                                        <button @click.stop="openAssetModal = true; assetType = 'video'; activeAssetUrl = '{{ str_starts_with($gen->video_url, 'http') ? $gen->video_url : asset('storage/' . $gen->video_url) }}'" 
                                                                            class="w-6 h-6 rounded bg-pink-500/10 flex items-center justify-center text-pink-400 hover:bg-pink-500 hover:text-white border border-pink-500/20 transition-all" title="View Base Video">
                                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                                        </button>
                                                                    @elseif($gen->video_status === 'failed')
                                                                        <span class="text-red-500 font-black text-[10px] cursor-help" title="{{ $gen->video_error_message }}">⚠ ERR</span>
                                                                    @else
                                                                        <span class="w-6 h-6 rounded bg-white/5 flex items-center justify-center text-gray-700 text-xs">-</span>
                                                                    @endif
                                                                </div>
                                                            </td>

                                                            <td class="px-4 py-3 align-top text-center border-l border-white/5">
                                                                <div class="flex items-center justify-center gap-2">
                                                                    @if($gen->branded_image_url)
                                                                        <button @click.stop="openAssetModal = true; assetType = 'image'; activeAssetUrl = '{{ str_starts_with($gen->branded_image_url, 'http') ? $gen->branded_image_url : asset('storage/' . $gen->branded_image_url) }}'" 
                                                                            class="px-2 py-1 bg-indigo-500/10 hover:bg-indigo-500 text-[8px] font-black text-indigo-400 hover:text-white border border-indigo-500/20 rounded transition-all uppercase tracking-widest flex items-center gap-1">
                                                                            IMG ✓
                                                                        </button>
                                                                    @else
                                                                        <span class="text-[8px] font-bold text-gray-700 uppercase tracking-widest">No Img</span>
                                                                    @endif

                                                                    @if($gen->branded_video_url)
                                                                        <button @click.stop="openAssetModal = true; assetType = 'video'; activeAssetUrl = '{{ str_starts_with($gen->branded_video_url, 'http') ? $gen->branded_video_url : asset('storage/' . $gen->branded_video_url) }}'" 
                                                                            class="px-2 py-1 bg-indigo-500/10 hover:bg-indigo-500 text-[8px] font-black text-indigo-400 hover:text-white border border-indigo-500/20 rounded transition-all uppercase tracking-widest flex items-center gap-1">
                                                                            VID ✓
                                                                        </button>
                                                                    @else
                                                                        <span class="text-[8px] font-bold text-gray-700 uppercase tracking-widest">No Vid</span>
                                                                    @endif
                                                                </div>
                                                            </td>

                                                            <td class="px-4 py-3 align-top text-center border-l border-white/5">
                                                                @if(!empty($gen->social_post_url) || $gen->is_published) 
                                                                    <div class="inline-flex items-center gap-1.5 px-2 py-1 bg-blue-500/10 border border-blue-500/20 rounded-md">
                                                                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_5px_#3b82f6] animate-pulse"></div>
                                                                        <span class="text-[8px] font-black uppercase tracking-widest text-blue-400">Posted</span>
                                                                    </div>
                                                                @else
                                                                    <span class="text-[8px] font-bold text-gray-600 uppercase tracking-widest bg-white/5 px-2 py-1 rounded-md">Unpublished</span>
                                                                @endif
                                                            </td>

                                                            <td class="px-4 py-3 align-top text-right border-l border-white/5">
                                                                {{-- CONVERTED TO DHAKA TIME --}}
                                                                <span class="text-[9px] text-gray-400 block font-mono">{{ $gen->created_at->timezone('Asia/Dhaka')->format('M d, Y') }}</span>
                                                                <span class="text-[8px] text-gray-600 font-mono">{{ $gen->created_at->timezone('Asia/Dhaka')->format('h:i A') }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01]">
                {{ $users->links('pagination::tailwind') }}
            </div>
        </div>

        {{-- PROMPT VIEWER MODAL --}}
        <template x-teleport="body">
            <div x-show="openPromptModal" class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6 bg-[#050505]/95 backdrop-blur-md" x-cloak>
                <div class="bg-[#0a0a0a] border border-white/10 w-full max-w-2xl rounded-xl shadow-2xl overflow-hidden animate-in zoom-in duration-200" @click.away="openPromptModal = false">
                    <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]"
                         :class="{
                             'bg-blue-500/5 border-b-blue-500/20': promptType === 'Image',
                             'bg-pink-500/5 border-b-pink-500/20': promptType === 'Video',
                             'bg-amber-500/5 border-b-amber-500/20': promptType === 'Audio'
                         }">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.3em] flex items-center gap-2"
                            :class="{
                                'text-blue-400': promptType === 'Image',
                                'text-pink-400': promptType === 'Video',
                                'text-amber-400': promptType === 'Audio'
                            }">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span x-text="promptType + ' Prompt Payload'"></span>
                        </h3>
                        <button @click="openPromptModal = false" class="text-gray-500 hover:text-white text-lg transition-colors">✕</button>
                    </div>

                    <div class="p-6">
                        <div class="bg-black p-5 rounded-lg border border-white/5 font-mono text-xs text-gray-300 max-h-[50vh] overflow-y-auto whitespace-pre-wrap leading-relaxed shadow-inner" 
                             :class="{
                                 'selection:bg-blue-500/30': promptType === 'Image',
                                 'selection:bg-pink-500/30': promptType === 'Video',
                                 'selection:bg-amber-500/30': promptType === 'Audio'
                             }"
                             x-text="activePromptText || 'No payload data registered.'">
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-end">
                        <button @click="openPromptModal = false" class="px-6 py-2 bg-white/5 border border-white/10 text-gray-300 rounded text-[10px] font-black uppercase tracking-widest hover:bg-white/10 hover:text-white transition-all">Dismiss</button>
                    </div>
                </div>
            </div>
        </template>

        {{-- NEW CINEMATIC ASSET VIEWER MODAL --}}
        <template x-teleport="body">
            <div x-show="openAssetModal" 
                 class="fixed inset-0 z-[1000] flex flex-col items-center justify-center p-4 sm:p-10 bg-[#050505]/95 backdrop-blur-2xl" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 x-cloak>
                
                {{-- Invisible Click-Away Overlay --}}
                <div class="absolute inset-0" @click="openAssetModal = false; activeAssetUrl = ''"></div>

                {{-- Floating Close Button (Top Right) --}}
                <button @click="openAssetModal = false; activeAssetUrl = ''" 
                        class="absolute top-6 right-6 z-50 px-5 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-[10px] font-black text-white uppercase tracking-[0.2em] transition-all backdrop-blur-md flex items-center gap-2">
                    CLOSE PIPELINE ✕
                </button>

                {{-- Centered Cinematic Media Container (SHRINK-WRAPPED FRAME) --}}
                <div class="relative z-10 flex flex-col items-center justify-center animate-in zoom-in duration-300 max-w-[95vw]">
                    
                    {{-- The Asset Frame (Inline-flex forces it to match the image dimensions) --}}
                    <div class="relative rounded-2xl overflow-hidden border border-white/10 shadow-[0_0_80px_rgba(0,0,0,0.8)] bg-black inline-flex justify-center items-center">
                        <template x-if="assetType === 'image'">
                            <img :src="activeAssetUrl" class="max-w-full max-h-[80vh] object-contain block">
                        </template>

                        <template x-if="assetType === 'video'">
                            <video :src="activeAssetUrl" controls autoplay class="max-w-full max-h-[80vh] object-contain block"></video>
                        </template>
                    </div>

                    {{-- Floating Download Button (Bottom) --}}
                    <button @click="forceDownload(activeAssetUrl, 'neural-asset.png')" 
                            :disabled="isDownloading"
                            class="mt-8 px-6 py-3 bg-purple-600/10 hover:bg-purple-600/30 border border-purple-500/30 rounded-full text-[10px] font-black text-purple-300 hover:text-white uppercase tracking-[0.2em] transition-all backdrop-blur-md flex items-center gap-3 shadow-2xl disabled:opacity-50">
                        
                        <span x-show="!isDownloading" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download Asset
                        </span>
                        
                        <span x-show="isDownloading" class="flex items-center gap-2" x-cloak>
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Downloading...
                        </span>
                    </button>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>