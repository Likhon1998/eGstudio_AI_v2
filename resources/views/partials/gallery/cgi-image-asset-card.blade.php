@php
    $image = $asset->model;
    $mediaUrl = $asset->url;
    $downloadName = $asset->variant . '-' . $image->id;
@endphp

@switch($asset->variant)
    @case('raw')
        <div x-show="(clientFilter === 'all' || clientFilter == '{{ $asset->user_id }}') && (filter === 'all' || filter === 'raw')"
             x-transition:enter="transition ease-out duration-300"
             class="group bg-[#0a0a0a] border border-white/5 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-white/20">
            <div class="relative aspect-square bg-black cursor-pointer overflow-hidden"
                 @click="openModal = true; currentImage = @js($mediaUrl); $dispatch('notify', {message: 'Enlarging Raw Still', type: 'info'})">
                <img src="{{ $mediaUrl }}" alt="{{ $image->product_name }}" class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-all duration-500" loading="lazy"
                     onerror="this.src=''; this.classList.add('opacity-30'); this.alt='Image unavailable';">
                <div class="absolute top-4 left-4">
                    <span class="px-2 py-1 bg-black/60 backdrop-blur-md border border-white/10 text-[8px] font-black text-gray-400 uppercase tracking-widest rounded-md">RAW_RENDER</span>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-[12px] font-black text-white uppercase tracking-wider truncate">{{ $image->product_name }}</h3>
                <div class="flex justify-between items-center pt-4 border-t border-white/5 mt-4">
                    <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Neural Asset</span>
                    <button type="button" @click.stop="openDownloadPicker(@js($mediaUrl), @js($downloadName))"
                            class="text-gray-600 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        @break

    @case('branded')
        <div x-show="(clientFilter === 'all' || clientFilter == '{{ $asset->user_id }}') && (filter === 'all' || filter === 'branded')"
             x-transition:enter="transition ease-out duration-300"
             class="group bg-[#0a0a0a] border border-emerald-500/10 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-emerald-500/40">
            <div class="relative aspect-square bg-black cursor-pointer overflow-hidden"
                 @click="openModal = true; currentImage = @js($mediaUrl); $dispatch('notify', {message: 'Enlarging Branded Still', type: 'info'})">
                <img src="{{ $mediaUrl }}" alt="{{ $image->product_name }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-all duration-500" loading="lazy"
                     onerror="this.src=''; this.classList.add('opacity-30'); this.alt='Image unavailable';">
                <div class="absolute top-4 left-4">
                    <span class="px-2 py-1 bg-emerald-600 text-[8px] font-black text-white uppercase tracking-widest rounded-md shadow-lg shadow-emerald-600/20">Identity_Applied</span>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-[12px] font-black text-white uppercase tracking-wider truncate">{{ $image->product_name }}</h3>
                <div class="flex justify-between items-center pt-4 border-t border-white/5 mt-4">
                    <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Master Still</span>
                    <button type="button" @click.stop="openDownloadPicker(@js($mediaUrl), @js($downloadName))"
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
             class="group bg-[#0a0a0a] border border-orange-500/10 rounded-2xl overflow-hidden shadow-2xl transition-all hover:border-orange-500/40">
            <div class="relative aspect-square bg-black cursor-pointer overflow-hidden"
                 @click="openModal = true; currentImage = @js($mediaUrl); $dispatch('notify', {message: 'Enlarging Merged Still', type: 'info'})">
                <img src="{{ $mediaUrl }}" alt="{{ $image->product_name }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-all duration-500" loading="lazy"
                     onerror="this.src=''; this.classList.add('opacity-30'); this.alt='Image unavailable';">
                <div class="absolute top-4 left-4">
                    <span class="px-2 py-1 bg-orange-600 text-[8px] font-black text-white uppercase tracking-widest rounded-md shadow-lg shadow-orange-600/20">Merged_Template</span>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-[12px] font-black text-white uppercase tracking-wider truncate">{{ $image->product_name }}</h3>
                <div class="flex justify-between items-center pt-4 border-t border-white/5 mt-4">
                    <span class="text-[9px] font-black text-orange-500 uppercase tracking-widest">Merged Still</span>
                    <button type="button" @click.stop="openDownloadPicker(@js($mediaUrl), @js($downloadName))"
                            class="text-gray-600 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </button>
                </div>
                @php
                    $mergedApproval = $requiresApproval
                        ? \App\Http\Controllers\ApprovalController::approvalRecord('cgi', $image->id, 'image', 'merged')
                        : null;
                @endphp
                @include('partials.approval-control', [
                    'requiresApproval' => $requiresApproval,
                    'genId'     => $image->id,
                    'mediaUrl'  => $mediaUrl,
                    'mediaType' => 'image',
                    'variant'   => 'merged',
                    'isBranded' => false,
                    'approval'  => $mergedApproval,
                ])
            </div>
        </div>
        @break
@endswitch
