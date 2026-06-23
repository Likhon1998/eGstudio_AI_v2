<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if(request()->boolean('session_expired'))
        <div class="relative z-10 mb-5 px-4 py-3 bg-amber-500/10 border border-amber-500/20 text-amber-200 text-sm rounded-lg leading-relaxed">
            Your session expired while you were working. Please log in again to continue safely.
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="relative z-10">
        @csrf
        <h2 class="text-2xl font-semibold mb-6 text-white text-center">Director Login</h2>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-400">Email</label>
            <input id="email" class="block mt-1 w-full bg-black/50 border border-gray-700 text-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-lg p-3" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500" />
        </div>

        <div class="mt-4" x-data="{ showPassword: false }">
            <label for="password" class="block text-sm font-medium text-gray-400">Password</label>
            <div class="relative mt-1">
                <input id="password"
                    class="block w-full bg-black/50 border border-gray-700 text-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-lg p-3 pr-11"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password" />
                <button type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-300 transition-colors"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'">
                    <svg x-show="!showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858 3.029a3 3 0 114.243-4.243m-9.878 9.878L3 21m15-12.879l2.121-2.121M3 3l18 18"></path>
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500" />
        </div>

        <div class="block mt-4 flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded bg-gray-900 border-gray-700 text-blue-600 focus:ring-blue-500" name="remember">
                <span class="ms-2 text-sm text-gray-400">Keep me logged in</span>
            </label>

            {{-- NEW: Forgot Password Link connecting to your OTP System --}}
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-[10px] font-black text-gray-500 hover:text-white uppercase tracking-widest transition-colors">
                    Forget password? 
                </a>
            @endif
        </div>

        <div class="mt-8">
            <button class="w-full justify-center py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg transition duration-200 shadow-[0_0_15px_rgba(37,99,235,0.4)] hover:shadow-[0_0_25px_rgba(37,99,235,0.6)]" data-loading>
                Log In
            </button>
        </div>
    </form>
</x-guest-layout>