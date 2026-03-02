<x-app-layout>
    {{-- Global Notification System (Toasts) --}}
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
        class="fixed top-6 right-6 z-[1000] flex flex-col gap-3 w-80">
        
        <template x-for="n in notifications" :key="n.id">
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-8"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="px-5 py-4 rounded-xl shadow-2xl border flex items-center gap-3 backdrop-blur-xl transition-all"
                 :class="{
                    'bg-emerald-500/10 border-emerald-500/20 text-emerald-400': n.type === 'success',
                    'bg-red-500/10 border-red-500/20 text-red-400': n.type === 'error',
                    'bg-blue-500/10 border-blue-500/20 text-blue-400': n.type === 'info'
                 }">
                <div class="flex-1 text-[10px] font-black uppercase tracking-widest leading-none" x-text="n.message"></div>
                <button @click="remove(n.id)" class="text-white/20 hover:text-white">✕</button>
            </div>
        </template>
    </div>

    {{-- Main Workspace --}}
    <div class="max-w-full mx-auto bg-[#050505] min-h-screen">
        
        {{-- Slim Top Toolbar --}}
        <div class="flex items-center justify-between px-8 py-4 border-b border-white/5 bg-[#0a0a0a]">
            <div>
                <h1 class="text-[13px] font-black text-white tracking-[0.2em] uppercase flex items-center gap-3">
                    <span class="w-1 h-5 bg-blue-600 rounded-full"></span>
                    CGI Directive Studio
                </h1>
                <p class="text-[9px] text-gray-600 font-bold uppercase tracking-widest mt-0.5">Neural Asset Pipeline v3.2</p>
            </div>
            <a href="{{ route('cgi.create') }}"
                class="flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-[10px] font-black rounded-md transition-all uppercase tracking-widest shadow-lg shadow-blue-600/20">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                New Directive
            </a>
        </div>

        <div class="p-8">
            <div class="bg-[#0a0a0a] border border-white/5 rounded-xl overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white/[0.02] border-b border-white/5">
                            <tr class="text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold">
                                <th class="px-8 py-5">Directive Identity</th>
                                <th class="px-8 py-5 text-center">Neural Prompts</th>
                                <th class="px-8 py-5">Render Engine</th>
                                <th class="px-8 py-5 text-right">Control</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-white/[0.03]">
                            @foreach($generations as $gen)
                            <tr x-data="{
                                status: '{{ $gen->status }}',
                                imageStatus: '{{ $gen->image_status }}',
                                videoStatus: '{{ $gen->video_status ?? 'pending' }}',
                                openModal: null, isEditing: false, isSaving: false, isTriggering: false, isVideoTriggering: false,
                                
                                liveImagePrompt: @js($gen->image_prompt),
                                liveVideoPrompt: @js($gen->video_prompt),
                                liveAudioPrompt: @js($gen->audio_prompt),
                                
                                inputImage: @js($gen->image_prompt), 
                                inputVideo: @js($gen->video_prompt), 
                                inputAudio: @js($gen->audio_prompt),

                                imageUrl: '{{ $gen->image_url }}', videoUrl: '{{ $gen->video_url }}',

                                async saveChanges(){
                                    if(this.isSaving) return;
                                    this.isSaving = true;
                                    try {
                                        const response = await fetch('/cgi/{{ $gen->id }}/update-prompts', {
                                            method: 'PUT',
                                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                            body: JSON.stringify({ 
                                                image_prompt: this.inputImage, 
                                                video_prompt: this.inputVideo, 
                                                audio_prompt: this.inputAudio 
                                            })
                                        });
                                        const result = await response.json();
                                        if(response.ok && result.success){
                                            this.liveImagePrompt = result.image_prompt;
                                            this.liveVideoPrompt = result.video_prompt;
                                            this.liveAudioPrompt = result.audio_prompt;
                                            this.isEditing = false;
                                            $dispatch('notify', { message: 'Directive Synced Successfully', type: 'success' });
                                        }
                                    } catch(e) { $dispatch('notify', { message: 'Network Sync Error', type: 'error' }); }
                                    finally { this.isSaving = false; }
                                },

                                async triggerMakePicture(){
                                    if(!confirm('Initiate Image Render?')) return;
                                    this.isTriggering = true;
                                    try {
                                        const response = await fetch('/cgi/{{ $gen->id }}/make-picture', {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                        });
                                        const data = await response.json();
                                        if(data.success) { 
                                            this.imageStatus = 'making'; 
                                            $dispatch('notify', { message: 'Image Generation Queued', type: 'info' });
                                        }
                                    } catch(e) { $dispatch('notify', { message: 'Render Server Offline', type: 'error' }); }
                                    finally { this.isTriggering = false; }
                                },

                                async triggerMakeVideo(){
                                    if(!confirm('Initiate Video Pipeline?')) return;
                                    this.isVideoTriggering = true;
                                    try {
                                        const response = await fetch('/cgi/{{ $gen->id }}/make-video', {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                        });
                                        const data = await response.json();
                                        if(data.success) { 
                                            this.videoStatus = 'making'; 
                                            $dispatch('notify', { message: 'Video Synthesis Started', type: 'info' });
                                        }
                                    } catch(e) { $dispatch('notify', { message: 'Pipeline Error', type: 'error' }); }
                                    finally { this.isVideoTriggering = false; }
                                }
                            }"
                            x-init="if(status==='processing'){setInterval(()=>{location.reload();},15000);}"
                            x-effect="if(imageStatus==='making' || videoStatus==='making'){setInterval(()=>{location.reload();},10000);}"
                            class="hover:bg-white/[0.01] transition-colors"
                            >

                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-black text-gray-100 uppercase tracking-wider">{{ $gen->product_name }}</span>
                                        <span class="text-[10px] text-gray-500 mt-0.5 font-bold">{{ $gen->marketing_angle }}</span>
                                        <span class="mt-2 text-[8px] font-black text-blue-500/80 uppercase tracking-[0.2em]">{{ $gen->visual_prop }}</span>
                                    </div>
                                </td>

                                <td class="px-8 py-6 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button @click="openModal='image'; isEditing=false;" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-[9px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Image Prompt</button>
                                        <button @click="openModal='video'; isEditing=false;" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-[9px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Video Prompt</button>
                                        <button @click="openModal='audio'; isEditing=false;" class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-[9px] font-black text-gray-400 border border-white/5 rounded transition-all uppercase tracking-widest">Audio Prompt</button>
                                    </div>
                                </td>

                                <td class="px-8 py-6">
                                    @if($gen->status == 'processing')
                                        <div class="flex items-center gap-3 px-4 py-2 bg-yellow-500/5 border border-yellow-500/10 rounded-lg">
                                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse shadow-[0_0_8px_rgba(234,179,8,0.6)]"></span>
                                            <span class="text-[9px] font-black text-yellow-500 uppercase tracking-widest">Generating DNA</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-3">
                                            {{-- IMAGE BUTTON --}}
                                            <button @click="imageUrl ? openModal='preview' : triggerMakePicture()" :disabled="imageStatus==='making' || isTriggering"
                                                class="h-9 px-5 text-[10px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-2 border shadow-lg"
                                                :class="{
                                                    'bg-emerald-500 border-emerald-500 text-black animate-pulse shadow-[0_0_15px_rgba(16,185,129,0.4)]': imageStatus === 'making',
                                                    'bg-emerald-500/10 border-emerald-500/20 text-emerald-500 hover:bg-emerald-500 hover:text-white': (imageUrl && imageStatus !== 'making'),
                                                    'bg-white text-black border-transparent hover:bg-blue-600 hover:text-white': (!imageUrl && imageStatus !== 'making')
                                                }">
                                                <span x-text="imageStatus==='making' ? 'RENDERING...' : (imageUrl ? 'View Pic' : 'Make Pic')"></span>
                                            </button>

                                            {{-- VIDEO BUTTON - UPDATED COLORS FOR VISIBILITY --}}
                                            <button @click="videoUrl ? openModal='videoPreview' : triggerMakeVideo()" :disabled="videoStatus==='making' || isVideoTriggering || !imageUrl"
                                                class="h-9 px-5 text-[10px] font-black rounded transition-all uppercase tracking-widest flex items-center gap-2 border disabled:opacity-10 shadow-lg"
                                                :class="{
                                                    'bg-pink-500 border-pink-500 text-black animate-pulse shadow-[0_0_15px_rgba(236,72,153,0.4)]': videoStatus === 'making',
                                                    'bg-pink-500/10 border-pink-500/20 text-pink-500 hover:bg-pink-500 hover:text-white shadow-pink-500/10': (videoUrl && videoStatus !== 'making'),
                                                    'bg-[#1a1a1a] text-gray-400 border-white/10 hover:bg-white hover:text-black': (!videoUrl && videoStatus !== 'making')
                                                }">
                                                <span x-text="videoStatus==='making' ? 'SYNTHESIZING...' : (videoUrl ? 'View Video' : 'Make Video')"></span>
                                            </button>
                                        </div>
                                    @endif

                                    {{-- Directive Modal --}}
                                    <template x-teleport="body">
                                        <div x-show="openModal" class="fixed inset-0 z-[999] flex items-center justify-center p-6 bg-black/95 backdrop-blur-md" x-cloak>
                                            <div x-show="['image','video','audio'].includes(openModal)" class="bg-[#0a0a0a] border border-white/10 w-full max-w-xl rounded-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                                                <div class="px-6 py-4 border-b border-white/5 flex justify-between items-center bg-white/[0.02]">
                                                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]" x-text="openModal + ' Directive Definition'"></h3>
                                                    <button @click="openModal=null" class="text-gray-500 hover:text-white text-lg">✕</button>
                                                </div>
                                                <div class="p-8">
                                                    {{-- READ ONLY VIEW --}}
                                                    <div x-show="!isEditing" class="bg-black p-5 rounded border border-white/5 font-mono text-xs text-gray-400 max-h-[40vh] overflow-y-auto whitespace-pre-wrap leading-relaxed shadow-inner" x-text="openModal==='image' ? liveImagePrompt : (openModal==='video' ? liveVideoPrompt : liveAudioPrompt)"></div>
                                                    
                                                    {{-- EDIT VIEW --}}
                                                    <div x-show="isEditing">
                                                        <template x-if="openModal==='image'">
                                                            <textarea x-model="inputImage" class="w-full h-48 bg-black border border-white/10 rounded p-5 text-white font-mono text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all"></textarea>
                                                        </template>
                                                        <template x-if="openModal==='video'">
                                                            <textarea x-model="inputVideo" class="w-full h-48 bg-black border border-white/10 rounded p-5 text-white font-mono text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all"></textarea>
                                                        </template>
                                                        <template x-if="openModal==='audio'">
                                                            <textarea x-model="inputAudio" class="w-full h-48 bg-black border border-white/10 rounded p-5 text-white font-mono text-xs focus:ring-1 focus:ring-blue-500 outline-none transition-all"></textarea>
                                                        </template>
                                                    </div>
                                                </div>
                                                <div class="px-8 py-5 border-t border-white/5 flex justify-end gap-3 bg-white/[0.01]">
                                                    {{-- LOGIC FOR EDIT BUTTON VISIBILITY --}}
                                                    <template x-if="(openModal==='image' && !imageUrl) || ( (openModal==='video' || openModal==='audio') && !videoUrl )">
                                                        <div class="flex gap-2">
                                                            <button @click="isEditing=!isEditing" class="px-5 py-2.5 bg-gray-800 text-white rounded text-[10px] font-black uppercase tracking-widest hover:bg-gray-700 transition-colors">
                                                                <span x-text="isEditing?'Cancel':'Modify Prompt'"></span>
                                                            </button>
                                                            <button x-show="isEditing" @click="saveChanges()" :disabled="isSaving" class="px-5 py-2.5 bg-blue-600 text-white rounded text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-600/20">Sync Data</button>
                                                        </div>
                                                    </template>
                                                    
                                                    {{-- LOGIC FOR LOCKED MESSAGE --}}
                                                    <template x-if="(openModal==='image' && imageUrl) || ( (openModal==='video' || openModal==='audio') && videoUrl )">
                                                        <span class="text-[9px] font-black text-gray-600 italic uppercase tracking-widest">Directive Finalized & Locked</span>
                                                    </template>
                                                </div>
                                            </div>

                                            {{-- Asset Preview --}}
                                            <div x-show="openModal==='preview' || openModal==='videoPreview'" class="relative w-full max-w-4xl animate-in fade-in slide-in-from-bottom-4 duration-300">
                                                <button @click="openModal=null" class="absolute -top-12 right-0 text-white text-[10px] font-black uppercase tracking-[0.2em] bg-white/5 px-4 py-2 rounded-full hover:bg-red-500 transition-all">Close Pipeline ✕</button>
                                                <div class="bg-black border border-white/10 rounded-xl overflow-hidden shadow-2xl">
                                                    <template x-if="openModal==='preview'">
                                                        <img :src="imageUrl" class="w-full max-h-[80vh] object-contain">
                                                    </template>
                                                    <template x-if="openModal==='videoPreview'">
                                                        <video :src="videoUrl" class="w-full max-h-[80vh] object-contain" controls autoplay loop></video>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </td>

                                <td class="px-8 py-6 text-right">
                                    <form action="{{ route('cgi.destroy', $gen->id) }}" method="POST" onsubmit="return confirm('Purge directive?');">
                                        @csrf @method('DELETE')
                                        <button class="text-gray-800 hover:text-red-500 transition-colors p-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        body { background-color: #050505; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: #050505; }
        ::-webkit-scrollbar-thumb { background: #1a1a1a; border-radius: 10px; }
    </style>
</x-app-layout>