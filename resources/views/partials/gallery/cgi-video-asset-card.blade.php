@php
    $video = $asset->model;
    $mediaUrl = $asset->url;
    $posterUrl = $video->image_url
        ? \App\Support\PublicMediaUrl::forMedia($video->image_url)
        : $mediaUrl;
    $downloadName = $asset->variant . '-' . $video->id . '.mp4';
@endphp

@switch($asset->variant)
    @case('branded')
        <div x-show="(clientFilter === 'all' || clientFilter == '{{ $asset->user_id }}') && (filter === 'all' || filter === 'branded')"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="group bg-[#0a0a0a] border border-blue-500/20 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-blue-500">
            <div class="relative aspect-video bg-black cursor-pointer" @click="openModal=true; currentVideo='{{ $mediaUrl }}'">
                <img src="{{ $posterUrl }}" class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity" loading="lazy">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-2xl"><svg class="w-5 h-5 fill-current ml-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                </div>
                <span class="absolute top-4 left-4 px-2 py-1 bg-blue-600 text-[8px] font-black text-white uppercase rounded tracking-widest">Logo Applied</span>
            </div>
            <div class="p-5">
                <h3 class="text-[11px] font-black text-white uppercase truncate">{{ $video->product_name }}</h3>
                <div class="mt-4 pt-4 border-t border-white/5 flex justify-between items-center">
                    <span class="text-[8px] text-blue-500 font-bold uppercase tracking-widest">Master Production</span>
                    <button type="button" @click.stop="forceDownload('{{ $mediaUrl }}', '{{ $downloadName }}')"
                            class="text-gray-600 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        @break

    @case('raw')
        <div x-show="(clientFilter === 'all' || clientFilter == '{{ $asset->user_id }}') && (filter === 'all' || filter === 'raw')"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="group bg-[#0a0a0a] border border-pink-500/10 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-pink-500/40">
            <div class="relative aspect-video bg-black cursor-pointer" @click="openModal=true; currentVideo='{{ $mediaUrl }}'">
                <img src="{{ $posterUrl }}" class="w-full h-full object-cover opacity-40 group-hover:opacity-100 transition-opacity" loading="lazy">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-12 h-12 bg-pink-600 text-white rounded-full flex items-center justify-center shadow-2xl"><svg class="w-5 h-5 fill-current ml-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                </div>
                <span class="absolute top-4 left-4 px-2 py-1 bg-pink-600 text-[8px] font-black text-white uppercase rounded tracking-widest">No Logo</span>
            </div>
            <div class="p-5">
                <h3 class="text-[11px] font-black text-white uppercase truncate">{{ $video->product_name }}</h3>
                <div class="mt-4 pt-4 border-t border-white/5 flex justify-between items-center">
                    <span class="text-[8px] text-gray-500 font-bold uppercase tracking-widest">Raw Render</span>
                    <button type="button" @click.stop="forceDownload('{{ $mediaUrl }}', '{{ $downloadName }}')"
                            class="text-gray-600 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        @break

    @case('merged')
        <div x-show="(clientFilter === 'all' || clientFilter == '{{ $asset->user_id }}') && (filter === 'all' || filter === 'merged')"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="group bg-[#0a0a0a] border border-orange-500/10 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-orange-500/40">
            <div class="relative aspect-video bg-black cursor-pointer" @click="openModal=true; currentVideo='{{ $mediaUrl }}'">
                <img src="{{ $posterUrl }}" class="w-full h-full object-cover opacity-50 group-hover:opacity-100 transition-opacity" loading="lazy">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-12 h-12 bg-orange-600 text-white rounded-full flex items-center justify-center shadow-2xl"><svg class="w-5 h-5 fill-current ml-1" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>
                </div>
                <span class="absolute top-4 left-4 px-2 py-1 bg-orange-600 text-[8px] font-black text-white uppercase rounded tracking-widest">Merged</span>
            </div>
            <div class="p-5">
                <h3 class="text-[11px] font-black text-white uppercase truncate">{{ $video->product_name }}</h3>
                <div class="mt-4 pt-4 border-t border-white/5 flex justify-between items-center">
                    <span class="text-[8px] text-orange-500 font-bold uppercase tracking-widest">Merged Production</span>
                    <button type="button" @click.stop="forceDownload('{{ $mediaUrl }}', '{{ $downloadName }}')"
                            class="text-gray-600 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </button>
                </div>
                @php
                    $mergedVidApproval = $requiresApproval
                        ? \App\Http\Controllers\ApprovalController::approvalRecord('cgi', $video->id, 'video', 'merged')
                        : null;
                @endphp
                @include('partials.approval-control', [
                    'requiresApproval' => $requiresApproval,
                    'genId'     => $video->id,
                    'mediaUrl'  => $mediaUrl,
                    'mediaType' => 'video',
                    'variant'   => 'merged',
                    'isBranded' => false,
                    'approval'  => $mergedVidApproval,
                ])
            </div>
        </div>
        @break
@endswitch
