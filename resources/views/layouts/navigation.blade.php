<nav x-data="{ open: false }" class="h-16 bg-gray-900/80 backdrop-blur-lg border-b border-gray-800 flex items-center justify-between px-6 sticky top-0 z-50">
    
    <div class="flex items-center sm:hidden">
        <span class="text-xl font-bold tracking-tighter text-white">eGStudio_<span class="text-blue-500">AI</span></span>
    </div>

    <div class="hidden sm:block"></div>

    <div class="flex items-center">
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2 text-sm text-gray-400 hover:text-white transition">
                    <div class="w-8 h-8 rounded-full bg-gray-800 flex items-center justify-center border border-gray-700">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <span class="hidden sm:block">{{ Auth::user()->name }}</span>
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
            </x-slot>
            
            <x-slot name="content">
                <div class="bg-gray-800 border border-gray-700 rounded-md">
                    <x-dropdown-link :href="route('profile.edit')" class="text-gray-300 hover:bg-gray-700 hover:text-white">
                        {{ __('Profile') }}
                    </x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-400 hover:bg-gray-700 hover:text-red-300 border-t border-gray-700">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </div>
            </x-slot>
        </x-dropdown>
    </div>
</nav>