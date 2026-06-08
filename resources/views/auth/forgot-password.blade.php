<x-guest-layout>
    {{-- Chrome Autofill Defeater --}}
    <style>
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #0a0a0a inset !important;
            -webkit-text-fill-color: #ffffff !important;
            caret-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>

    <div class="max-w-md w-full mx-auto p-8 bg-[#0a0a0a] border border-white/10 rounded-2xl shadow-2xl">
        <h2 class="text-xl font-black text-white uppercase tracking-widest mb-2 text-center">System Override</h2>
        <p class="text-[11px] text-gray-500 font-bold uppercase tracking-widest text-center mb-8">Enter your registered identity ping to receive a 6-digit OTP code.</p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.otp.send') }}">
            @csrf
            
            <div>
                <x-input-label for="email" value="Identity Email" class="text-gray-400 text-xs uppercase tracking-widest" />
                
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autofocus 
                       placeholder="director@egstudio.ai"
                       class="block mt-1 w-full bg-transparent border border-white/10 text-white caret-white placeholder:text-white focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm transition-colors" />
                
                @error('email')
                    <p class="mt-2 text-[10px] font-black text-red-500 uppercase tracking-widest flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="flex items-center justify-between mt-8">
                <a class="text-[10px] text-gray-500 hover:text-white uppercase tracking-widest font-black transition-colors" href="{{ route('login') }}">
                    Cancel Override
                </a>
                <button type="submit" class="px-6 py-2.5 bg-purple-600/20 border border-purple-500/50 text-purple-400 hover:bg-purple-600 hover:text-white rounded text-[10px] font-black uppercase tracking-widest transition-all shadow-lg hover:shadow-purple-500/20">
                    Send OTP Code
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>