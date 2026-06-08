@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="mt-10">
        <div class="flex flex-col items-center gap-4">
            <p class="text-[9px] font-bold text-gray-600 uppercase tracking-widest">
                @if ($paginator->total() > 0)
                    Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
                @else
                    No results
                @endif
            </p>

            <div class="inline-flex items-center gap-1 bg-white/[0.03] border border-white/10 rounded-xl p-1">
                @if ($paginator->onFirstPage())
                    <span class="px-3 py-2 text-[9px] font-black uppercase tracking-widest text-gray-700 cursor-not-allowed rounded-lg">Prev</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                       class="px-3 py-2 text-[9px] font-black uppercase tracking-widest text-gray-400 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Prev</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 text-[9px] font-bold text-gray-600">…</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="min-w-[2.25rem] px-3 py-2 text-center text-[9px] font-black uppercase tracking-widest text-black bg-white rounded-lg">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}"
                                   class="min-w-[2.25rem] px-3 py-2 text-center text-[9px] font-black uppercase tracking-widest text-gray-400 hover:text-white hover:bg-white/10 rounded-lg transition-colors">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                       class="px-3 py-2 text-[9px] font-black uppercase tracking-widest text-gray-400 hover:text-white hover:bg-white/10 rounded-lg transition-colors">Next</a>
                @else
                    <span class="px-3 py-2 text-[9px] font-black uppercase tracking-widest text-gray-700 cursor-not-allowed rounded-lg">Next</span>
                @endif
            </div>
        </div>
    </nav>
@endif
