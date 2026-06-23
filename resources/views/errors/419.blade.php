<x-guest-layout>
    <div class="relative z-10 text-center space-y-5">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center">
            <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <div>
            <h2 class="text-xl font-semibold text-white">Session Expired</h2>
            <p class="mt-3 text-sm text-gray-400 leading-relaxed">
                This page was open too long or your login session ended. For your security, the form could not be submitted.
            </p>
            <p class="mt-2 text-sm text-gray-500">
                Please log in again to continue using eGStudio AI.
            </p>
        </div>

        <a href="{{ route('login', ['session_expired' => 1]) }}"
            class="inline-flex w-full justify-center py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg transition duration-200 shadow-[0_0_15px_rgba(37,99,235,0.4)]">
            Log In Again
        </a>

        <a href="{{ url('/') }}" class="inline-block text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-white transition-colors">
            Back to home
        </a>
    </div>
</x-guest-layout>
