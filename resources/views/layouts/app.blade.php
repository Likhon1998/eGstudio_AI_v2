<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'eGStudio_AI') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-gray-100 bg-black flex h-screen overflow-hidden">

    <aside class="w-64 bg-gray-900 border-r border-gray-800 hidden sm:flex sm:flex-col">
        <div class="h-16 flex items-center px-6 border-b border-gray-800">
            <span class="text-2xl font-bold tracking-tighter text-white">eGStudio_<span
                    class="text-blue-500">AI</span></span>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-4 py-3 bg-blue-600/10 text-blue-500 rounded-lg border border-blue-500/20 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                    </path>
                </svg>
                <span class="font-semibold">Dashboard</span>
            </a>

            <a href="{{ route('cgi.create') }}"
                class="{{ request()->routeIs('cgi.create') ? 'bg-blue-600/10 text-blue-500 border-blue-500/20' : 'text-gray-400 hover:bg-gray-800 hover:text-white border-transparent' }} flex items-center gap-3 px-4 py-3 rounded-lg border transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span class="font-semibold">CGI Directive</span>
            </a>

            <a href="{{ route('cgi.videos') }}"
                class="{{ request()->routeIs('cgi.videos') ? 'bg-pink-600/10 text-pink-500 border-pink-500/20' : 'text-gray-400 hover:bg-gray-800 hover:text-white border-transparent' }} flex items-center gap-3 px-4 py-3 rounded-lg border transition group">
                <svg class="w-5 h-5 {{ request()->routeIs('cgi.videos') ? 'text-pink-500' : 'text-gray-400 group-hover:text-white' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                    </path>
                </svg>
                <span class="font-semibold">Video Gallery</span>
            </a>

            <a href="{{ route('cgi.images') }}"
                class="{{ request()->routeIs('cgi.images') ? 'bg-pink-600/10 text-pink-500 border-pink-500/20' : 'text-gray-400 hover:bg-gray-800 hover:text-white border-transparent' }} flex items-center gap-3 px-4 py-3 rounded-lg border transition group">

                <svg class="w-5 h-5 {{ request()->routeIs('cgi.images') ? 'text-pink-500' : 'text-gray-400 group-hover:text-white' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>

                <span class="font-semibold">Image Gallery</span>
            </a>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">

        @include('layouts.navigation')

        <main
            class="flex-1 overflow-y-auto bg-black bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-gray-900 via-black to-black p-6 sm:p-10">
            {{ $slot }}
        </main>

    </div>
</body>

</html>