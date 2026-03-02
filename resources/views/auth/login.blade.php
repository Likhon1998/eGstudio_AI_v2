<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="relative z-10">
        @csrf
        <h2 class="text-2xl font-semibold mb-6 text-white text-center">Director Login</h2>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-400">System Email</label>
            <input id="email" class="block mt-1 w-full bg-black/50 border border-gray-700 text-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-lg p-3" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500" />
        </div>

        <div class="mt-4">
            <label for="password" class="block text-sm font-medium text-gray-400">Access Key</label>
            <input id="password" class="block mt-1 w-full bg-black/50 border border-gray-700 text-gray-100 focus:border-blue-500 focus:ring-blue-500 rounded-lg p-3" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500" />
        </div>

        <div class="block mt-4 flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded bg-gray-900 border-gray-700 text-blue-600 focus:ring-blue-500" name="remember">
                <span class="ms-2 text-sm text-gray-400">Keep me secured</span>
            </label>
        </div>

        <div class="mt-8">
            <button class="w-full justify-center py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg transition duration-200 shadow-[0_0_15px_rgba(37,99,235,0.4)] hover:shadow-[0_0_25px_rgba(37,99,235,0.6)]">
                INITIALIZE TERMINAL
            </button>
        </div>
    </form>
</x-guest-layout>