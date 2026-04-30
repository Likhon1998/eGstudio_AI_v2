<x-guest-layout>
    <div class="max-w-md w-full mx-auto p-8 bg-[#0a0a0a] border border-white/10 rounded-2xl shadow-2xl">
        <h2 class="text-xl font-black text-white uppercase tracking-widest mb-2 text-center">System Override</h2>
        <p class="text-[11px] text-gray-500 font-bold uppercase tracking-widest text-center mb-8">Enter your registered identity ping to receive a temporary password.</p>

        <form method="POST" action="{{ route('password.otp.send') }}">
            @csrf
            <div>
                <x-input-label for="email" value="Identity Email" class="text-gray-400 text-xs uppercase tracking-widest" />
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="director@egstudio.ai"
                       class="block mt-1 w-full bg-transparent border border-white/10 text-white caret-white placeholder:text-white/30 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm transition-colors" />
                
                @error('email')
                    <p class="mt-2 text-[10px] font-black text-red-500 uppercase tracking-widest">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between mt-8">
                <a class="text-[10px] text-gray-500 hover:text-white uppercase tracking-widest font-black transition-colors" href="{{ route('login') }}">Cancel Override</a>
                <button type="submit" class="px-6 py-2.5 bg-purple-600 border border-purple-500 text-white rounded text-[10px] font-black uppercase tracking-widest transition-all hover:bg-purple-500 shadow-[0_0_15px_rgba(168,85,247,0.4)]">
                    Transmit Temp Pass
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>