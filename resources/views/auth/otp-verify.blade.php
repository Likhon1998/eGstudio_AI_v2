<x-guest-layout>
    <div class="max-w-md w-full mx-auto p-8 bg-[#0a0a0a] border border-white/10 rounded-2xl shadow-2xl">
        <h2 class="text-xl font-black text-white uppercase tracking-widest mb-2 text-center mt-2">Verify Identity</h2>
        <p class="text-[11px] text-gray-500 font-bold uppercase tracking-widest text-center mb-8">Enter the 6-digit transmission code sent to your email.</p>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                <ul class="text-[10px] font-black text-red-500 uppercase tracking-widest list-none space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>⚠ {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.otp.check') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div>
                <x-input-label for="otp" value="Temporary Password" class="text-purple-400 text-xs uppercase tracking-widest" />
                <input id="otp" type="text" name="otp" required autofocus autocomplete="off" placeholder="XXXXXX"
                       class="block mt-1 w-full bg-transparent border border-purple-500/30 text-white focus:border-purple-500 focus:ring-purple-500 font-mono tracking-[0.5em] text-center rounded-md shadow-sm transition-colors caret-white text-2xl py-3" />
            </div>

            <div class="mt-8 flex flex-col gap-3">
                <button type="submit" class="w-full px-6 py-3 bg-purple-600 border border-purple-500 text-white rounded text-[11px] font-black uppercase tracking-widest transition-all hover:bg-purple-500 shadow-[0_0_15px_rgba(168,85,247,0.4)]">
                    Authenticate Code
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>