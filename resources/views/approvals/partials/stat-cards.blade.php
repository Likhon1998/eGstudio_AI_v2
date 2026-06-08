{{-- 2x2 overview stat grid. Expects: $stats array with keys pending, approved, rejected, total --}}
@php
    $cards = [
        ['label' => 'Awaiting Review', 'value' => $stats['pending'],  'ring' => 'bg-amber-500/10 text-amber-400',     'bar' => 'bg-amber-500',   'd' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Approved',        'value' => $stats['approved'], 'ring' => 'bg-emerald-500/10 text-emerald-400', 'bar' => 'bg-emerald-500', 'd' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Rejected',        'value' => $stats['rejected'], 'ring' => 'bg-red-500/10 text-red-400',         'bar' => 'bg-red-500',     'd' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Total Assets',    'value' => $stats['total'],    'ring' => 'bg-blue-500/10 text-blue-400',       'bar' => 'bg-blue-500',    'd' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
    ];
@endphp
<div class="grid grid-cols-2 gap-3 sm:gap-4 h-full">
    @foreach($cards as $card)
        <div class="relative bg-[#0c0c0e] border border-white/[0.06] rounded-2xl p-4 overflow-hidden flex flex-col justify-between min-h-[100px]">
            <span class="absolute left-0 top-3 bottom-3 w-0.5 rounded-full {{ $card['bar'] }} opacity-70"></span>
            <div class="flex items-center justify-between gap-2">
                <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider leading-tight">{{ $card['label'] }}</span>
                <span class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 {{ $card['ring'] }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['d'] }}"></path></svg>
                </span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-white mt-2 tracking-tight tabular-nums">{{ $card['value'] }}</div>
        </div>
    @endforeach
</div>
