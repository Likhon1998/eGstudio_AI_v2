<x-guest-layout>
    <div class="max-w-md w-full mx-auto p-8 bg-[#0a0a0a] border border-white/10 rounded-2xl shadow-2xl">
        <h2 class="text-xl font-black text-purple-400 uppercase tracking-widest mb-2 text-center mt-2">Authentication Successful</h2>
        <p class="text-[11px] text-gray-500 font-bold uppercase tracking-widest text-center mb-8">Establish your new permanent credentials.</p>

        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                <ul class="text-[10px] font-black text-red-500 uppercase tracking-widest list-none space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>⚠ {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.otp.update') }}">
            @csrf

            <div class="space-y-5">
                <div>
                    <x-input-label for="password" value="New Permanent Password" class="text-gray-400 text-xs uppercase tracking-widest" />
                    <input id="password" type="password" name="password" required autofocus placeholder="••••••••"
                           class="block mt-1 w-full bg-transparent border border-white/10 text-white focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm transition-colors caret-white placeholder:text-gray-700 py-3" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirm Permanent Password" class="text-gray-400 text-xs uppercase tracking-widest" />
                    <input id="password_confirmation" type="password" name="password_confirmation" required placeholder="••••••••"
                           class="block mt-1 w-full bg-transparent border border-white/10 text-white focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm transition-colors caret-white placeholder:text-gray-700 py-3" />
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-3">
                <button type="submit" class="w-full px-6 py-3 bg-green-600 border border-green-500 text-white rounded text-[11px] font-black uppercase tracking-widest transition-all hover:bg-green-500 shadow-[0_0_15px_rgba(34,197,94,0.4)]">
                    Establish New Neural Link
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>