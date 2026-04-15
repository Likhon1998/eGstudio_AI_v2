<div
    x-data="{ isLoading: false }"
    x-on:loading.window="isLoading = true"
    x-on:loading-done.window="isLoading = false"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm"
    style="display: none;"
    x-cloak
>
    <div class="flex flex-col items-center gap-4">
        <svg class="animate-spin h-10 w-10 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <span class="text-white text-sm font-bold uppercase tracking-widest">Loading...</span>
    </div>
</div>
